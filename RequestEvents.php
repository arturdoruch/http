<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

/**
 * Contains events thrown while HTTP request is making.
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
 