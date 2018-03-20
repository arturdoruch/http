<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Curl\MessageHandler;
use ArturDoruch\Http\Event\EventDispatcherHelper;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Message\ResponseCollection;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
abstract class AbstractClient
{
    /**
     * @var int Number of parallel connections.
     */
    private $connections = 8;

    /**
     * @var MessageHandler[]
     */
    private $messageHandlers = [];

    /**
     * Messages handlers grouped by cURL handel id.
     *
     * @var MessageHandler[]
     */
    private $messageHandlerRegister = [];

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
     * @param MessageHandler $messageHandler
     *
     * @return Response
     */
    protected function sendRequest(MessageHandler $messageHandler)
    {
        $this->responseCollection = new ResponseCollection();

        $handle = curl_init();
        curl_setopt_array($handle, $messageHandler->getOptions());

        $this->dispatcherHelper->requestBefore($messageHandler->getRequest(), $this);

        curl_exec($handle);
        $this->handleResponse($handle, $messageHandler);
        curl_close($handle);

        return $this->responseCollection->all()[0];
    }

    /**
     * Sends parallel requests.
     *
     * @param MessageHandler[] $messageHandlers
     * @param callable         $rejectUrl
     *
     * @return Response[]
     */
    protected function sendMultiRequest($messageHandlers, callable $rejectUrl = null)
    {
        $this->responseCollection = new ResponseCollection();
        $this->messageHandlers = $messageHandlers;

        $rejectUrl = $rejectUrl ?: function () {};
        $maxConnections = count($this->messageHandlers);
        $connections = $maxConnections < $this->connections ? $maxConnections : $this->connections;

        $mh = curl_multi_init();
        // Add initial urls to the multi handle
        for ($i = 0; $i < $connections; $i++) {
            $this->addMultiRequest($mh, $rejectUrl);
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
                // One of the requests has been finished.
                $handel = $mhInfo['handle'];
                $this->handleResponse($handel, $this->getMessageHandler($handel));

                curl_multi_remove_handle($mh, $handel);
                curl_close($handel);
            }

            // Add a new url
            $this->addMultiRequest($mh, $rejectUrl);
        }

        curl_multi_close($mh);

        unset($this->messageHandlerRegister);

        return $this->responseCollection->all();
    }

    /**
     * Adds request to the multi handle.
     *
     * @param resource       $multiHandel Multi handle
     * @param callable       $rejectUrl
     */
    private function addMultiRequest($multiHandel, callable $rejectUrl)
    {
        if (!$messageHandler = array_shift($this->messageHandlers)) {
            return;
        }

        $request = $messageHandler->getRequest();

        if ($rejectUrl($request->getUrl()) === true) {
            return;
        }

        $handel = curl_init();
        curl_setopt_array($handel, $messageHandler->getOptions());
        curl_multi_add_handle($multiHandel, $handel);

        $this->registerMessageHandler($handel, $messageHandler);

        $this->dispatcherHelper->requestBefore($request, $this);
    }

    /**
     * Handles response cURL resource.
     *
     * @param resource $handel The cULR handel
     * @param MessageHandler $messageHandler
     * @param bool $multiRequest
     */
    private function handleResponse($handel, MessageHandler $messageHandler, $multiRequest = false)
    {
        $response = $messageHandler->createResponse($handel);
        // Dispatch event
        $this->dispatcherHelper->requestComplete($messageHandler->getRequest(), $response, $this, $multiRequest);
        $this->responseCollection->add($response, (int)$handel);
    }

    /**
     * @param resource $handle cURL handle
     * @param MessageHandler $messageHandler
     */
    private function registerMessageHandler($handle, MessageHandler $messageHandler)
    {
        $this->messageHandlerRegister[(int) $handle] = $messageHandler;
    }

    /**
     * @param resource $handle cURL handle
     *
     * @return MessageHandler
     */
    private function getMessageHandler($handle)
    {
        return $this->messageHandlerRegister[(int) $handle];
    }
}
