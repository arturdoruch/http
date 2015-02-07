<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Response;


class Response implements \JsonSerializable
{
    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var int
     */
    private $httpCode;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $url;

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
    private $redirects;

    public function __clone()
    {
        
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
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
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
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets single header fields by specified name.
     *
     * @param string $name
     * @return mixed
     */
    public function getHeader($name)
    {
        $name = ucfirst($name);

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @param int $httpCode
     * @return $this
     */
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;

        return $this;
    }

    /**
     * @return array
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @param array $redirects
     * @return $this
     */
    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

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
     * {@inheritdoc}
     */
    public function jsonSerialize(array $properties = null)
    {
        static $propertiesToExpose = array(
            'headers',
            'httpCode',
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
 