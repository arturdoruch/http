<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Event\Listener\HttpErrorListener;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherHelper
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
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
     * Dispatches BEFORE event to listeners, before send HTTP request.
     *
     * @param Request $request
     * @param Client $client
     */
    public function requestBefore(Request $request, Client $client)
    {
        $event = new BeforeEvent($request, $client);
        $this->dispatcher->dispatch(RequestEvents::BEFORE, $event);
    }

    /**
     * Dispatches COMPLETE event to listeners, when HTTP request is done.
     *
     * @param Request $request
     * @param Response $response
     * @param Client $client
     * @param bool $multiRequest
     */
    public function requestComplete(Request $request, Response $response, Client $client, $multiRequest = false)
    {
        $event = new CompleteEvent($request, $response, $client, $multiRequest);
        $this->dispatcher->dispatch(RequestEvents::COMPLETE, $event);
    }

}
 