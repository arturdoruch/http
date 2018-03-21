# Http

HTTP client for making HTTP requests in enjoyable way.

## Installation
Via composer
```
composer require "arturdoruch/http"
```

## Usage

### Basic usage

Making HTTP request is pretty straightforward.

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

### Creating a client

```php
use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Client;

// Set curl options, which will be used in all http requests.
$curlOptions = [
    'followlocation' => false,
    'timeout' => 120
];

// Enabled or disabled throwing RequestException, when request is complete and response status code is 4xx, 5xx or 0.
$throwExceptions = true;

// Set file where all http session cookies should be stored.
$cookieFile = new CookieFile('path/to/cookies.txt');

$client = new Client($curlOptions, $throwExceptions, $cookieFile);
```

### Sending requests

You can send requests with dedicated methods
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

### Sending multi (parallel) requests
```php
$requests = [
    // The list of ArturDoruch\Http\Request objects or urls to send. 
];
$responses = $client->multiRequest($requests);

foreach ($responses as $response) {
    var_dump($response->getBody());
}
```

### Sending POST, PATCH, PUT requests with form data

```php
// Form data
$parameters = [
    'name' => 'value',
    'choices' => [1, 2, 3]
];

$response = $client->post('http://httpbin.org/post', $parameters);

$request = new Request('POST', 'http://httpbin.org/post', $parameters);
$response = $client->request($request);
```

### Request options

Request options allows to set request: body, headers, cookies, ect. to send with HTTP request.
Those options can be passed into Client::get(), Client::post(), etc. methods as third argument,
or into Client::createRequest() as fourth argument.

<a name="#cookie"></a>
#### <i>cookie</i>

<b>type</b>: string

Cookie string must following with this <a href="http://curl.haxx.se/rfc/cookie_spec.html">specification</a>.
```php
$client->get('/get', [], [
    'cookie' => 'NAME=VALUE; expires=DATE; path=PATH; domain=DOMAIN_NAME; secure'
]);
```

<a name="#headers"></a>
#### <i>headers</i>

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
#### <i>body</i>

<b>type</b>: string|resource

Send request body as plain text.

```php
// Send body as plain text taken from resource.
$resource = fopen('http://httpbin.org', 'r');
$client->post('/post', [], ['body' => $resource]);

// Send plain text.
$client->post('/post', [], ['body' => 'Raw data']);
```

<a name="#json"></a>
#### <i>json</i>

<b>type</b>: array

Send json.

```php
$client->put('/put', [], [
    'json' => [
        'foo' => 'bar',
        'key' => 'value'
    ]
]);
```

<a name="#files"></a>
#### <i>files</i>

<b>type</b>: ArturDoruch\Http\Post\PostFile[]

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

### HTTP request events

While HTTP request is making, are called two events:

 * request.before - called just before send HTTP request
 * request.complete - called when HTTP request is done.
  
To add listeners for those events use Client::addListener() method. 
The registered listeners depends on event to listen for, receive argument:

 * ArturDoruch\Http\Event\BeforeEvent - for request.before event,
 * ArturDoruch\Http\Event\CompleteEvent - for request.complete event.

```php
use App\EventListener\HttpRequestListener;
use ArturDoruch\Http\Event\BeforeEvent;
use ArturDoruch\Http\Event\CompleteEvent;
use ArturDoruch\Http\Event\RequestEvents;

// Add listener to request.before event as anonymous function.
$client->addListener(RequestEvents::BEFORE, function (BeforeEvent $event) {
    $request = $event->getRequest();
});

// Add listener to request.before event as method class.
$client->addListener(RequestEvents::BEFORE, [new HttpRequestListener(), 'onBefore']);
    
// Add listener to request.complete event as method class.
$client->addListener(RequestEvents::COMPLETE, [new HttpRequestListener(), 'onComplete']);
```

Example of HttpRequestListener class.

```php
namespace App\EventListener;

use ArturDoruch\Http\Event\BeforeEvent;
use ArturDoruch\Http\Event\CompleteEvent;

class HttpRequestListener
{    
    /**
     * @param BeforeEvent $event
     */
    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        // Do some actions before HTTP request is sending.
    }

    /**
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        $response = $event->getResponse();
        // Do some actions when HTTP request is complete.
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
$response->expose([
    'statusCode',
    'body',
]);    
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

## Tips

* Get request headers
```php
$client = new Client([CURLINFO_HEADER_OUT => true]);
    
$response = $client->get('http://httpbin.org/get');
$curlInfo = $response->getCurlInfo()

$requestHeaders = $curlInfo['request_header'];
```
 
