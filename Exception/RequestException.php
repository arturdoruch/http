<?php

namespace ArturDoruch\Http\Exception;

use ArturDoruch\Http\Curl\Codes;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;

/**
 * HTTP request exception.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
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

    /**
     * @todo Not create ConnectException with this method.
     * @todo Make $request parameter required.
     *
     * Creates exception with a normalized message.
     *
     * @param Request $request
     * @param Response $response
     * @param \Exception $previous
     *
     * @return self
     */
    final public static function create(Response $response, \Exception $previous = null, Request $request = null)
    {
        if ($response->getStatusCode() >= 500) {
            $className = ServerException::class;
        } elseif ($response->getStatusCode() >= 400) {
            $className = ClientException::class;
        } elseif (self::isConnectionError($response)) {
            $className = ConnectException::class;
        } else {
            $className = static::class;
        }

        return new $className('', $response, $previous, $request);
    }

    /**
     * @param string $message The error message. If empty, then will be created normalized.
     * @param Response $response
     * @param \Exception|null $previous
     * @param Request|null $request The request caused this error.
     */
    public function __construct($message = '', Response $response, \Exception $previous = null, Request $request = null)
    {
        $this->response = $response;
        $this->request = $request;
        parent::__construct($message ?: self::createMessage($request, $response), $response->getStatusCode(), $previous);
    }

    /**
     * Gets the request caused this error.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @deprecated
     *
     * @return bool
     */
    public function hasRequest()
    {
        return $this->request !== null;
    }

    /**
     * Gets request response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }


    private static function createMessage(Request $request = null, Response $response)
    {
        $message = static::getErrorType($response) . ': ';
        $errorMessage = trim($response->getErrorMsg());

        if (0 === $statusCode = $response->getStatusCode()) {
            $message .= $response->getErrorNumber() . ' ' . $errorMessage;
        } else {
            $message .= $statusCode . ' ';
            $message .= $errorMessage ?: $response->getReasonPhrase();
        }

        $message = rtrim($message, '.,!') . ', while request ';

        if ($request) {
            $message .= 'GET ';
        }

        return $message . $response->getRequestUrl() . '.';
    }

    /**
     * @deprecated Use method getErrorType() instead.
     * @param Response $response
     *
     * @return string
     */
    public static function getErrorLabel(Response $response)
    {
        return static::getErrorType($response);
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    public static function getErrorType(Response $response)
    {
        if ($response->getStatusCode() >= 500) {
            return 'Server request error';
        }

        if ($response->getStatusCode() >= 400) {
            return 'Client request error';
        }

        if (self::isConnectionError($response)) {
            return 'Connection request error';
        }

        return 'Request error';
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    private static function isConnectionError(Response $response)
    {
        return $response->getStatusCode() === 0 && Codes::isConnectionError($response->getErrorNumber());
    }
}
