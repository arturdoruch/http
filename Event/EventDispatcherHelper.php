<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Event\Listener\HttpErrorListener;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherHelper
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var BeforeEvent
     */
    private $startEvent;

    /**
     * @var CompleteEvent
     */
    private $completeEvent;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->startEvent = new BeforeEvent();
        $this->completeEvent = new CompleteEvent();
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Adds listener throwing RequestException, when http request fail.
     */
    public function addHttpErrorListener()
    {
        $this->dispatcher->addListener(RequestEvents::COMPLETE, array(new HttpErrorListener(), 'onComplete'));
    }

    /**
     * Dispatches StartEvent to listeners, before send HTTP request.
     *
     * @param Request $request
     * @param Client $client
     */
    public function requestBefore(Request $request, Client $client)
    {
        $this->startEvent->setData($request, $client);
        $this->dispatcher->dispatch(RequestEvents::BEFORE, $this->startEvent);
    }

    /**
     * Dispatches CompleteEvent to listeners, when HTTP request is done.
     *
     * @param Request $request
     * @param Response $response
     * @param Client $client
     * @param bool $multiRequest
     */
    public function requestComplete(Request $request, Response $response, Client $client, $multiRequest = false)
    {
        $this->completeEvent->setData($request, $response, $client, $multiRequest);
        $this->dispatcher->dispatch(RequestEvents::COMPLETE, $this->completeEvent);
    }

}
 