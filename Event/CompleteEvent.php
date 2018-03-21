<?php

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class CompleteEvent extends Event
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
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $multiRequest = false;

    /**
     * @param Request $request
     * @param Response $response
     * @param Client $client
     * @param bool $multiRequest
     */
    public function __construct(Request $request, $response, Client $client, $multiRequest)
    {
        $this->request = $request;
        $this->response = $response;
        $this->client = $client;
        $this->multiRequest = $multiRequest;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return bool
     */
    public function isMultiRequest()
    {
        return $this->multiRequest;
    }
}
 