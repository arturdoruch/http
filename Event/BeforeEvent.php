<?php

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Request;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class BeforeEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Request $request
     * @param Client $client
     */
    public function __construct(Request $request, Client $client)
    {
        $this->request = $request;
        $this->client = $client;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
 