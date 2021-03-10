<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\RequestBodyCompiler;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Request
{
    use MessageTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array The form data or URL query parameters.
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $cookies = [];

    public function __construct($method, $url)
    {
        $this->setMethod($method);
        $this->setUrl($url);
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
     *
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);

        return $this;
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
     *
     * @return $this
     */
    public function setUrl($url)
    {
        if (!$url) {
            throw new \InvalidArgumentException('Empty request URL.');
        }

        $this->url = (string) $url;

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
     * Sets the request body. This method overrides the "Content-Type" header.
     *
     * @param string|array $body The request body. Allowed body contents:
     *  - (string) plain text
     *  - (resource) resource
     *  - (array) with one of the keys:
     *    - "json" for sending JSON.
     *      Usage:
     *          setBody(['json' => [
     *              'key' => 'value'
     *          ]]);
     *
     *    - "files" with instances of the "ArturDoruch\Http\Post\PostFile" class for sending the files.
     *      Usage:
     *          setBody(['files' => [
     *              new PostFile($name, $file[, $filename = null])
     *          ]]);
     *
     * @param string $contentType The body content type. If not specified it will be determined based on the body.
     *
     * @return $this
     */
    public function setBody($body, $contentType = '')
    {
        $this->body = RequestBodyCompiler::compile($body, $bodyContentType);
        $this->addHeader('Content-Type', $contentType ?: $bodyContentType);

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
     *
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
     *
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
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     *
     * @return $this
     */
    public function setCookies($cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param string $cookie
     *
     * @return $this
     */
    public function addCookie($cookie)
    {
        $this->cookies[] = trim($cookie);

        return $this;
    }
}
 