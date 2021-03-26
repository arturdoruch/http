<?php

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\MessageTrait;
use ArturDoruch\Http\Message\RequestBodyCompiler;
use ArturDoruch\Http\Post\PostFile;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Request
{
    use MessageTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array The form data or URL query parameters depend on the request method.
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $cookies = [];

    public function __construct($method, $url)
    {
        $this->setMethod($method);
        $this->setUrl($url);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);

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
     *
     * @return $this
     */
    public function setUrl($url)
    {
        if (!$url) {
            throw new \InvalidArgumentException('Empty request URL.');
        }

        $this->url = (string) $url;

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
     * Sets the request body.
     *
     * @param string|array $body The request body. Allowed body contents:
     *
     *  - (string) plain text
     *  - (resource) resource
     *  - (array) with one of the keys:
     *    - "json" (array|mixed)
     *      Usage:
     *          setBody([
     *              'json' => [
     *                  'key' => 'value'
     *              ]
     *          ]);
     *
     *    - "multipart" (array) (can be multidimensional) for sending multipart form data.
     *      Usage:
     *          setBody([
     *              'multipart' => [
     *                  'name' => 'value',
     *                  'categories' => [
     *                      'animals' => ['dog', 'cat'],
     *                  ],
     *                  'file' => new ArturDoruch\Http\Message\FormFile('/path/file.txt.', 'own-file-name.txt')
     *              ]
     *          ]);
     *
     *    - "files" (array) with instances of the "ArturDoruch\Http\Post\PostFile" class for sending the files.
     *      Usage:
     *          setBody(['files' => [
     *              new PostFile($name, $file[, $filename = null])
     *          ]]);
     *
     *      NOTE: The option is deprecated. Use "multipart" instead.
     *
     * @param string $contentType The body content type. If not specified it will be determined based on the body.
     *                            This parameter overrides the "Content-Type" header.
     *
     * @return $this
     */
    public function setBody($body, $contentType = '')
    {
        // todo Remove in version 4.
        if (is_array($body) && isset($body['files'])) {
            if (is_array($body['files'])) {
                $body['multipart'] = [];
                foreach ($body['files'] as $i => $file) {
                    $body['multipart'][$file instanceof PostFile ? $file->getName() : $i] = $file;
                }
            }

            unset($body['files']);
        }

        $this->body = RequestBodyCompiler::compile($body, $bodyContentType);

        if ($contentType) {
            $this->addHeader('Content-Type', $contentType);
        } elseif (!$this->hasHeader('Content-Type')) {
            $this->addHeader('Content-Type', $bodyContentType);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Removes all set parameters.
     */
    public function clearParameters()
    {
        $this->parameters = [];
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     *
     * @return $this
     */
    public function setCookies($cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param string $cookie
     *
     * @return $this
     */
    public function addCookie($cookie)
    {
        $this->cookies[] = trim($cookie);

        return $this;
    }
}
 