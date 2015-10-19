<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;

use ArturDoruch\Http\Redirect;
use ArturDoruch\Http\Util\HtmlUtils;

class Response implements \JsonSerializable
{
    use ResponseTrait;
    use MessageTrait;

    /**
     * @var string
     */
    private $requestUrl;

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
    private $redirects = array();

    /**
     * @var array
     */
    private $curlInfo = array();

    /**
     * @var string
     */
    private $body;

    public function __clone()
    {
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
     * Request url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @param string $url Request url.
     *
     * @return $this
     */
    public function setRequestUrl($url)
    {
        $this->requestUrl = $url;

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
     * Converts Response into json.
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
     * Converts Response into associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    /**
     * Parses response body where content type is type of 'application/json' into associative array.
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
     * @param bool $removeHead  Removes <head> tag and leaves only <body> content.
     * @param bool $removeNoise Removes comments and unwanted tags like:
     *                          script, noscript, iframe, meta, input.
     * @param bool $removeImages
     * @param bool $removeWhiteSpaces
     *
     * @return $this;
     */
    public function cleanHtmlBody(MessageBodyCleanerInterface $cleaner = null, $removeHead = true, $removeNoise = true, $removeImages = true, $removeWhiteSpaces = true)
    {
        if (strpos($this->getContentType(), 'text/html') === 0) {
            $body = $this->getBody();

            HtmlUtils::removeBlankLines($body);

            if ($removeWhiteSpaces === true) {
                HtmlUtils::removeWhiteSpace($body);
                $body = str_replace("\n", '', $body);
            }

            if ($removeNoise === true) {
                HtmlUtils::removeNoise($body, $removeImages);
            }

            if ($cleaner) {
                $this->setBody($body);
                $body = $cleaner->cleanHtml($this);
            }

            if ($removeHead === true && preg_match('/<\s*body[^>]*>(.*)<\/body>/si', $body, $matches)) {
                $body = $matches[1];
            }

            $this->setBody($body);
        }

        return $this;
    }

    /**
     * Determines which property in Response object should be available
     * in serialized to json object "toJson" or array "toArray".
     * As default are exposed: headers, httpCode, body.
     *
     * @param array $properties
     *
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

        if (!$properties) {
            $data = array();

            foreach ($propertiesToExpose as $property) {
                if (property_exists($this, $property)) {
                    $data[$property] = $this->$property;
                }
            }

            return $data;
        } else {
            $propertiesToExpose = $properties;

            return null;
        }
    }

}
 