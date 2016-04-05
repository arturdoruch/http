<?php

namespace ArturDoruch\Http\Message;

/**
 * Trait implementing functionality common to requests and responses.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
trait MessageTrait
{
    /**
     * @var string The http protocol
     */
    private $protocol = 'HTTP/1.1';

    /**
     * @var array Cached HTTP header collection with lowercase key to values
     */
    private $headers = array();

    /**
     * HTTP header names collection with lowercase to original capitalization header name
     *
     * @var array
     */
    private $headerNames = array();

    /**
     * @return string The http protocol
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     *
     * @return $this
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Returns the headers, with original capitalization's.
     *
     * @return array An array of headers
     */
    public function getHeaders()
    {
        return array_combine($this->headerNames, $this->headers);
    }

    /**
     * Gets single header fields by specified name.
     *
     * @param string $name Header name.
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Checks if given hearer name exist in headers array.
     *
     * @param string $name Header name.
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @param string $name Header field name.
     * @param string $value
     *
     * @return $this
     */
    public function addHeader($name, $value)
    {
        if (is_string($name) && is_scalar($value)) {
            $name = trim($name);
            $lowercaseName = strtolower($name);

            $this->headers[$lowercaseName] = trim($value);
            $this->headerNames[$lowercaseName] = $name;
        }

        return $this;
    }

    /**
     * Sets message headers
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }

        return $this;
    }
}
