<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Event\EventManager;
use ArturDoruch\Http\Message\Response;
use ArturDoruch\Http\Message\ResponseCollection;
use ArturDoruch\Http\Request;
use ArturDoruch\Http\Util\ResponseUtils;


class ResourceHandler
{
    /**
     * @var ResponseCollection
     */
    private $responseCollection;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var CompleteEvent
     */
    private $completeEvent;

    /**
     * @var bool
     */
    private $multiRequest;

    /**
     * @var array
     */
    private $unusedInfoKeys = array('url', 'content_type', 'http_code', 'redirect_count');

    public function __construct(EventManager $eventManager)
    {
        $this->response = new Response();

        $this->completeEvent = new CompleteEvent();
        $this->eventManager = $eventManager;
    }

    /**
     * Creates new instance of ResponseCollection.
     *
     * @var bool $multi Is multi or single Request collection.
     */
    public function setCollection($multi = false)
    {
        $this->multiRequest = $multi;
        $this->responseCollection = new ResponseCollection();
    }

    /**
     * @return ResponseCollection
     */
    public function getResponseCollection()
    {
        return $this->responseCollection;
    }

    /**
     * @param resource $handle cURL response resource.
     * @param Request  $request
     * @param Client
     */
    public function handle($handle, Request $request, $client)
    {
        if ($this->responseCollection === null) {
            throw new \InvalidArgumentException(
                'Class "ArturDoruch\Http\Response\ResponseCollection" was not instantiated.'
            );
        }

        $url = $request->getUrl();
        if (empty($url)) {
            throw new \InvalidArgumentException('Request url is empty!');
        }

        if (isset($handle['handle'])) {
            // Multi request
            $errorNo = $handle['result'];
            $handle = $handle['handle'];
        } else {
            // Single request
            $errorNo = curl_errno($handle);
        }

        $response = $this->parseResponse($handle, $url, $errorNo);

        // Dispatch event
        $this->completeEvent->setMultiRequest($this->multiRequest);
        $this->completeEvent->setData($request, $response, $client);
        $this->eventManager->dispatch('complete', $this->completeEvent);

        $this->responseCollection->add($response, $this->getResourceId($handle));
    }

    /**
     * @param resource $handle cURL response resource.
     * @return int
     */
    public function getResourceId($handle)
    {
        return (int) filter_var($handle, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Parses cURL response data. If cURL options CURLOPT_HEADER was set on true,
     * then also will be set headers information.
     *
     * @param resource $handle  cURL resource.
     * @param string   $url     Original target request url.
     * @param int      $errorNo Error number
     *
     * @return Response
     */
    private function parseResponse($handle, $url, $errorNo)
    {
        $info = curl_getinfo($handle);
        $content = curl_multi_getcontent($handle);
        $reasonPhrase = ResponseUtils::getReasonPhrase($info['http_code']);

        $response = clone $this->response;
        $response
            ->setStatusCode($info['http_code'])
            ->setReasonPhrase($reasonPhrase)
            ->setRequestUrl($url)
            ->setEffectiveUrl($info['url'])
            ->setContentType($info['content_type'])
            ->setErrorMsg(curl_error($handle))
            ->setErrorNumber($errorNo)
            ->setRedirects(array(
                    'count' => $info['redirect_count'],
                    'headers' => array()
                ));

        $body = trim(substr($content, $info['header_size']));
        $headerSets = explode("\r\n\r\n", trim(substr($content, 0, $info['header_size'])));

        $response->setBody($body);
        $response->setHeaders( $this->parseHeaders(array_pop($headerSets)) );

        if (count($headerSets) > 0) {
            $redirects = $response->getRedirects();
            foreach ($headerSets as $headers) {
                $redirects['headers'][] = $this->parseHeaders($headers);
            }
            $response->setRedirects($redirects);
        }

        foreach ($this->unusedInfoKeys as $key) {
            unset($info[$key]);
        }

        $response->setCurlInfo($info);

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

}
 