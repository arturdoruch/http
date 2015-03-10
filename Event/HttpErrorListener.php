<?php

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Throws exceptions when a 4xx or 5xx response is received
 */
class HttpErrorListener
{
    /**
     * Throw a RequestException on an HTTP protocol error
     *
     * @param CompleteEvent $event Emitted event
     * @throws RequestException
     */
    public function onComplete(CompleteEvent $event)
    {
        $code = (string) $event->getResponse()->getStatusCode();
        // Throw an exception for an unsuccessful response
        if ($code[0] >= 4) {
            throw RequestException::create($event->getResponse());
        }
    }
}
