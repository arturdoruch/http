<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Curl\MessageHandlerFactory;
use ArturDoruch\Http\Curl\Options;
use ArturDoruch\Http\Message\Response;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Client extends AbstractClient
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var CookieFile
     */
    private $cookieFile;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $lastResponse;

    /**
     * @var MessageHandlerFactory
     */
    private $messageHandlerFactory;

    /**
     * @param array $curlOptions     cURL options as array with key, value pairs, where
     *                               key is cURL option. Key can be in three different formats:
     *                                - full constant name or,
     *                                - constant integer value or,
     *                                - constant name without part "CURLOPT_".
     *                               For example to set CURLOPT_TIMEOUT to 15 seconds,
     *                               pass [CURLOPT_TIMEOUT => 15] or [13 => 15] or ['timeout' => 15].
     * @param bool $throwException   It true RequestException will be thrown, when server, client
     *                               or connection error occur.
     * @param CookieFile $cookieFile Set if you want to store website session cookies into text file.
     */
    public function __construct(array $curlOptions = array(), $throwException = true, CookieFile $cookieFile = null)
    {
        $this->cookieFile = $cookieFile;
        $this->options = new Options($cookieFile, $curlOptions);
        $this->request = new Request('GET', '-');
        $this->messageHandlerFactory = new MessageHandlerFactory($this->options);

        parent::__construct($throwException);
    }

    /**
     * @deprecated
     * @param string $filename  Path to file where session cookies will be stored.
     */
    public function setCookieFilename($filename)
    {
        if ($this->cookieFile) {
            $this->cookieFile->setFile($filename);
        }
    }

    /**
     * Gets path to currently used cookie file.
     *
     * @return string
     */
    public function getCookieFilename()
    {
        return $this->cookieFile ? $this->cookieFile->getFilename() : null;
    }

    /**
     * @todo Rename method to getCurlOptions()
     *
     * Gets default cURL options.
     *
     * @param bool $keysAsConstants
     *
     * @return array
     */
    public function getDefaultCurlOptions($keysAsConstants = false)
    {
        return $this->options->getDefault($keysAsConstants);
    }

    /**
     * Sets default cURL options, which will be used in every request.
     *
     * @param array $options
     */
    public function setDefaultCurlOptions(array $options)
    {
        $this->options->setDefault($options);
    }

    /**
     * @todo Rename method to getLastCurlOptions()
     *
     * Gets cURL options used to send the last request.
     *
     * @param bool $keysAsConstants
     *
     * @return array
     */
    public function getCurlOptions($keysAsConstants = false)
    {
        return $this->options->getLast($keysAsConstants);
    }

    /**
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Adds listener to one of HTTP request event:
     * BEFORE - event called just before send HTTP request,
     * COMPLETE - event called when HTTP request is done.
     *
     * @param string   $eventName One of RequestEvents constants.
     * @param callable $listener  The listener function receive one argument - event object.
     *                            For BEFORE event will be ArturDoruch\Http\Event\BeforeEvent,
     *                            for COMPLETE event will be ArturDoruch\Http\Event\CompleteEvent object.
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain.
     */
    public function addListener($eventName, callable $listener, $priority = 0)
    {
        $this->dispatcherHelper->getDispatcher()->addListener($eventName, $listener, $priority);
    }

    /**
     * Makes GET request.
     *
     * @param string $url
     * @param array $parameters Url query parameters
     * @param array $options
     * @param array $curlOptions
     *
     * @return Response
     */
    public function get($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'GET', $parameters, $options), $curlOptions);
    }

    /**
     * Makes POST request.
     *
     * @param string $url
     * @param array $parameters
     * @param array $options
     * @param array $curlOptions
     *
     * @return Response
     */
    public function post($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'POST', $parameters, $options), $curlOptions);
    }

    /**
     * Makes PATCH request.
     *
     * @param string $url
     * @param array $parameters
     * @param array $options
     * @param array $curlOptions
     *
     * @return Response
     */
    public function patch($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'PATCH', $parameters, $options), $curlOptions);
    }

    /**
     * Makes PUT request.
     *
     * @param string $url
     * @param array $parameters Form parameters
     * @param array $options
     * @param array $curlOptions
     *
     * @return Response
     */
    public function put($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'PUT', $parameters, $options), $curlOptions);
    }

    /**
     * Makes DELETE request.
     *
     * @param string $url
     * @param array $parameters Form parameters
     * @param array $options
     * @param array $curlOptions
     *
     * @return Response
     */
    public function delete($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'DELETE', $parameters, $options), $curlOptions);
    }

    /**
     * @param Request $request
     * @param array   $curlOptions
     *
     * @return Response
     */
    public function request(Request $request, array $curlOptions = array())
    {
        $messageHandler = $this->messageHandlerFactory->create($request, $curlOptions);

        return $this->lastResponse = $this->sendRequest($messageHandler);
    }

    /**
     * Makes multi parallel requests.
     *
     * @param Request[]|array $requests The list of Requests or urls.
     * @param Request  $request @deprecated This parameter is not used any more.
     * @param array    $curlOptions
     * @param int      $connections Number of parallel connections
     * @param callable $rejectUrl   The listener allowing to reject added request url. Received
     *                              one argument $url and must return true if url should be rejected.
     *
     * @return Response[]
     */
    public function multiRequest(array $requests, Request $request = null, array $curlOptions = array(), $connections = null, callable $rejectUrl = null)
    {
        if ($connections) {
            $this->setConnections($connections);
        }

        $messageHandlers = [];

        foreach ($requests as $request) {
            if (!$request instanceof Request) {
                $request = $this->createRequest($request);
            }

            $messageHandlers[] = $this->messageHandlerFactory->create($request, $curlOptions);
        }

        return $this->sendMultiRequest($messageHandlers, $rejectUrl);
    }

    /**
     * Creates an Request object.
     *
     * @param string $url
     * @param string $method
     * @param array $parameters Form parameters
     * @param array $options
     *  - cookie
     *  - headers
     *  - body
     *  - json
     *  - files
     *
     * @return Request
     */
    public function createRequest($url, $method = 'GET', array $parameters = array(), array $options = null)
    {
        $request = clone $this->request;
        $request
            ->setUrl($url)
            ->setMethod($method)
            ->setParameters($parameters);

        if (!empty($options)) {
            if (isset($options['cookie'])) {
                $request->addCookie((string) $options['cookie']);
            }

            if (isset($options['headers'])) {
                $request->setHeaders($options['headers']);
            }

            if (isset($options['body'])) {
                $request->setBody($options['body']);
            } elseif (isset($options['json']) || isset($options['files'])) {
                $request->setBody($options);
            }
        }

        return $request;
    }
}
