<?php

namespace ArturDoruch\Http\Message;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ResponseCollection
{
    /**
     * @var Response[]
     */
    private $responses = array();

    /**
     * Adds a new Response into collection.
     *
     * @param Response $response
     * @param int      $resourceId cURL resource id.
     */
    public function add(Response $response, $resourceId)
    {
        $this->responses[$resourceId] = $response;
    }

    /**
     * Gets collection of all responses.
     *
     * @return Response[]
     */
    public function all()
    {
        ksort($this->responses, SORT_NUMERIC);
        $this->responses = array_values($this->responses);

        return $this->responses;
    }
}
 