<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Curl\Options;
use ArturDoruch\Http\Message\Response;

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
     * @param array $curlOptions
     * cURL options as array with key, value pairs, when key is cURL option.
     * Key can be in three different formats:
     *  - full constant name or,
     *  - constant integer value or,
     *  - constant name without part "CURLOPT_".
     * For example to set CURLOPT_TIMEOUT to 15 seconds,
     * pass [CURLOPT_TIMEOUT => 15] or [13 => 15] or ['timeout' => 15].
     *
     * @param bool $throwExceptions
     * If true and http request return 0 or server or client error status code, then RequestException will be thrown.
     * @param CookieFile $cookieFile
     * Set if you want store website session cookies into custom location. As default cookies are storing into
     * "Cookie/cookies.txt" file.
     */
    public function __construct(array $curlOptions = array(), $throwExceptions = true, CookieFile $cookieFile = null)
    {
        $this->cookieFile = $cookieFile ?: new CookieFile();
        $this->options = new Options($this->cookieFile);
        $this->options->setDefault($curlOptions);
        $this->request = new Request();

        parent::__construct();

        if ($throwExceptions === true) {
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
     * @param string $filename  Path to file where session cookies will be stored.
     */
    public function setCookieFilename($filename)
    {
        $this->cookieFile->setFile($filename);
    }

    /**
     * Gets path to currently used cookie file.
     *
     * @return string
     */
    public function getCookieFilename()
    {
        return $this->cookieFile->getFilename();
    }

    /**
     * Gets default cURL options.
     *
     * @return array
     */
    public function getDefaultCurlOptions()
    {
        return $this->options->getDefault();
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
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Adds event listener, that will be called after HTTP request is complete.
     *
     * @param string   $eventName [complete]
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->eventManager->addListener($eventName, $listener, $priority);
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
     * Creates Request object with given parameters.
     *
     * @param string $url
     * @param string $method
     * @param array $parameters Request parameters
     * @param array $options
     *  - cookie
     *  - headers
     *  - body
     *  - json
     *  - files
     *
     * @return Request
     */
    public function createRequest($url = null, $method = 'GET', array $parameters = array(), array $options = null)
    {
        $request = clone $this->request;
        $request
            ->setUrl($url)
            ->setMethod($method)
            ->setParameters($parameters);

        if (!empty($options)) {
            if (isset($options['cookie'])) {
                $request->addCookie($options['cookie']);
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

    /**
     * @param Request $request
     * @param array   $curlOptions
     *
     * @return Response
     */
    public function request(Request $request, array $curlOptions = array())
    {
        $this->validateUrl($request->getUrl());

        $curlOptions = $this->options->parse($request, $curlOptions);
        $this->sendRequest($curlOptions, $request);

        $responses = $this->resourceHandler->getResponseCollection()->all();

        return $this->lastResponse = $responses[0];
    }

    /**
     * @param array   $urls Collection of urls
     * @param Request $request
     * @param array   $curlOptions
     * @param int     $connections Number of maximum multi connections
     *
     * @return Response[]
     */
    public function multiRequest(array $urls, Request $request = null, array $curlOptions = array(), $connections = null)
    {
        if ($connections) {
            $this->setConnections($connections);
        }

        if (!$request) {
            $request = $this->createRequest(null, 'GET');
        }

        $curlOptions = $this->options->parse($request, $curlOptions);
        $this->sendMultiRequest($urls, $curlOptions, $request);

        return $this->resourceHandler->getResponseCollection()->all();
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

}
