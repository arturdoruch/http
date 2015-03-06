<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Response\ResponseCollection;

class Client extends AbstractClient
{
    /**
     * @var array Default cURL options
     */
    private $defaultOptions;
    /**
     * @var string
     */
    private $cookieFile;

    /**
     * @var RequestParameter
     */
    private $requestParameter;

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
        $this->cookieFile = $cookieFile ?: __DIR__ . '/cookies.txt';
        $this->setDefaultOptions($options);
        $this->setConnections($connections);
        $this->requestParameter = new RequestParameter();

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
        $this->cookieFile = $cookieFile;
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

        $options = $this->parseOptions($url, $parameters, $options);
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

        $options = $this->parseOptions(null, $parameters, $options);
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
        $this->setOptions($parameters['options']);

        $this->requestParameter
            ->setUrl($url)
            ->setMethod($method)
            ->setParameters($parameters['parameters'])
            ->setCookies(array())
            ->addCookie($parameters['cookie'])
            ->setHeaders($parameters['headers']);

        return $this->requestParameter;
    }

    /**
     * @param string           $url
     * @param RequestParameter $parameters
     * @param array            $options
     * @return array
     */
    private function parseOptions($url = null, RequestParameter $parameters = null, array $options = array())
    {
        $options = $this->setOptions($options);
        $options[CURLOPT_URL] = $url;

        if ($parameters) {
            if ($url = $parameters->getUrl()) {
                $options[CURLOPT_URL] = $url;
            }

            if ($params = $parameters->getParameters()) {
                $method = $parameters->getMethod();
                if ($method == 'POST') {
                    $params = http_build_query($params);

                    $options[CURLOPT_POSTFIELDS] = $params;
                    $options[CURLOPT_POST] = count($params);
                } elseif ($method == 'GET') {
                    $options[CURLOPT_URL] .= '?' . http_build_query($params);
                } else {
                    //$options[CURLOPT_CUSTOMREQUEST] = "POST";
                    //$options[CURLOPT_VERBOSE] = true;
                }
            }

            if ($cookies = $parameters->getCookies()) {
                if (count($cookies) === 1) {
                    $options[CURLOPT_COOKIE] = $cookies[0];
                } else {
                    // ToDo Write $cookies in cookies.txt file ?
                }
            }

            if ($headers = $parameters->getHeaders()) {
                $options[CURLOPT_HTTPHEADER] = $headers;
            }
        }

        return $options;
    }

    private function setDefaultOptions(array $options)
    {
        $defaultOptions = array(
            CURLOPT_USERAGENT => isset($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            155 => 90000,
        );

        $this->defaultOptions = $this->validateOptions($options) + $defaultOptions;
    }

    /**
     * Sets cURL options.
     *
     * @param array $options
     * @return array
     */
    private function setOptions(array $options)
    {
        return $this->defaultOptions = $this->validateOptions($options) + $this->defaultOptions;
    }


    private function validateOptions(array $options)
    {
        $validOptions = array();
        foreach ($options as $option => $value) {
            if (!is_numeric($option)) {
                if ($opt = @constant('CURLOPT_' . strtoupper($option))) {
                    $validOptions[$opt] = $value;
                }
            }
        }

        return $validOptions;
    }

}
 