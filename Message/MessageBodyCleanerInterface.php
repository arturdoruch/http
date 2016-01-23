<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;

/**
 * Provides a custom way to clearing response body.
 *
 * @deprecated Will be removed in version 4.
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
 