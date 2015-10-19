<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;

/**
 * Representation of an outgoing, server-side response.
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
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

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
     */
    public function setReasonPhrase($reasonPhrase)
    {
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

}
 