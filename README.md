# Http

HTTP client for sending HTTP requests.

## Installation
```
composer require "arturdoruch/http"
```

## Usage

### Basic usage

```php
use ArturDoruch\Http\Client;

$client = new Client();
// Send GET request.
$response = $client->get('http://httpbin.org/get');

// Get response status code.
$statusCode = $response->getStatusCode();

// Get response body.
$body = $response->getBody();

// Display response raw headers and body.
echo $response;

// Display response headers.
foreach ($response->getHeaders() as $name => $value) {
    echo sprintf("%s: %s\n", $name, $value);
}
```

### Creating a client

```php
use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Client;

// Set the cURL options, which will be used for send every HTTP request.
$curlOptions = [
    'followlocation' => false,
    'timeout' => 120
];

// Enabled or disabled throwing RequestException, when request is complete and response status code is 4xx, 5xx or 0.
$throwExceptions = true;

// Set file where all HTTP session cookies should be stored.
$cookieFile = new CookieFile('path/to/cookies.txt');

$client = new Client($curlOptions, $throwExceptions, $cookieFile);
```

### Sending requests

Request can be send with dedicated methods:
```php
$response = $client->get('http://httpbin.org/get');
$response = $client->post('http://httpbin.org/post');
$response = $client->patch('http://httpbin.org/patch');
$response = $client->put('http://httpbin.org/put');
$response = $client->delete('http://httpbin.org/delete');
```

or by `request()` method, with prepared the `ArturDoruch\Http\Request` object.  
```php
use ArturDoruch\Http\Request;

$request = new Request('DELETE', 'http://httpbin.org/delete');
$response = $client->request($request);
```

### Sending multi (parallel) requests

```php
$requests = [
    // The list of ArturDoruch\Http\Request objects or URLs to send. 
];
$responses = $client->multiRequest($requests);

foreach ($responses as $response) {
    var_dump($response->getBody());
}
```

### Sending form data (parameters)

```php
$formData = [
    'name' => 'value',
    'choices' => [1, 2, 3]
];

$response = $client->post('http://httpbin.org/post', $formData);

$request = new Request('POST', 'http://httpbin.org/post', $formData);
$response = $client->request($request);
```

Form data can be send only with the request methods: `POST`, `PUT`, `PATCH` and `DELETE`.
For other methods form data will be used as the URL query parameters.

### Request options

Request options can be set with dedicated methods on the `ArturDoruch\Http\Request` object,
or as the third argument in the `Client::get()`, `Client::post()`, etc. methods,
or as the fourth argument in the `Client::createRequest()` method.

 * `cookie` string  
 
    Sets the cookie to send. The cookie format must conform to the [specification](http://curl.haxx.se/rfc/cookie_spec.html).
    
    ```php
    $client->get('/get', [], [
        'cookie' => 'NAME=VALUE; expires=DATE; path=PATH; domain=DOMAIN_NAME; secure'
    ]);
    ```

 * `headers` array 

    ```php
    $client->get('/get', [], [
        'headers' => [
            'User-Agent' => 'testing/1.0',
            'Accept' => 'application/json',
            'X-Foo' => ['Bar', 'Baz']
        ]
    ]);
    ```

 * `body` string|resource

    Sets the body plain text. If the `Content-Type` header is not specified then will be set to `text/plain`. 
    
    ```php
    // Send body as plain text taken from a resource.
    $resource = fopen('http://httpbin.org', 'r');
    $client->post('/post', [], ['body' => $resource]);
    
    // Send plain text.
    $client->post('/post', [], ['body' => 'Raw data']);
    ```

 * `json` array   

    Sets the body to JSON data. If the `Content-Type` header is not specified then will be set to `application/json`. 
    
    ```php
    $client->post('/post', [], [
        'json' => [
            'foo' => 'bar',
            'key' => 'value'
        ]
    ]);
    ```

 * `multipart` array

    Sets the body to a multipart form data.
    For sending file create the `ArturDoruch\Http\Message\FormFile` object and pass as the form field value.
    If the `Content-Type` header is not specified then will be set to `multipart/form-data; boundary=`. 
    
    ```php
    use ArturDoruch\Http\Message\FormFile;
    
    $client->post('/post', [], [
        'multipart' => [
            'name' => 'value',
            'categories' => [
                'animals' => ['dog', 'cat'],
            ],
            'file' => new FormFile('/path/file.txt.', 'optional-custom-filename.txt')
        ]        
    ]);
    ```

### HTTP request events

While HTTP request is sending, are called two events:

 * `request.before` - Called before sending the request.
 * `request.complete` - Called when the request is done.
  
To add listeners for those events call the `Client::addListener()` method. 
Registered listeners receives argument depend on the event to listen for. One of the:

 * `ArturDoruch\Http\Event\BeforeEvent` - for the `request.before` event
 * `ArturDoruch\Http\Event\CompleteEvent` - for the `request.complete` event

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

Example of the `HttpRequestListener` class.

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

* Get actually sent request headers.
    ```php
    $client = new Client([CURLINFO_HEADER_OUT => true]);
        
    $response = $client->get('http://httpbin.org/get');
    $curlInfo = $response->getCurlInfo()
    
    $requestHeaders = $curlInfo['request_header'];
    ``` 
