<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Curl\Options;

use ArturDoruch\Http\Response\Response;
use ArturDoruch\Http\Response\ResponseCollection;


class Client extends AbstractClient
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var RequestParameter
     */
    private $requestParameter;

    /**
     * @var array
     */
    private $parameters = array(
        'parameters' => array(),
        'headers' => array(),
        'cookie' => null
    );

    /**
     * @param array       $options        An array with key => value pairs, where key is related to cURL option
     *                                    constant name without "CURLOPT_" part or constant integer value.
     *                                    For example to set CURLOPT_TIMEOUT on 15000
     *                                    pass ['timeout' => 15000] or [13 => 15000].
     * @param bool        $enabledExceptions
     */
    public function __construct(array $options = array(), $enabledExceptions = true)
    {
        $this->options = new Options();
        $this->options->setDefault($options);

        $this->requestParameter = new RequestParameter();

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
     * Sets default cURL options, that will be used in every requests.
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
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($listener, $priority = 0)
    {
        $this->eventManager->addListener('request.complete', $listener, $priority);
    }

    /**
     * @param string|null      $url
     * @param RequestParameter $parameters
     * @param array            $options     An array with key => value pairs, where key is related to cURL option
     *                                      constant name without "CURLOPT_" part or constant integer value.
     *
     * @return Response
     */
    public function request($url, RequestParameter $parameters = null, array $options = array())
    {
        if ($url !== null && !is_string($url)) {
            throw new \InvalidArgumentException(
                sprintf('Parameter $url must be a type of string. Type of "%s" given.', gettype($url))
            );
        }

        $options = $this->options->parse($url, $parameters, $options);
        $this->sendRequest($options);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * @param array            $urls        Collection of urls
     * @param RequestParameter $parameters  Request parameters
     * @param array            $options     An array with key => value pairs, where key is related to cURL option
     *                                      constant name without "CURLOPT_" part or constant integer value.
     * @param int              $connections Number of maximum multi connections
     *
     * @return ResponseCollection
     */
    public function multiRequest(array $urls, RequestParameter $parameters = null, array $options = array(), $connections = null)
    {
        if ($connections) {
            $this->setConnections($connections);
        }

        $options = $this->options->parse(null, $parameters, $options);
        $this->sendMultiRequest($urls, $options);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * Makes GET request
     *
     * @param string $url
     * @param array $parameters
     *  - parameters array
     *  - headers    array
     *  - cookie     string
     *  - options    array  cURL options
     *
     * @return ResponseCollection
     */
    public function get($url, array $parameters = array())
    {
        $options = isset($parameters['options']) ? $parameters['options'] : array();

        return $this->request(null, $this->setRequestParameters($url, 'GET', $parameters), $options);
    }

    /**
     * Makes POST request
     *
     * @param string $url
     * @param array $parameters
     *  - parameters array
     *  - headers    array
     *  - cookie     string
     *  - options    array  cURL options
     *
     * @return ResponseCollection
     */
    public function post($url, array $parameters = array())
    {
        $options = isset($parameters['options']) ? $parameters['options'] : array();

        return $this->request(null, $this->setRequestParameters($url, 'POST', $parameters), $options);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $parameters
     *
     * @return RequestParameter
     */
    private function setRequestParameters($url, $method, array $parameters)
    {
        $parameters = array_merge($this->parameters, $parameters);

        $this->requestParameter
            ->setUrl($url)
            ->setMethod($method)
            ->setParameters($parameters['parameters'])
            ->setCookies(array())
            ->addCookie($parameters['cookie'])
            ->setHeaders($parameters['headers']);

        return $this->requestParameter;
    }

}
 