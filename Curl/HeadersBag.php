<?php

namespace ArturDoruch\Http\Curl;

/**
 * Holds the cURL responses headers.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class HeadersBag
{
    /**
     * @var array Responses headers stock.
     */
    private $headersStock = [];

    /**
     * @var array Response headers
     */
    private $headers = [];

    /**
     * @param string $header
     */
    public function add($header)
    {
        if ($header !== '') {
            $this->headers[] = $header;

            return;
        }

        // Header is empty === the last response header.
        $this->headersStock[] = $this->headers;
        $this->headers = [];
    }

    /**
     * @return array
     */
    public function getHeadersStock()
    {
        return $this->headersStock;
    }
}
