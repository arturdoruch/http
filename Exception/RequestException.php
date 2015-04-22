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

    public function __construct($message, Response $response, \Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $response->getStatusCode(), $previous);
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param Response $response Response received
     * @param \Exception        $previous Previous exception
     *
     * @return self
     */
    public static function create(Response $response, \Exception $previous = null)
    {
        $level = floor($response->getStatusCode() / 100);
        if ($level == '4') {
            $className = __NAMESPACE__ . '\\ClientException';
        } elseif ($level == '5') {
            $className = __NAMESPACE__ . '\\ServerException';
        } else {
            $className = __CLASS__;
        }

        $message = static::getErrorLabel($response) . ' [url] ' . $response->getUrl()
            . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase()
            . ' [error message] ' . $response->getErrorMsg();

        return new $className($message, $response, $previous);
    }

    /**
     * @param Response $response
     * @return string
     */
    public static function getErrorLabel(Response $response)
    {
        $level = floor($response->getStatusCode() / 100);

        if ($level == '4') {
            return 'Client error response';
        } elseif ($level == '5') {
            return 'Server error response';
        }

        return 'Unsuccessful response';
    }

    /**
     * Get the associated response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

}
