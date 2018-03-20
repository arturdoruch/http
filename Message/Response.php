<?php

namespace ArturDoruch\Http\Message;

use ArturDoruch\Http\Redirect;
use ArturDoruch\Http\Util\HtmlUtils;
use ArturDoruch\Http\Request;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Response implements \JsonSerializable, ResponseInterface
{
    use ResponseTrait;
    use MessageTrait;

    /**
     * @var string
     */
    private $effectiveUrl;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string
     */
    private $errorMsg;

    /**
     * @var int
     */
    private $errorNumber;

    /**
     * @var array
     */
    private $redirects = [];

    /**
     * @var array
     */
    private $curlInfo = [];

    /**
     * @var string
     */
    private $body;

    /**
     * @var Request
     */
    private $request;

    public function __clone()
    {
    }

    /**
     * Gets response raw headers and body
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRawHeaders() . $this->body;
    }

    /**
     * @return string
     */
    public function getEffectiveUrl()
    {
        return $this->effectiveUrl;
    }

    /**
     * @param string $effectiveUrl
     * @return $this
     */
    public function setEffectiveUrl($effectiveUrl)
    {
        $this->effectiveUrl = $effectiveUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     * @return $this
     */
    public function setErrorMsg($errorMsg)
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrorNumber()
    {
        return $this->errorNumber;
    }

    /**
     * @param int $errorNumber
     * @return $this
     */
    public function setErrorNumber($errorNumber)
    {
        $this->errorNumber = $errorNumber;

        return $this;
    }

    /**
     * @return Redirect[]
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @param Redirect $redirect
     *
     * @return $this
     */
    public function addRedirect(Redirect $redirect)
    {
        $this->redirects[] = $redirect;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Gets cURL response information.
     *
     * @return array
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @param array $curlInfo
     *
     * @return $this
     */
    public function setCurlInfo(array $curlInfo)
    {
        $this->curlInfo = $curlInfo;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets the raw headers as a string
     *
     * @return string
     */
    public function getRawHeaders()
    {
        $rawHeaders = $this->getProtocol() . ' ' . $this->statusCode . ' ' . $this->reasonPhrase . "\r\n";

        foreach ($this->getHeaders() as $name => $value) {
            $rawHeaders .= $name . ": " . $value . "\r\n";
        }

        return $rawHeaders . "\r\n";
    }

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->request->getUrl();
    }

    /**
     * Converts Response object into json.
     * To determine which object properties should be available in returned json
     * use method expose() or exposeAll() to expose all properties.
     *
     * @param bool $prettyPrint
     *
     * @return string
     */
    public function toJson($prettyPrint = false)
    {
        return json_encode($this, ($prettyPrint === true ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * Converts Response object into associative array.
     * To determine which object properties should be available in returned array
     * use method expose() or exposeAll() to expose all properties.
     *
     * @return array
     */
    public function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    /**
     * Parses response body with content type 'application/json' into associative array.
     *
     * @return $this
     */
    public function parseJsonBody()
    {
        if (preg_match('/^application\/.*json/i', $this->getContentType())) {
            $this->setBody( json_decode($this->getBody(), true) );
        }

        return $this;
    }

    /**
     * Cleans Response body where content type is type of 'text/html'.
     *
     * @param MessageBodyCleanerInterface $cleaner Provides custom ways to clearing HTML.
     * @param bool $removeHead  Removes <head> tag and leaves only <body> tag.
     * @param bool $removeNoise Removes comments and unwanted tags like:
     *                          script, noscript, iframe, meta, input.
     * @param bool $removeImages
     * @param bool $minify
     *
     * @return $this;
     *
     * @deprecated To be removed in 4.0. Use tool ArturDoruch\HtmlCleaner and class
     * ArturDoruch\HttpResponseHtmlCleaner instead @link https://github.com/arturdoruch/HtmlCleaner
     */
    public function cleanHtmlBody(MessageBodyCleanerInterface $cleaner = null, $removeHead = true, $removeNoise = true, $removeImages = true, $minify = true)
    {
        if (strpos($this->getContentType(), 'text/html') === 0) {
            $body = $this->getBody();

            HtmlUtils::removeEmptyLines($body);

            $elements = array();
            if ($removeHead) {
                $elements[] = 'head';
            }
            if ($removeNoise) {
                $elements[] = 'script';
                $elements[] = 'input_meta';
            }
            if ($removeImages) {
                $elements[] = 'image';
            }

            if (!empty($elements)) {
                HtmlUtils::removeNoise($body, $elements, false);
            }

            if ($minify === true) {
                HtmlUtils::minify($body);
            }

            if ($cleaner) {
                $this->setBody($body);
                $body = $cleaner->cleanHtml($this);
            }

            $this->setBody($body);
        }

        return $this;
    }

    /**
     * Determines which Response object properties should be available
     * in value returned by methods Response::toJson() or Response::toArray().
     *
     * @param array $properties The Response object properties names. One of:
     * protocol, statusCode, reasonPhrase, headers, headerLines, contentType,
     * body, requestUrl, effectiveUrl, errorMsg, errorNumber, curlInfo.
     *
     * @return $this;
     */
    public function expose(array $properties)
    {
        $this->setExpose($properties);

        return $this;
    }

    /**
     * Sets all Response object properties (except "redirects") to available
     * in value returned by methods Response::toJson() or Response::toArray().
     *
     * @return $this;
     */
    public function exposeAll()
    {
        $this->setExpose(array(), true);

        return $this;
    }

    /**
     * @param array $properties
     * @param bool  $all
     */
    private function setExpose(array $properties, $all = false)
    {
        if ($all === true) {
            $reflection = new \ReflectionClass($this);
            $allProperties = $reflection->getProperties();
            foreach ($allProperties as $property) {
                $properties[] = $property->getName();
            }
        }

        $this->jsonSerialize($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(array $properties = null)
    {
        static $propertiesToExpose = array(
            'headers',
            'statusCode',
            'body'
        );
        static $excludedProperties = array(
            'redirects' => true
        );

        if ($properties) {
            $propertiesToExpose = $properties;

            return null;
        }

        $data = array();

        foreach ($propertiesToExpose as $property) {
            if (property_exists($this, $property) && !isset($excludedProperties[$property])) {
                $data[$property] = $this->$property;
            }
        }

        return $data;
    }
}
 