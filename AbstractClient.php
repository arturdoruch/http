<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Event\EventManager;
use ArturDoruch\Http\Message\ResponseCollection;

abstract class AbstractClient
{
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
    private $requestUrls = array();

    /**
     * @var array
     */
    private $trackingUrls = array();

    /**
     * @var ResponseCollection
     */
    private $responseCollection;


    public function __construct($exceptions)
    {
        $this->eventManager = new EventManager();
        if ($exceptions === true) {
            $this->eventManager->enabledHttpErrorListener();
        }

        $this->completeEvent = new CompleteEvent();
    }

    /**
     * @param RequestHandler $handler
     *
     * @return \ArturDoruch\Http\Message\Response[]
     */
    protected function sendRequest(RequestHandler $handler)
    {
        $this->responseCollection = new ResponseCollection();

        $ch = curl_init();

        curl_setopt_array($ch, $handler->getOptions());
        curl_exec($ch);

        $this->handleResource($handler, $ch);
        curl_close($ch);

        return $this->responseCollection->all();
    }

    /**
     * @param array $urls
     * @param RequestHandler $handler
     *
     * @return \ArturDoruch\Http\Message\Response[]
     */
    protected function sendMultiRequest(array $urls, RequestHandler $handler)
    {
        $this->responseCollection = new ResponseCollection();

        $this->index = 0;
        $this->requestUrls = array_values($urls);
        $options = $handler->getOptions();

        $mh = curl_multi_init();
        $multiExec = function () use ($mh, &$mrc, &$active) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        };

        // Add multiple URLs to the multi handle
        for ($i = 0; $i < $this->connections; $i++) {
            $this->addUrlToMultiHandle($mh, $options);
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
                $ch = $mhInfo['handle'];

                $handler->getRequest()->setUrl($this->getTrackingUrl($ch));
                $this->handleResource($handler, $ch, true);

                curl_multi_remove_handle($mh, $ch);
                //curl_close($h);

                // Add a new url and do work
                if ($this->addUrlToMultiHandle($mh, $options)) {
                    $multiExec();
                }
            }
        }

        curl_multi_close($mh);

        return $this->responseCollection->all();
    }

    /**
     * Adds a url to the multi handle
     *
     * @param resource $mh Multi handle
     * @param array $options
     * @return bool
     */
    private function addUrlToMultiHandle($mh, array $options)
    {
        if (!isset($this->requestUrls[$this->index])) {
            return false;
        }

        $ch = curl_init();
        $options[CURLOPT_URL] = trim($this->requestUrls[$this->index]);

        $this->setTrackingUrl($ch, $options[CURLOPT_URL]);

        curl_setopt_array($ch, $options);
        // Add it to the multi handle
        curl_multi_add_handle($mh, $ch);
        // Increment so next url is used next time
        $this->index++;

        return true;
    }

    /**
     * Handles response cURL resource.
     *
     * @param RequestHandler $handler
     * @param resource $ch
     * @param bool $multiRequest
     */
    private function handleResource(RequestHandler $handler, $ch, $multiRequest = false)
    {
        // Create response
        $response = $handler->createResponse($ch);
        // Dispatch event
        $this->completeEvent->setMultiRequest($multiRequest);
        $this->completeEvent->setData($handler->getRequest(), $response, $this);
        $this->eventManager->dispatch('complete', $this->completeEvent);
        // Add response to collection
        $this->responseCollection->add($response, (int) $ch);
    }

    /**
     * @param resource $ch cURL handle
     * @param string $url
     */
    private function setTrackingUrl($ch, $url)
    {
        $this->trackingUrls[(int) $ch] = $url;
    }

    /**
     * @param resource $ch cURL handle
     *
     * @return string|null
     */
    private function getTrackingUrl($ch)
    {
        $resourceId = (int) $ch;

        return isset($this->trackingUrls[$resourceId]) ? $this->trackingUrls[$resourceId] : null;
    }

}
 