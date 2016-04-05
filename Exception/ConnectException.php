<?php

namespace ArturDoruch\Http\Exception;

/**
 * Exception thrown when a connection cannot be established.
 * Note that no response is present for a ConnectException
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ConnectException extends RequestException {}
