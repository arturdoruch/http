<?php

namespace ArturDoruch\Http\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
if (is_subclass_of(EventDispatcher::class, EventDispatcherInterface::class)) {
    abstract class AbstractEvent extends \Symfony\Contracts\EventDispatcher\Event
    {
    }
} else {
    abstract class AbstractEvent extends \Symfony\Component\EventDispatcher\Event
    {
    }
}
