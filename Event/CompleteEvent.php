<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;
use Symfony\Component\EventDispatcher\Event;

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
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Response|Response[]
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response|Response[] $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Request $request
     * @param Response|Response[] $response
     * @param Client $client
     */
    public function setData(Request $request, $response, Client $client)
    {
        $this->setRequest($request);
        $this->setResponse($response);
        $this->setClient($client);
    }

    /**
     * @return bool
     */
    public function isMultiRequest()
    {
        return $this->multiRequest;
    }

    /**
     * @param bool $multiRequest
     */
    public function setMultiRequest($multiRequest)
    {
        $this->multiRequest = $multiRequest;
    }

}
 