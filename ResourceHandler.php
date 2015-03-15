<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http;

use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Event\EventManager;
use ArturDoruch\Http\Response\Response;
use ArturDoruch\Http\Response\ResponseCollection;
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
        //$this->response = new Response();
        $this->responseCollection = new ResponseCollection($multi);
    }

    /**
     * @return ResponseCollection
     */
    public function getResponseCollection()
    {
        return $this->responseCollection;
    }

    /**
     * @param resource $handle  cURL response resource.
     * @param Request  $request
     * @param Client
     *
     * @return mixed
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
        $this->completeEvent->setData($request, $response, $client);
        $this->eventManager->dispatch('complete', $this->completeEvent);

        $resourceId = preg_replace('/[^\d]/i', '', $handle);
        $this->responseCollection->add($response, $resourceId);
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

        $statusCode = $info['http_code'];
        $reasonPhrase = isset(ResponseUtils::$statusTexts[$statusCode]) ? ResponseUtils::$statusTexts[$statusCode] : '';

        $response = clone $this->response;
        $response
            ->setStatusCode($statusCode)
            ->setReasonPhrase($reasonPhrase)
            ->setBody($content)
            ->setUrl($url)
            ->setEffectiveUrl($info['url'])
            ->setContentType($info['content_type'])
            ->setErrorMsg(curl_error($handle))
            ->setErrorNumber($errorNo);

        $redirectCount = $info['redirect_count'];
        $bodyParts = explode("\r\n\r\n", $content, $redirectCount + 2);

        if (count($bodyParts) >= 2) {
            $response->setBody( array_pop($bodyParts) );
            $response->setHeaders( $this->parseHeaders(array_pop($bodyParts)) );

            if ($redirectCount > 0) {
                $redirects = array(
                    'count' => $redirectCount,
                    'headers' => array()
                );
                foreach ($bodyParts as $headers) {
                    $redirects['headers'][] = $this->parseHeaders($headers);
                }
                $response->setRedirects($redirects);
            }
        }

        return $response;
    }


    private function parseHeaders($headers)
    {
        $data = array();
        $headerParts = explode("\n", $headers);

        foreach ($headerParts as $part) {
            @list($name, $value) = explode(': ', $part);
            $data[$name] = trim($value);
        }

        return $data;
    }

}
 