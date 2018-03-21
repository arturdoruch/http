<?php

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Message\ResponseInterface;
use ArturDoruch\Http\Redirect;
use ArturDoruch\Http\Request;
use ArturDoruch\Http\Util\ResponseUtils;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class MessageHandler
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var array cURL request options.
     */
    private $options;

    /**
     * @var HeadersBag
     */
    private $headersBag;

    /**
     * @var Stream
     */
    private $stream;

    /**
     * @param Request    $request
     * @param array      $options cURL options
     * @param Stream     $stream
     * @param HeadersBag $headersBag
     */
    public function __construct(Request $request, array $options, Stream $stream, HeadersBag $headersBag)
    {
        $this->request = $request;
        $this->options = $options;
        $this->headersBag = $headersBag;
        $this->stream = $stream;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return array cURL options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param resource $handler cURL handler
     *
     * @return Response
     */
    public function createResponse($handler)
    {
        $info = curl_getinfo($handler);

        $response = new Response();
        $response
            ->setRequestUrl($this->request->getUrl())
            ->setEffectiveUrl($info['url'])
            ->setContentType($info['content_type'])
            ->setErrorMsg(curl_error($handler))
            ->setErrorNumber(isset($handler['result']) ? $handler['result'] : curl_errno($handler))
            ->setBody($this->stream->getContents());

        $this->stream->close();

        $headersStock = $this->headersBag->getHeadersStock();

        if ($responseHeaders = array_pop($headersStock)) {
            self::parseResponseHeaders($response, $responseHeaders);
            // Set redirects
            foreach ($headersStock as $headers) {
                self::parseResponseHeaders($redirect = new Redirect(), $headers);
                $response->addRedirect($redirect);
            }
        } else {
            $response
                ->setStatusCode($info['http_code'])
                ->setReasonPhrase(ResponseUtils::getReasonPhrase($info['http_code']));
        }

        self::compileCurlInfo($info);
        $response->setCurlInfo($info);

        return $response;
    }

    /**
     * Parses response headers and fills Response object with parsed values.
     *
     * @param ResponseInterface $response
     * @param array             $headers
     */
    private static function parseResponseHeaders(ResponseInterface $response, array $headers)
    {
        // Parse header status line
        $parts = explode(' ', array_shift($headers), 3);

        $response
            ->setProtocol($parts[0])
            ->setStatusCode($parts[1])
            ->setReasonPhrase(isset($parts[2]) ? $parts[2] : ResponseUtils::getReasonPhrase($parts[1]));

        // Set headers
        foreach ($headers as $header) {
            $parts = explode(': ', $header);
            $response->addHeader($parts[0], (isset($parts[1]) ? $parts[1] : null));
        }
    }

    private static function compileCurlInfo(array &$info)
    {
        static $keysToUnset = ['url', 'content_type', 'http_code', 'redirect_count'];

        foreach ($keysToUnset as $key) {
            unset($info[$key]);
        }
    }
}
