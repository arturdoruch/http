<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Curl\Options;
use ArturDoruch\Http\Response\ResponseCollection;

class Client extends AbstractClient
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param array  $options  An array with key => value pairs, where key is related to cURL option
     *                         constant name without "CURLOPT_" part or constant integer value.
     *                         For example to set CURLOPT_TIMEOUT on 15000
     *                         pass ['timeout' => 15000] or [13 => 15000].
     * @param bool   $enabledExceptions
     */
    public function __construct(array $options = array(), $enabledExceptions = true)
    {
        $this->options = new Options();
        $this->options->setDefault($options);
        $this->request = new Request();

        parent::__construct();

        if ($enabledExceptions === true) {
            $this->eventManager->enabledHttpErrorListener();
        }
    }

    /**
     * @param int $connections Number of multi connections.
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
    }

    /**
     * @param string $cookieFile  Path to file to storage cookie information to sent or retrieve from server.
     */
    public function setCookieFile($cookieFile)
    {
        $this->options->setCookieFile($cookieFile);
    }

    /**
     * Gets default cURL options.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->options->getDefault();
    }

    /**
     * Sets default cURL options, which will be used in every request.
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $this->options->setDefault($options);
    }

    /**
     * Adds event listener, that will be called after HTTP request is complete.
     *
     * @param string   $eventName [complete, end]
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->eventManager->addListener($eventName, $listener, $priority);
    }

    /**
     * @param string|null  $url
     * @param Request|null $request
     * @param array        $options  An array with key => value pairs, where key is related to cURL option
     *                               constant name without "CURLOPT_" part or constant integer value.
     *
     * @return ResponseCollection
     */
    public function request($url = null, Request $request = null, array $options = array())
    {
        $request = $this->setAndGetRequest($url, $request);

        $options = $this->options->parse($request, $options);
        $this->sendRequest($options, $request);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * @param array   $urls        Collection of urls
     * @param Request $request     Request parameters
     * @param array   $options     An array with key => value pairs, where key is related to cURL option
     *                             constant name without "CURLOPT_" part or constant integer value.
     * @param int     $connections Number of maximum multi connections
     *
     * @return ResponseCollection
     */
    public function multiRequest(array $urls, Request $request = null, array $options = array(), $connections = null)
    {
        if ($connections) {
            $this->setConnections($connections);
        }

        $request = $this->setAndGetRequest('array', $request);

        $options = $this->options->parse($request, $options);
        $this->sendMultiRequest($urls, $options, $request);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * Makes GET request
     *
     * @param string $url
     * @param array  $parameters
     *  - parameters array
     *  - headers    array
     *  - cookie     string
     * @param array  $options cURL options
     *
     * @return ResponseCollection
     */
    public function get($url, array $parameters = array(), array $options = array())
    {
        return $this->request(null, $this->createRequest($url, 'GET', $parameters), $options);
    }

    /**
     * Makes POST request
     *
     * @param string $url
     * @param array  $parameters
     *  - parameters array
     *  - headers    array
     *  - cookie     string
     *  @param array $options cURL options
     *
     * @return ResponseCollection
     */
    public function post($url, array $parameters = array(), array $options = array())
    {
        return $this->request(null, $this->createRequest($url, 'POST', $parameters), $options);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $parameters
     *
     * @return Request
     */
    public function createRequest($url, $method, array $parameters = array())
    {
        $request = clone $this->request;
        $request
            ->setUrl($url)
            ->setMethod($method);

        if (!empty($parameters)) {
            $parameters = array_merge(
                array('parameters' => array(), 'cookie' => null, 'headers' => array()),
                $parameters
            );

            $request
                ->setParameters($parameters['parameters'])
                ->addCookie($parameters['cookie'])
                ->setHeaders($parameters['headers']);
        }

        $this->validateUrl($request->getUrl());

        return $request;
    }

    /**
     * @param null|string  $url
     * @param Request|null $request
     *
     * @return Request
     */
    private function setAndGetRequest($url = null, Request $request = null)
    {
        if ($request === null) {
            $request = $this->createRequest($url, 'GET');
        } elseif (!$request->getUrl()) {
            $request->setUrl($url);
        }

        $this->validateUrl($request->getUrl());

        return $request;
    }


    private function validateUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('The "url" parameter cannot be empty.');
        }

        if (!is_string($url)) {
            throw new \InvalidArgumentException(sprintf(
                    'Invalid "url" parameter. Url must be type of string, but got "%s".', gettype($url)
                ));
        }
    }

    /*private function dispatchEndEvent(Request $request, Response $response)
    {
        // Dispatch event
        $endEvent = new EndEvent();
        $endEvent->setData($request, $response, $this);

        $this->eventManager->dispatch('end', $endEvent);
    }*/

}
 