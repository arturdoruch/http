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
        $this->addListener('complete', array(new HttpErrorListener(), 'onComplete'));
    }

    /**
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        if ($eventName !== 'complete' && $eventName !== 'end') {
            throw new \InvalidArgumentException(
                'Invalid "eventName" value. Allowed values are "complete" or "end".'
            );
        }

        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * @param string $eventName
     * @param Event  $event
     */
    public function dispatch($eventName, Event $event)
    {
        $this->dispatcher->dispatch($eventName, $event);
    }

}
 