<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Response;

use ArturDoruch\Http\Util\HtmlUtils;

class ResponseCollection
{
    /**
     * @var Response[]
     */
    private $collection = array();

    /**
     * @var bool Describes if a collection array of Resource's was sorted.
     */
    private $isSorted = false;

    /**
     * @var int Counts collection array length.
     */
    private $count = 0;

    /**
     * @var bool Is multi or single Request collection.
     */
    private $multi;

    public function __construct($multi)
    {
        $this->multi = $multi;
    }

    /**
     * Adds a new cURL response into collection.
     *
     * @param Response $response
     * @param int      $resourceId cURL resource id.
     */
    public function add(Response $response, $resourceId)
    {
        $this->collection[$resourceId] = $response;
        $this->count++;
        $this->isSorted = false;
    }

    /**
     * Gets all Response's collection. If was making single request,
     * then will be returned single response object. If was making multi request,
     * then will be returned array of Response objects.
     *
     * @return Response|Response[]
     */
    public function get()
    {
        $this->sortCollection();

        return $this->multi === true ? $this->collection : $this->collection[0];
    }

    /**
     * Converts Resource's collection into json.
     *
     * @param bool $prettyPrint
     * @return string
     */
    public function toJson($prettyPrint = false)
    {
        return json_encode($this->get(), ($prettyPrint === true ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * Converts Resource's collection into associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    /**
     * If response content type is a type of "application/json"
     * then response body will be parsed into associative array.
     *
     * @return $this;
     */
    public function parseJsonBody()
    {
        $this->sortCollection();

        for ($i = 0; $i < $this->count; $i++) {
            $response = $this->collection[$i];
            if (preg_match('/^application\/.*json/i', $response->getContentType())) {
                $response->setBody( json_decode($response->getBody(), true) );
            }
        }

        return $this;
    }

    /**
     * Cleans Response body where content type is type of 'text/html'.
     * Extrude from html code only body tag content. Removes whitespaces and tags which type of is:
     * script, noscript, image, iframe, img, meta, input.
     *
     * @param ResponseBodyInterface $responseBody Provides custom ways to clearing HTML.
     * @param bool $removeHead  Removes <head> tag and leaves only <body> content.
     * @param bool $removeNoise Removes comments and unwanted tags like:
     *                          script, noscript, image, iframe, img, meta, input.
     *
     * @param bool $removeImages
     * @param bool $removeWhiteSpaces
     *
     * @return $this;
     */
    public function cleanHtmlBody(ResponseBodyInterface $responseBody = null, $removeHead = true, $removeNoise = true, $removeImages = true, $removeWhiteSpaces = true)
    {
        $this->sortCollection();

        // 'application/octet-stream' // Nfo content type.
        for ($i = 0; $i < $this->count; $i++) {
            $response = $this->collection[$i];
            if (strpos($response->getContentType(), 'text/html') === 0) {
                $body = $response->getBody();

                if ($removeWhiteSpaces === true) {
                    HtmlUtils::removeWhiteSpace($body);
                }

                if ($removeNoise === true) {
                    HtmlUtils::removeNoise($body, $removeImages);
                }

                if ($responseBody) {
                    $response->setBody($body);
                    $body = $responseBody->clean($response);
                }

                if ($removeHead === true) {
                    if (preg_match('/<\s*body[^>]*>(.*)<\/body>/si', $body, $matches)) {
                        $body = $matches[1];
                    }
                }

                $response->setBody($body);
            }
        }

        return $this;
    }

    /**
     * Determines which property in Response object should be available
     * in serialized to json object "toJson" or array "toArray".
     * As default are exposed these properties: headers, httpCode, body.
     *
     * @param array $properties
     * @return $this;
     */
    public function expose(array $properties)
    {
        $this->setExpose($properties);

        return $this;
    }

    /**
     * Sets to all property in Response object as available
     * in serialized to json object "toJson" or array "toArray".
     *
     * @return $this;
     */
    public function exposeAll()
    {
        $this->setExpose(array(), true);

        return $this;
    }

    private function setExpose(array $properties, $all = false)
    {
        $this->sortCollection();

        if ($all === true && $this->count > 0) {
            $reflection = new \ReflectionClass($this->collection[0]);
            $allProperties = $reflection->getProperties();
            foreach ($allProperties as $property) {
                $properties[] = $property->getName();
            }
        }

        for ($i = 0; $i < $this->count; $i++) {
            $this->collection[$i]->jsonSerialize($properties);
        }
    }

    private function sortCollection()
    {
        if (!$this->isSorted) {
            ksort($this->collection, SORT_NUMERIC);
            $this->collection = array_values($this->collection);
            $this->isSorted = true;
        }
    }

}
 