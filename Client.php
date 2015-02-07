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
    private $cookieFilePath;

    /**
     * @param array       $options        cURL options
     * @param int         $connections    Number of multi connections.
     * @param string|null $cookieFilePath Path to file to storage cookie information
     *                                    to sent or retrieve from server.
     */
    public function __construct(array $options = array(), $connections = 8, $cookieFilePath = null)
    {
        $this->cookieFilePath = $cookieFilePath;
        $this->setDefaultOptions($options);
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
     * @param string           $url
     * @param RequestParameter $parameters
     * @param array            $options cURL options
     *
     * @return ResponseCollection
     */
    public function makeRequest($url, RequestParameter $parameters = null, array $options = array())
    {
        $options = $this->parseOptions($url, $parameters, $options);
        $this->request($options);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * @param array            $urls        Collection of urls
     * @param RequestParameter $parameters  Request parameters
     * @param array            $options     cURL options
     * @param int              $connections Number of maximum multi connections
     *
     * @return ResponseCollection
     */
    public function makeMultiRequest(array $urls, RequestParameter $parameters = null, array $options = array(), $connections = null)
    {
        if ($connections) {
            $this->setConnections($connections);
        }

        $options = $this->parseOptions(null, $parameters, $options);
        $this->multiRequest($urls, $options);

        return $this->resourceHandler->getResponseCollection();
    }

    /**
     * @param string           $url
     * @param RequestParameter $parameters
     * @param array            $options
     * @return array
     */
    private function parseOptions($url = null, RequestParameter $parameters = null, array $options = array())
    {
        $options = $options + $this->defaultOptions;
        $options[CURLOPT_URL] = $url;

        if ($parameters) {
            if ($url = $parameters->getUrl()) {
                $options[CURLOPT_URL] = $url;
            }

            if ($params = $parameters->getParameters()) {
                $method = $parameters->getMethod();
                if ($method == 'POST') {
                    $params = http_build_query($params);
                    //$options[CURLOPT_CUSTOMREQUEST] = "POST";
                    //$options[CURLOPT_VERBOSE] = true;
                    $options[CURLOPT_POSTFIELDS] = $params;
                    $options[CURLOPT_POST] = count($params);
                } elseif ($method == 'GET') {
                    $options[CURLOPT_URL] .= '?' . http_build_query($params);
                }
            }

            if ($cookies = $parameters->getCookies()) {
                if (count($cookies) == 1) {
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
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => true,
            CURLOPT_COOKIEJAR => $this->cookieFilePath,
            CURLOPT_COOKIEFILE => $this->cookieFilePath,
            155 => 90000, // TIMEOUT_MS - 90 seconds,
        );

        $this->defaultOptions = $options + $defaultOptions;
    }

}
 