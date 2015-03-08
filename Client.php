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
     * @var RequestParameter
     */
    private $requestParameter;

    /**
     * @var array
     */
    private $parameters = array(
        'parameters' => array(),
        'headers' => array(),
        'cookie' => null,
        'options' => array()
    );

    /**
     * @param array       $options        An array with key => value pairs, where key is related to cURL option
     *                                    constant name without "CURLOPT_" part or constant integer value.
     *                                    For example to set CURLOPT_TIMEOUT on 15000
     *                                    pass ['timeout' => 15000] or [13 => 15000].
     * @param int         $connections    Number of multi connections.
     * @param string|null $cookieFile     Path to file to storage cookie information
     *                                    to sent or retrieve from server.
     */
    public function __construct(array $options = array(), $connections = 8, $cookieFile = null)
    {
        $this->options = new Options($cookieFile);
        $this->options->setDefault($options);

        $this->requestParameter = new RequestParameter();
        $this->setConnections($connections);

        parent::__construct();
    }

    /**
     * @param int $connections Number of multi connections.
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
    }

    /**
     * @param $cookieFile
     */
    public function setCookieFile($cookieFile)
    {
        $this->options->setCookieFile($cookieFile);
    }

    /**
     * @param string|null      $url
     * @param RequestParameter $parameters
     * @param array            $options     An array with key => value pairs, where key is related to cURL option
     *                                      constant name without "CURLOPT_" part or constant integer value.
     *
     * @return ResponseCollection
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
        return $this->request(null, $this->setRequestParameters($url, 'GET', $parameters));
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
        return $this->request(null, $this->setRequestParameters($url, 'POST', $parameters));
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
        $this->options->set($parameters['options']);

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
 