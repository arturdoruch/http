<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventManager
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
     * Enables throwing HTTP response status code 4xx and 5xx exceptions.
     */
    public function enabledHttpErrorListener()
    {
        $this->addListener(array(new HttpErrorListener(), 'onComplete'));
    }

    /**
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($listener, $priority = 0)
    {
        $this->dispatcher->addListener('request.complete', $listener, $priority);
    }

    /**
     * @param Event $event
     */
    public function dispatch(Event $event)
    {
        $this->dispatcher->dispatch('request.complete', $event);
    }

}
 