<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

use ArturDoruch\Http\Exception\RequestException;

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
        if ($event->isMultiRequest()) {
            return;
        }

        $code = (string) $event->getResponse()->getStatusCode();
        // Throw an exception for an unsuccessful response
        if ($code[0] >= 4 || $code[0] == 0) {
            throw RequestException::create($event->getResponse());
        }
    }
}
