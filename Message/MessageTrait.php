<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;

/**
 * Trait implementing functionality common to requests and responses.
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
     * @var array Actual key to list of values per header.
     */
    private $headerLines = array();

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
     * @return array
     */
    public function getHeaders()
    {
        return $this->headerLines;
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
     * @return $this
     */
    public function addHeader($name, $value)
    {
        if (is_string($name) && is_scalar($value)) {
            $name = trim($name);
            $value = trim($value);
            $this->headers[strtolower($name)] = $value;
            $this->headerLines[$name] = $value;
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