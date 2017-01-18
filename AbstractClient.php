<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Event\EventDispatcherHelper;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Message\ResponseCollection;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
abstract class AbstractClient
{
    /**
     * @var int Number of multi connections.
     */
    private $connections = 8;

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

    /**
     * @var EventDispatcherHelper
     */
    protected $dispatcherHelper;

    /**
     * @param bool $throwException
     */
    public function __construct($throwException)
    {
        $this->dispatcherHelper = new EventDispatcherHelper();
        if ($throwException === true) {
            $this->dispatcherHelper->addHttpErrorListener();
        }
    }

    /**
     * @param int $connections Number of parallel connections.
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
    }

    /**
     * @param RequestHandler $handler
     *
     * @return Response[]
     */
    protected function sendRequest(RequestHandler $handler)
    {
        $this->responseCollection = new ResponseCollection();

        $ch = curl_init();
        curl_setopt_array($ch, $handler->getOptions());

        $this->dispatcherHelper->requestBefore($handler->getRequest(), $this);
        curl_exec($ch);

        $this->handleResource($handler, $ch);
        curl_close($ch);

        return $this->responseCollection->all();
    }

    /**
     * @param array $urls
     * @param RequestHandler $handler
     * @param callable       $rejectUrl
     *
     * @return Response[]
     */
    protected function sendMultiRequest(array $urls, RequestHandler $handler, callable $rejectUrl = null)
    {
        $this->responseCollection = new ResponseCollection();
        $this->index = 0;
        $this->requestUrls = array_values($urls);
        $rejectUrl = $rejectUrl ?: function () {};
        $connections = ($maxConnection = count($urls)) < $this->connections ? $maxConnection : $this->connections;

        $mh = curl_multi_init();
        // Add initial urls to the multi handle
        for ($i = 0; $i < $connections; $i++) {
            $this->addUrlToHandle($mh, $handler, $rejectUrl);
        }

        // Initial execution
        while (CURLM_CALL_MULTI_PERFORM === $mrc = curl_multi_exec($mh, $active));

        while ($active && $mrc == CURLM_OK) {
            // Handle PHP Bug, see: https://bugs.php.net/bug.php?id=61141
            if (curl_multi_select($mh) === -1) {
                usleep(250);
            }

            // Execute
            while (CURLM_CALL_MULTI_PERFORM === $mrc = curl_multi_exec($mh, $active));

            while ($mhInfo = curl_multi_info_read($mh)) {
                // One of the requests wes finished
                $ch = $mhInfo['handle'];

                $handler->getRequest()->setUrl($this->getTrackingUrl($ch));
                $this->handleResource($handler, $ch, true);

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }

            // Add a new url
            $this->addUrlToHandle($mh, $handler, $rejectUrl);
        }

        curl_multi_close($mh);

        return $this->responseCollection->all();
    }

    /**
     * Adds a url to the multi handle.
     *
     * @param resource       $mh Multi handle
     * @param RequestHandler $handler
     * @param callable       $rejectUrl
     */
    private function addUrlToHandle($mh, RequestHandler $handler, callable $rejectUrl)
    {
        if (!isset($this->requestUrls[$this->index])) {
            return;
        }

        $url = trim($this->requestUrls[$this->index]);

        if ($rejectUrl($url) === true) {
            unset($this->requestUrls[$this->index]);
            $this->index++;

            return;
        }

        $ch = curl_init();
        $this->setTrackingUrl($ch, $url);

        $options = $handler->getOptions();
        $options[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $options);
        curl_multi_add_handle($mh, $ch);
        // Increment so next url is used next time
        $this->index++;

        $this->dispatcherHelper->requestBefore($handler->getRequest()->setUrl($url), $this);
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
        $this->dispatcherHelper->requestComplete($handler->getRequest(), $response, $this, $multiRequest);
        // Add response to collection
        $this->responseCollection->add($response, (int)$ch);
    }

    /**
     * @param resource $ch cURL handle
     * @param string $url
     */
    private function setTrackingUrl($ch, $url)
    {
        $this->trackingUrls[(int)$ch] = $url;
    }

    /**
     * @param resource $ch cURL handle
     *
     * @return string|null
     */
    private function getTrackingUrl($ch)
    {
        $resourceId = (int)$ch;

        return isset($this->trackingUrls[$resourceId]) ? $this->trackingUrls[$resourceId] : null;
    }
}
