<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

class Request
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $cookies = array();

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var string
     */
    private $method = 'GET';

    public function __clone()
    {

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
        $this->parameters = array();
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
        $this->method = $method;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets single header fields by specified name.
     *
     * @param string $name
     * @return mixed
     */
    public function getHeader($name)
    {
        //$name = ucfirst($name);

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

}
 