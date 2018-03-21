<?php

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher
{
    /**
     * Dispatches ArturDoruch\Http\Event\RequestEvents::BEFORE event.
     *
     * @param Request $request
     * @param Client $client
     */
    public function dispatchRequestBefore(Request $request, Client $client)
    {
        $this->dispatch(RequestEvents::BEFORE, new BeforeEvent($request, $client));
    }

    /**
     * Dispatches ArturDoruch\Http\Event\RequestEvents::COMPLETE event.
     *
     * @param Request $request
     * @param Response $response
     * @param Client $client
     * @param bool $multiRequest
     */
    public function dispatchRequestComplete(Request $request, Response $response, Client $client, $multiRequest = false)
    {
        $event = new CompleteEvent($request, $response, $client, $multiRequest);
        $this->dispatch(RequestEvents::COMPLETE, $event);
    }
}
 