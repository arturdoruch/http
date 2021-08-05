<?php

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher
{
    private $eventArgumentFirst;

    public function __construct()
    {
        parent::__construct();
        $this->eventArgumentFirst = is_subclass_of($this, EventDispatcherInterface::class);
    }

    /**
     * Dispatches the ArturDoruch\Http\Event\RequestEvents::BEFORE event.
     *
     * @param Request $request
     * @param Client $client
     */
    public function dispatchRequestBefore(Request $request, Client $client)
    {
        $event = new BeforeEvent($request, $client);

        if ($this->eventArgumentFirst) {
            $this->dispatch($event, RequestEvents::BEFORE);
        } else {
            $this->dispatch(RequestEvents::BEFORE, $event);
        }
    }

    /**
     * Dispatches the ArturDoruch\Http\Event\RequestEvents::COMPLETE event.
     *
     * @param Request $request
     * @param Response $response
     * @param Client $client
     * @param bool $multiRequest
     */
    public function dispatchRequestComplete(Request $request, Response $response, Client $client, $multiRequest = false)
    {
        $event = new CompleteEvent($request, $response, $client, $multiRequest);

        if ($this->eventArgumentFirst) {
            $this->dispatch($event, RequestEvents::COMPLETE);
        } else {
            $this->dispatch(RequestEvents::COMPLETE, $event);
        }
    }
}
