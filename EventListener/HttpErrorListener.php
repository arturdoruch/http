<?php

namespace ArturDoruch\Http\EventListener;

use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Exception\RequestException;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class HttpErrorListener
{
    /**
     * Throws RequestException when response status code is 4xx, 5xx or 0.
     *
     * @param CompleteEvent $event
     *
     * @throws RequestException
     */
    public function onComplete(CompleteEvent $event)
    {
        if ($event->isMultiRequest()) {
            return;
        }

        $code = (string) $event->getResponse()->getStatusCode();

        if ($code[0] >= 4 || $code[0] == 0) {
            throw RequestException::create($event->getResponse(), null, $event->getRequest());
        }
    }
}
