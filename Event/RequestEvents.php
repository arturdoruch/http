<?php

namespace ArturDoruch\Http\Event;

/**
 * Contains events thrown while HTTP request is making.
 * @todo In version 4 move this class to ArturDoruch\Http namespace.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
final class RequestEvents
{
    /**
     * The event dispatched before sending the HTTP request.
     *
     * @Event("ArturDoruch\Http\Event\BeforeEvent")
     */
    const BEFORE = 'request.before';

    /**
     * The event dispatched when the HTTP request is sent.
     *
     * @Event("ArturDoruch\Http\Event\CompleteEvent")
     */
    const COMPLETE = 'request.complete';
}
 