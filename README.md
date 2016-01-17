# Http

HTTP client for making http requests in enjoyable way.

## Installation
Via composer just run command
```
composer require "arturdoruch/http"
```

## Usage

### Basic usage

Making http request is pretty straightforward.

```php
use ArturDoruch\Http\Client;

$client = new Client();
$response = $client->get('http://httpbin.org/get');

// Gets response status code
$statusCode = $response->getStatusCode();

// Gets response body
$body = $response->getBody();

// Displays response raw headers and body
echo $response;

// Displays response headers
foreach ($response->getHeaders() as $header => $value) {
    echo sprintf("%s: %s\n", $header, $value);
}
```

### Create a client

```php
use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Client;

// Set curl options, which will be used in all http requests.
$curlOptions = array(
    'followlocation' => false,
    'timeout' => 120
);

// Enabled or disabled throwing RequestException, when request is complete 
// (when "complete" event is fired) and status code is: 0, 4xx or 5xx.
$throwExceptions = true;

// Set file where all http session cookies should be stored.
$filename = 'path/to/cookies.txt';
$cookieFile = new CookieFile($filename);

$client = new Client($curlOptions, $throwExceptions, $cookieFile);
```

### Sending requests

You can send requests with dedicated methods,
```php
$response = $client->get('http://httpbin.org/get');
$response = $client->post('http://httpbin.org/post');
$response = $client->patch('http://httpbin.org/patch');
$response = $client->put('http://httpbin.org/put');
$response = $client->delete('http://httpbin.org/delete');
```

or create Request object, and pass it into request() method.  
```php
use ArturDoruch\Http\Request;

$request = new Request('DELETE', 'http://httpbin.org/delete');
$response = $client->request($request);
```

#### Multi (parallel) requests
```php
$urls = array(
    // The list of urls to requested
);
$responses = $client->multiRequest($urls);

foreach ($responses as $response) {
    var_dump($response->getBody());
}
```

### Request options

Request options allows to set request body, headers, cookie, which will be send with http request.
Those options can be passed into Client::get(), Client::post(), etc. methods as third argument,
or into Client::createRequest() as fourth argument.

<a name="#cookie"></a>
####<i>cookie</i>

<b>type</b>: string

Cookie string must following with this <a href="http://curl.haxx.se/rfc/cookie_spec.html">specification</a>.
```php
$client->get('/get', [], [
    'cookie' => 'NAME=VALUE; expires=DATE; path=PATH; domain=DOMAIN_NAME; secure'
]);
```

<a name="#headers"></a>
####<i>headers</i>

<b>type</b>: array

```php
$client->get('/get', [], [
    'headers' => [
        'User-Agent' => 'testing/1.0',
        'Accept'     => 'application/json',
        'X-Foo'      => ['Bar', 'Baz']
    ]
]);
```

<a name="#body"></a>
####<i>body</i>

<b>type</b>: string|resource

Sets request body as plain text.

```php
// Send body as plain text taken from resource.
$resource = fopen('http://httpbin.org', 'r');
$client->post('/post', [], ['body' => $resource]);

// Send plain text.
$client->post('/post', [], ['body' => 'Raw data']);
```

<a name="#json"></a>
####<i>json</i>

<b>type</b>: array

Send json
```php
$client->put('/put', [], [
    'json' => [
        'foo' => 'bar',
        'key' => 'value'
    ]
]);
```

<a name="#files"></a>
####<i>files</i>

<b>type</b>: PostFile[]

Send files.

```php
use ArturDoruch\Http\Post\PostFile;

$client->post('/post', [], [
    'files' => [
        new PostFile('form_name', 'path/to/file', 'optional_custom_filename'),
        new PostFile('foo', __DIR__ . '/foo.txt'),
    ]
]);
```

### Http Request events listeners

While HTTP request is making, are called two events:

 * BEFORE - event called just before send HTTP request,
 * COMPLETE - event called when HTTP request is done.
  
To add listeners for those events use Client::addListener() method. 
The registered listener function depends on event to listen for, receive argument:

 * ArturDoruch\Http\Event\BeforeEvent - for BEFORE event,
 * ArturDoruch\Http\Event\CompleteEvent - for COMPLETE event.

```php
use App\EventListener\HttpListener;
use ArturDoruch\Http\Event\BeforeEvent;
use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Event\RequestEvents;

// Add listener to BEFORE event as anonymous function.
$client->addListener(RequestEvents::BEFORE, function (BeforeEvent $event) {
        $request = $event->getRequest();
    });
    
// Add listener to BEFORE event as method class.
$client->addListener(RequestEvents::BEFORE, array(new HttpListener(), 'onBefore'));
    
// Add listener to COMPLETE event as method class.
$client->addListener(RequestEvents::COMPLETE, array(new HttpListener(), 'onComplete'));
```

Example of HTTP events listener class.
```php
namespace App\EventListener;

use ArturDoruch\Http\Event\BeforeEvent;
use ArturDoruch\Http\Event\CompleteEvent;

class HttpListener
{
    /**
     * @param CompleteEvent $event Emitted event
     */
    public function onComplete(CompleteEvent $event)
    {
        $response = $event->getResponse();
        // Do some actions when HTTP request is complete.
    }
    
    /**
     * @param BeforeEvent $event Emitted event
     */
    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        // Do some actions before HTTP request is sending.
    }
}
```

### Convert Response object to array or json

In order to convert Response object to array call Response::toArray() method.
```php
$responseArray = $response->toArray();
```

In order to convert Response object to json call Response::toJson() method.
```php
$responseJson = $response->toJson();
// Use JSON_PRETTY_PRINT option to format output json
$responseJson = $response->toJson(true);
```

To determine which Response object properties should be available
in converted output value use Response::expose() method.
This method takes an argument "properties" with list of properties names to expose.
Available names are:

 * protocol
 * statusCode
 * reasonPhrase
 * headers
 * headerLines
 * body
 * contentType
 * requestUrl
 * effectiveUrl
 * errorMsg
 * errorNumber
 * curlInfo

As default are exposed properties: statusCode, headers, body.
 
```php
// Expose only the "statusCode" and "body" properties.
$response->expose(array(
        'statusCode',
        'body',
    ));    
// The array will contain only "statusCode" and "body" keys.    
$responseArray = $response->toArray();
```

To expose all Response properties use exposeAll() method.
```php    
// Expose all properties.
$response->exposeAll();    
// The array will contain all of available properties.  
$responseArray = $response->toArray();    
```
