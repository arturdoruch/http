<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\ResponseInterface;
use ArturDoruch\Http\Message\ResponseTrait;

/**
 * Http request redirect
 */
class Redirect implements ResponseInterface
{
    use ResponseTrait;
    use MessageTrait;
}
 