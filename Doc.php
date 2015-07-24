<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace Arturdoruch\Http;

use ArturDoruch\Http\Client;
use ArturDoruch\Http\Request;

class Doc
{
    public function get()
    {
        $url = '';

        // Set curl options which will be used in all http requests.
        $curlOptions = array(
            'followlocation' => false,
            'timeout' => 120
        );

        $client = new Client($curlOptions);

        // Make get request.
        $response = $client->get($url);

        // Make post request

        // Set request parameters
        $parameters = array(
            'param_1' => 'value',
            'param_2' => 'value'
        );
        // Set other request options
        $options = array(
            'headers' => array(
                'Accept: text/xml, application/json',
                'Cache-Control: max-age=0'
            ),
            'body' => 'This is body'
        );

        // Set curl options for this concrete request
        $curlOptions = array(
            'followlocation' => true,
        );

        $response = $client->post($url, $parameters, $options, $curlOptions);

        // If request required to send many parameters and options you can using method "request()"
        // and pass all required data into Request object.
        // With Request object you can set all http request parameters.
        $request = new Request();
        $request->setUrl($url)
            ->setMethod('POST')
            ->setBody('This is body')
            ->setParameters($parameters)
            ->setHeaders(array(
                'Accept: text/xml, application/json',
                'Cache-Control: max-age=0'
            ));

        $response = $client->request($request);


    }

    public function post()
    {

    }

    public function request()
    {

    }

    public function multiRequest()
    {

    }
}
 