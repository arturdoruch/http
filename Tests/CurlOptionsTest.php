<?php

namespace ArturDoruch\Http\Tests;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Request;

/**
 * Testing applying curl options
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class CurlOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyOptions()
    {
        $client = new Client([
            CURLOPT_USERAGENT => 'default-user-agent',
        ]);

        $options = $client->getDefaultCurlOptions();
        $this->assertEquals('default-user-agent', $options[10018]);

        $response = $client->get('https://httpbin.org/get', [], [
            /*'headers' => [
                'User-Agent' => 'gg',
            ]*/
        ], [
            CURLOPT_USERAGENT => 'request-user-agent',
        ]);

        $options = $client->getCurlOptions();
        $this->assertEquals('request-user-agent', $options[10018]);
    }
}
 