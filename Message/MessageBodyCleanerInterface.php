<?php

namespace ArturDoruch\Http\Message;

/**
 * Provides a custom way to clearing response body.
 *
 * @deprecated Will be removed in version 4.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
interface MessageBodyCleanerInterface
{
    /**
     * @param Response $response
     *
     * @return string|null
     */
    public function cleanHtml(Response $response);
}
 