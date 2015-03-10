<?php

namespace ArturDoruch\Http\Exception;

use ArturDoruch\Http\Response\Response;

/**
 * HTTP Request exception
 */
class RequestException extends \RuntimeException
{
    /**
     * @var Response
     */
    private $response;

    public function __construct($message, Response $response = null, \Exception $previous = null)
    {
        $code = $response ? $response->getStatusCode() : 0;
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param Response $response Response received
     * @param \Exception        $previous Previous exception
     *
     * @return self
     */
    public static function create(Response $response = null, \Exception $previous = null)
    {
        if (!$response) {
            return new self('Error completing request', null, $previous);
        }

        $level = floor($response->getStatusCode() / 100);
        if ($level == '4') {
            $label = 'Client error response';
            $className = __NAMESPACE__ . '\\ClientException';
        } elseif ($level == '5') {
            $label = 'Server error response';
            $className = __NAMESPACE__ . '\\ServerException';
        } else {
            $label = 'Unsuccessful response';
            $className = __CLASS__;
        }

        $message = $label . ' [url] ' . $response->getUrl()
            . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new $className($message, $response, $previous);
    }

    /**
     * Get the associated response
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Check if a response was received
     *
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }
}
