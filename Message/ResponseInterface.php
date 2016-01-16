<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;


interface ResponseInterface
{
    /**
     * Gets the response status code.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode);

    /**
     * @return string Reason phrase
     */
    public function getReasonPhrase();

    /**
     * @param string $reasonPhrase
     *
     * @return $this
     */
    public function setReasonPhrase($reasonPhrase);

    /**
     * @return string The http protocol
     */
    public function getProtocol();

    /**
     * @param string $protocol
     *
     * @return $this
     */
    public function setProtocol($protocol);

    /**
     * @return array
     */
    public function getHeaders();

    /**
     * Gets single header fields by specified name.
     *
     * @param string $name Header name.
     *
     * @return string|null
     */
    public function getHeader($name);

    /**
     * Checks if given hearer name exist in headers array.
     *
     * @param string $name Header name.
     *
     * @return bool
     */
    public function hasHeader($name);

    /**
     * @param string $name Header field name.
     * @param string $value
     *
     * @return $this
     */
    public function addHeader($name, $value);

} 