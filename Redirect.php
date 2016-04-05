<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\ResponseInterface;
use ArturDoruch\Http\Message\ResponseTrait;

/**
 * Http request redirect
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Redirect implements ResponseInterface
{
    use ResponseTrait;
    use MessageTrait;
}
 