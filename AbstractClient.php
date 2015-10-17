<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Curl\ResourceHandler;
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

    /**
     * @var int Urls array index.
     */
    private $index = 0;

    /**
     * @var array
     */
    private $multiRequestConfig = array();

    /**
     * @var array
     */
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

    /**
     * @param array $urls
     * @param array $options
     * @param Request $request
     */
    protected function sendMultiRequest(array $urls, array $options, Request $request)
    {
        $this->resourceHandler->setCollection(true);
        $this->index = 0;
        $this->multiRequestConfig['urls'] = array_values($urls);
        $this->multiRequestConfig['options'] = $options;

        $mh = curl_multi_init();
        $multiExec = function () use ($mh, &$mrc, &$active) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        };

        // Add multiple URLs to the multi handle
        for ($i = 0; $i < $this->connections; $i++) {
            $this->addUrlToMultiHandle($mh);
        }

        // Initial execution
        $multiExec();

        while ($active && $mrc == CURLM_OK) {
            // There is activity
            if (curl_multi_select($mh) == -1) {
                usleep(250);
            }
            // Do work
            $multiExec();

            if ($mhInfo = curl_multi_info_read($mh)) {
                // One of the requests were finished
                $handle = $mhInfo['handle'];
                $request->setUrl($this->getTrackingUrl($handle));
                $this->resourceHandler->handle($handle, $request, $this);

                curl_multi_remove_handle($mh, $handle);
                //curl_close($mhInfo['handle']);

                // Add a new url and do work
                if ($this->addUrlToMultiHandle($mh)) {
                    $multiExec();
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
        $urls = $this->multiRequestConfig['urls'];
        $options = $this->multiRequestConfig['options'];

        if (!isset($urls[$this->index])) {
            return false;
        }

        $handle = curl_init();
        $url = $options[CURLOPT_URL] = trim($urls[$this->index]);

        $this->setTrackingUrl($handle, $url);

        curl_setopt_array($handle, $options);
        // Add it to the multi handle
        curl_multi_add_handle($mh, $handle);
        // Increment so next url is used next time
        $this->index++;

        return true;
    }

    /**
     * @param resource $handle
     * @param string $url
     */
    private function setTrackingUrl($handle, $url)
    {
        $resourceId = (int) $handle;
        $this->trackingUrls[$resourceId] = $url;
    }

    /**
     * @param resource $handle
     *
     * @return string|null
     */
    private function getTrackingUrl($handle)
    {
        $resourceId = (int) $handle;

        return isset($this->trackingUrls[$resourceId]) ? $this->trackingUrls[$resourceId] : null;
    }

}
 