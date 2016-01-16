<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Message\ResponseInterface;
use ArturDoruch\Http\Util\ResponseUtils;

class RequestHandler
{
    /**
     * @var resource cURL resource
     */
    private $handle;

    /**
     * @var array Response headers
     */
    private $headers = array();

    /**
     * @var array cURL options
     */
    private $options = array();

    /**
     * @var Request
     */
    private $request;

    /*
     * @var array
     */
    //private $unusedInfoKeys = array('url', 'content_type', 'http_code', 'redirect_count');

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param int $resourceId
     * @param string $header
     * @return $this
     */
    public function addHeader($resourceId, $header)
    {
        if (!isset($this->headers[$resourceId])) {
            $this->headers[$resourceId] = '';
        }
        $this->headers[$resourceId] .= $header . "\n";

        return $this;
    }

    /**
     * @param array $options cURL options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return resource
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
     * @param resource $ch cURL handler
     *
     * @return Response
     */
    public function createResponse($ch)
    {
        if (!$this->request->getUrl()) {
            throw new \InvalidArgumentException('Request url is empty.');
        }

        $resourceId = (int) $ch;
        $info = curl_getinfo($ch);
        $reasonPhrase = ResponseUtils::getReasonPhrase($info['http_code']);
        $errorNo = isset($ch['result']) ? $ch['result'] : curl_errno($ch);

        $response = new Response();
        $response
            ->setStatusCode($info['http_code'])
            ->setReasonPhrase($reasonPhrase)
            ->setRequestUrl($this->request->getUrl())
            ->setEffectiveUrl($info['url'])
            ->setContentType($info['content_type'])
            ->setErrorMsg(curl_error($ch))
            ->setErrorNumber($errorNo)
            ->setBody(curl_multi_getcontent($ch));

        if (isset($this->headers[$resourceId])) {
            $headersSet = explode("\n\n", trim($this->headers[$resourceId]));

            $this->parseResponseHeaders($response, array_pop($headersSet));
            $response
                ->setStatusCode($info['http_code'])
                ->setReasonPhrase($reasonPhrase);

            // Set redirects
            foreach ($headersSet as $headers) {
                $redirect = new Redirect();
                $this->parseResponseHeaders($redirect, $headers);

                $response->addRedirect($redirect);
            }
        }

        /*foreach ($this->unusedInfoKeys as $key) {
            unset($info[$key]);
        }

        $response->setCurlInfo($info);*/

        return $response;
    }

    /**
     * Parses response headers and fills Response object with parsed values.
     *
     * @param ResponseInterface $response
     * @param string            $headerLines
     */
    private function parseResponseHeaders(ResponseInterface $response, $headerLines)
    {
        $headerLines = explode("\n", $headerLines);
        $statusLine = array_shift($headerLines);

        // Parse header status line
        list($protocol, $statusCode, $reasonPhrase) = explode(' ', $statusLine);
        $response
            ->setProtocol($protocol)
            ->setStatusCode($statusCode)
            ->setReasonPhrase($reasonPhrase);

        // Set headers
        foreach ($headerLines as $headerLine) {
            $parts = explode(': ', $headerLine);
            $response->addHeader(
                $parts[0],
                (isset($parts[1]) ? $parts[1] : null)
            );
        }
    }

}
 