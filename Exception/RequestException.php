<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Exception;

use ArturDoruch\Http\Curl\Codes;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;

/**
 * HTTP Request exception
 */
class RequestException extends \RuntimeException
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    public function __construct($message, Response $response, \Exception $previous = null, Request $request = null)
    {
        $this->response = $response;
        $this->request = $request;
        parent::__construct($message, $response->getStatusCode(), $previous);
    }

    /**
     * Get the request that caused the exception
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
     * Check if request was set
     *
     * @return bool
     */
    public function hasRequest()
    {
        return $this->request !== null;
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
     * @param Request $request
     * @param Response $response Response received
     * @param \Exception $previous Previous exception
     *
     * @return self
     */
    public static function create(Response $response, \Exception $previous = null, Request $request = null)
    {
        if (static::isConnectionError($response)) {
            return static::createConnect($response, $previous, $request);
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

        return new $className($message, $response, $previous, $request);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param \Exception $previous
     *
     * @return ConnectException
     */
    private static function createConnect(Response $response, \Exception $previous = null, Request $request = null)
    {
        $className = __NAMESPACE__ . '\\ConnectException';
        $message = sprintf(
            'Connection error [url] %s [error number] %d [error message] %s',
            $response->getRequestUrl(), $response->getErrorNumber(), $response->getErrorMsg()
        );

        return new $className($message, $response, $previous, $request);
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
