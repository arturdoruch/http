<?php

namespace ArturDoruch\Http\Tests;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class RequestMethodTest extends TestCase
{
    /**
     * @var Client
     */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = new Client();
    }


    public function testHeadRequest()
    {
        $response = self::$client->request(new Request('HEAD', 'https://httpbin.org/get'));

        $this->assertEmpty($response->getBody());
    }


    public function testGetRequest()
    {
        $response = self::$client->get('https://httpbin.org/get');

        $this->assertNotEmpty($response->getBody());
    }


    public function testGetRequestWithQueryParameters()
    {
        $request = new Request('GET', 'https://httpbin.org/get');
        $request->setParameters($queryParameters = [
            'key' => 'value',
            'filter' => [
                'name' => 'foo',
                'category' => 'language'
            ]
        ]);

        $data = $this->sendRequest($request);

        self::assertContains('?', $url = $data['url']);

        preg_match('/^.+\?(.+)$/', $url, $matches);
        parse_str($matches[1], $parameters);

        self::assertEquals($queryParameters, $parameters);
    }


    public function testPostRequestWithFormData()
    {
        $request = new Request('POST', 'https://httpbin.org/post');
        $request->setParameters($formData = [
            'foo' => 'bar',
            'name' => 'value'
        ]);

        $data = $this->sendRequest($request);

        self::assertEquals($formData, $data['form']);
        self::assertEquals('application/x-www-form-urlencoded', $data['headers']['Content-Type']);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function sendRequest(Request $request)
    {
        $response = self::$client->request($request);

        return json_decode($response->getBody(), true);
    }
}
 