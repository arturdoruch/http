<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Message\Response;
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

    /**
     * @var array
     */
    private $unusedInfoKeys = array('url', 'content_type', 'http_code', 'redirect_count');

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

        $headersSet = explode("\n\n", trim($this->headers[$resourceId]));
        $response->setHeaders($this->parseHeaders(array_pop($headersSet)));

        if (count($headersSet) > 0) {
            foreach ($headersSet as $headers) {
                $status = $this->parseStatusLine($headers);

                $redirect = new Redirect();
                $redirect
                    ->setHeaders($this->parseHeaders($headers))
                    ->setStatusCode($status['code'])
                    ->setReasonPhrase($status['reason_phrase']);

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
     * @param string $headerLines
     *
     * @return array
     */
    private function parseHeaders($headerLines)
    {
        $data = array();
        $headers = explode("\n", $headerLines);

        foreach ($headers as $header) {
            $parts = explode(': ', $header);
            $data[$parts[0]] = isset($parts[1]) ? trim($parts[1]) : null;
        }

        return $data;
    }

    /**
     * @param string $headerLines
     * @return array
     */
    private function parseStatusLine($headerLines)
    {
        $headers = explode("\n", $headerLines);

        if (!empty($headers)) {
            list($protocol, $statusCode, $reasonPhrase) = explode(' ', $headers[0]);

            return array(
                'code' => $statusCode,
                'reason_phrase' => $reasonPhrase
            );
        }

        return array();
    }

}
 