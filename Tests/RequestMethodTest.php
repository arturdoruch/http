<?php

namespace ArturDoruch\Http\Tests;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Request;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class RequestMethodTest extends \PHPUnit_Framework_TestCase
{
    public function testHeadRequest()
    {
        $client = new Client();
        $response = $client->request(new Request('HEAD', 'https://httpbin.org/get'));

        $this->assertEmpty($response->getBody());
    }

    public function testGetRequest()
    {
        $client = new Client();
        $response = $client->get('https://httpbin.org/get');

        $this->assertNotEmpty($response->getBody());
    }


    public function testPostFormDataRequest()
    {
        $client = new Client();
        $response = $client->post('https://httpbin.org/post', $formData = [
            'foo' => 'bar',
            'name' => 'value'
        ]);

        $data = json_decode($response->getBody(), true)['form'];

        $this->assertEquals($formData, $data);
    }


    public function testPostJsonRequest()
    {
        $client = new Client();
        $response = $client->post('https://httpbin.org/post', [], [
            'json' => $json = [
                'foo' => 'bar',
                'name' => 'value'
            ]
        ]);

        $data = json_decode($response->getBody(), true)['json'];

        $this->assertEquals($json, $data);
    }
}
 