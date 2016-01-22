<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Event;

/**
 * Contains events thrown while HTTP request is making.
 * @todo In version 4 move this class to ArturDoruch\Http namespace.
 */
final class RequestEvents
{
    /**
     * The BEFORE event is dispatched just before send HTTP request.
     *
     * @Event
     */
    const BEFORE = 'request.before';

    /**
     * The COMPLETE event is dispatched when HTTP request is done.
     *
     * @Event
     */
    const COMPLETE = 'request.complete';
}
 