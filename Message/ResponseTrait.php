<?php

namespace ArturDoruch\Http\Message;

use ArturDoruch\Http\Util\ResponseUtils;

/**
 * Representation of an outgoing, server-side response.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
trait ResponseTrait
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * The reason phrase of the response (human readable code).
     *
     * @var string
     */
    private $reasonPhrase;

    /**
     * Gets the response status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @param string|null $reasonPhrase
     *
     * @return $this
     */
    public function setStatusCode($statusCode, $reasonPhrase = null)
    {
        $this->statusCode = (int) $statusCode;
        $this->reasonPhrase = $reasonPhrase ?: ResponseUtils::getReasonPhrase($statusCode);

        return $this;
    }

    /**
     * @return string Reason phrase
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * @param string $reasonPhrase
     *
     * @return $this
     *
     * @deprecated Set reason phrase with setStatusCode() method instead.
     */
    public function setReasonPhrase($reasonPhrase)
    {
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }
}
