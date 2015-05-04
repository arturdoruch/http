<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Curl\Options;
use ArturDoruch\Http\Response\Response;

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
     * @param array $curlOptions
     * An array with key => value pairs. Key is cURL option and can be in three different formats:
     * as full constant name, constant integer value or constant name without part "CURLOPT_".
     * For example to set CURLOPT_TIMEOUT to 15 seconds,
     * pass [CURLOPT_TIMEOUT => 15] or [13 => 15] or ['timeout' => 15].
     * 
     * @param bool $enabledExceptions
     * @param CookieFile $cookieFile
     */
    public function __construct(array $curlOptions = array(), $enabledExceptions = true, CookieFile $cookieFile = null)
    {
        $this->cookieFile = $cookieFile ?: new CookieFile();
        $this->options = new Options($this->cookieFile);
        $this->options->setDefault($curlOptions);
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
     * @param string $filename  Path to file where session cookies will be stored.
     */
    public function setCookieFile($filename)
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
     * Makes GET request
     *
     * @param string $url
     * @param array  $parameters Request parameters
     * @param array  $options    Others request parameters
     *  - body
     *  - cookie
     *  - headers
     * @param array  $curlOptions
     *
     * @return Response
     */
    public function get($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'GET', $parameters, $options), $curlOptions);
    }

    /**
     * Makes POST request
     *
     * @param string $url
     * @param array  $parameters Request parameters
     * @param array  $options    Others request parameters
     *  - body
     *  - cookie
     *  - headers
     * @param array  $curlOptions
     *
     * @return Response
     */
    public function post($url, array $parameters = array(), array $options = array(), array $curlOptions = array())
    {
        return $this->request($this->createRequest($url, 'POST', $parameters, $options), $curlOptions);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $parameters Request parameters
     * @param array  $options    Others request parameters
     *  - body
     *  - cookie
     *  - headers
     *
     * @return Request
     */
    public function createRequest($url = null, $method = 'GET', array $parameters = array(), array $options = null)
    {
        $request = clone $this->request;
        $request
            ->setMethod($method)
            ->setParameters($parameters);

        if (!empty($url)) {
            $request->setUrl($url);
        }

        if (!empty($options)) {
            if (isset($options['cookie'])) {
                $request->addCookie($options['cookie']);
            }

            if (isset($options['headers'])) {
                $request->setHeaders($options['headers']);
            }

            if (isset($options['body'])) {
                $request->setBody($options['body']);
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

        return $responses[0];
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
 