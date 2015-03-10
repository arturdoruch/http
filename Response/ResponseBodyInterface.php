<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Response;

/**
 * Provides a custom way to clearing response body with HTML content type.
 */
interface ResponseBodyInterface
{
    /**
     * @param Response $response
     *
     * @return string|null
     */
    public function clean(Response $response);
}
 