<?php

namespace ArturDoruch\Http\Tests;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Post\PostFile;
use ArturDoruch\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class PostRequestTest extends TestCase
{
    private $filesDir = __DIR__ . '/Resources/files';
    private $baseUrl = 'https://httpbin.org';

    /**
     * @var Client
     */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = new Client();
    }


    public function testPostPlainText()
    {
        $request = new Request('POST', $this->baseUrl . '/post');
        $request->setBody('Sed ut perspiciatis unde omnis iste');

        $data = $this->sendRequest($request);

        self::assertEquals('text/plain', $data['headers']['Content-Type']);
        self::assertEquals('Sed ut perspiciatis unde omnis iste', $data['data']);
    }


    public function testPostResource()
    {
        $request = new Request('POST', $this->baseUrl . '/post');
        $request->setBody(fopen($this->filesDir . '/text.txt', 'r'));

        $data = $this->sendRequest($request);

        self::assertEquals('text/plain', $data['headers']['Content-Type']);
        self::assertStringStartsWith('Lorem ipsum dolor sit amet', $data['data']);
    }


    public function testPostJson()
    {
        $request = new Request('POST', $this->baseUrl . '/post');
        $request->setBody([
            'json' => [
                'key' => 'value'
            ]
        ]);

        $data = $this->sendRequest($request);

        self::assertEquals('application/json', $data['headers']['Content-Type']);
        self::assertArrayHasKey('key', $data['json']);
    }


    public function testPostFiles()
    {
        $request = new Request('POST', $this->baseUrl . '/post');
        $request->setBody([
            'files' => [
                new PostFile('textFile', $this->filesDir . '/text.txt', 'my-own-filename.txt'),
                new PostFile('pngFile', $this->filesDir . '/done.png'),
                new PostFile('svgFile', $this->filesDir . '/grade.svg'),
            ],
        ]);

        $data = $this->sendRequest($request);

        self::assertStringStartsWith('multipart/form-data; boundary=', $data['headers']['Content-Type']);
        self::assertCount(3, $data['files']);
        self::assertArrayHasKey('textFile', $data['files']);
    }


    public function testPostFormData()
    {
        $request = new Request('POST', $this->baseUrl . '/post');
        $request->setParameters([
            'key' => 'value',
            'filter' => [
                'name' => 'foo',
            ]
        ]);

        $data = $this->sendRequest($request);

        self::assertEquals('application/x-www-form-urlencoded', $data['headers']['Content-Type']);
        self::assertCount(2, $data['form']);
        self::assertArrayHasKey('filter[name]', $data['form']);
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
