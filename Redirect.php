<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\ResponseTrait;

/**
 * Http request redirect
 */
class Redirect
{
    use ResponseTrait;
    use MessageTrait;
}
 