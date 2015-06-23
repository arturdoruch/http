<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;


use ArturDoruch\Http\Event\EventManager;

abstract class AbstractClient
{
    /**
     * @var ResourceHandler
     */
    protected $resourceHandler;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var int Number of multi connections.
     */
    protected $connections = 8;

    private $index = 0;

    private $multiRequestConfig = array();

    private $trackingUrls = array();

    public function __construct()
    {
        $this->eventManager = new EventManager();
        $this->resourceHandler = new ResourceHandler($this->eventManager);
    }

    /**
     * @param array $options cURL options
     * @param Request $request
     *
     * @return mixed
     */
    protected function sendRequest(array $options, Request $request)
    {
        $this->resourceHandler->setCollection();
        
        $handle = curl_init();
        curl_setopt_array($handle, $options);

        curl_exec($handle);

        $request->setUrl($options[CURLOPT_URL]);
        $this->resourceHandler->handle($handle, $request, $this);

        curl_close($handle);
    }


    protected function sendMultiRequest(array $urls, array $options, Request $request)
    {
        $this->resourceHandler->setCollection(true);
        $this->index = 0;
        $this->multiRequestConfig['urls'] = array_values($urls);
        $this->multiRequestConfig['options'] = $options;

        $mh = curl_multi_init();

        // Add multiple URLs to the multi handle
        for ($i = 0; $i < $this->connections; $i++) {
            $this->addUrlToMultiHandle($mh);
        }

        // Initial execution
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            // There is activity
            if (curl_multi_select($mh) == -1) {
                usleep(1000);
            }
            // Do work
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            if ($mhInfo = curl_multi_info_read($mh)) {
                // This means one of the requests were finished
                $request->setUrl($this->getTrackingUrl($mhInfo['handle']));
                call_user_func_array(
                    array($this->resourceHandler, 'handle'),
                    array($mhInfo, $request, $this)
                );

                curl_multi_remove_handle($mh, $mhInfo['handle']);
                curl_close($mhInfo['handle']);

                // Add a new url and do work
                if ($this->addUrlToMultiHandle($mh)) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }
        }

        curl_multi_close($mh);
    }

    /**
     * Adds a url to the multi handle
     *
     * @param resource $mh Multi handle
     * @return bool
     */
    private function addUrlToMultiHandle($mh)
    {
        $config = $this->multiRequestConfig;

        // We are done adding new URLs
        if (!isset($config['urls'][$this->index])) {
            return false;
        }

        $ch = curl_init();

        $url = $config['options'][CURLOPT_URL] = trim($config['urls'][$this->index]);
        $this->setTrackingUrl($url, $ch);

        curl_setopt_array($ch, $config['options']);

        // Add it to the multi handle
        curl_multi_add_handle($mh, $ch);
        // Increment so next url is used next time
        $this->index++;

        return true;
    }

    private function setTrackingUrl($url, $handle)
    {
        $resourceId = $this->getResourceId($handle);
        $this->trackingUrls[$resourceId] = $url;
    }

    private function getTrackingUrl($handle)
    {
        $resourceId = $this->getResourceId($handle);

        return isset($this->trackingUrls[$resourceId]) ? $this->trackingUrls[$resourceId] : null;
    }

    private function getResourceId($handle)
    {
        return (int) filter_var($handle, FILTER_SANITIZE_NUMBER_INT);
    }
}
 