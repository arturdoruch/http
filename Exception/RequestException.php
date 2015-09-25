<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Exception;

use ArturDoruch\Http\Curl\Codes;
use ArturDoruch\Http\Message\Response;

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
     * Get the associated response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
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
        } elseif (static::isConnectionError($response)) {
            return 'Connection error';
        }

        return 'Unsuccessful response';
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
        if (static::isConnectionError($response)) {
            return static::createConnect($response, $previous);
        }

        $level = floor($response->getStatusCode() / 100);

        if ($level == '4') {
            $className = __NAMESPACE__ . '\\ClientException';
        } elseif ($level == '5') {
            $className = __NAMESPACE__ . '\\ServerException';
        } else {
            $className = __CLASS__;
        }

        $message = static::getErrorLabel($response) . ' [url] ' . $response->getRequestUrl();
        if ($response->getStatusCode() > 0) {
            $message .= ' [status code] ' . $response->getStatusCode() . ' [reason phrase] ' . $response->getReasonPhrase();
        }

        $errorMsg = trim($response->getErrorMsg());
        if (!empty($errorMsg)) {
            $message .= ' [error message] ' . $errorMsg;
        }

        return new $className($message, $response, $previous);
    }

    /**
     * @param Response $response
     * @param \Exception $previous
     *
     * @return ConnectException
     */
    private static function createConnect(Response $response, \Exception $previous = null)
    {
        $className = __NAMESPACE__ . '\\ConnectException';
        $message = sprintf(
            'Connection error [url] %s [error number] %d [error message] %s',
            $response->getRequestUrl(), $response->getErrorNumber(), $response->getErrorMsg()
        );

        return new $className($message, $response, $previous);
    }

    /**
     * @param Response $response
     * @return bool
     */
    private static function isConnectionError(Response $response)
    {
        return $response->getStatusCode() === 0 && Codes::isConnectionError($response->getErrorNumber());
    }
}
