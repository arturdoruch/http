<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\RequestBody;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Request
{
    use MessageTrait;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var array Form or url query parameters
     */
    private $parameters = [];

    /**
     * @var string|array
     */
    private $body;

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * @var RequestBody
     */
    private $requestBody;

    public function __construct($method = 'GET', $url)
    {
        $this->setMethod($method);
        $this->setUrl($url);
        $this->requestBody = new RequestBody();
    }

    public function __clone()
    {
        $this->requestBody = clone $this->requestBody;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        self::validateUrl($url);
        $this->url = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies($cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param string $cookie
     * @return $this
     */
    public function addCookie($cookie)
    {
        $this->cookies[] = trim($cookie);

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Removes all set parameters.
     */
    public function clearParameters()
    {
        $this->parameters = [];
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string|array $body Content to send with request.
     * For sending raw data pass string.
     * For sending json pass array with data under key "json".
     * Example:
     *      setBody(['json' => [
     *          'data' => 'value'
     *      ]]);
     *
     * For sending files pass array with PostFile instances under key "files".
     * Example:
     *      setBody(['files' => [
     *          new PostFile($name, $file, $filename = null)
     *      ]]);
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $this->requestBody->parseBody($body);
        $this->setContentType($this->requestBody->getContentType());

        return $this;
    }

    /**
     * @param string $contentType
     */
    private function setContentType($contentType)
    {
        if (!$this->getHeader('Content-Type') && $contentType) {
            $this->addHeader('Content-Type', $contentType);
        }
    }

    /**
     * @param string $url
     */
    private static function validateUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('The request url cannot be empty.');
        }

        if (!is_string($url)) {
            throw new \InvalidArgumentException(sprintf(
                'The request url must be type of string, but got "%s".', gettype($url)
            ));
        }
    }
}
 