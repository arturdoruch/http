# Http

HTTP client for making http requests in enjoyable way.

## Installation
Via composer. Add this lines into composer.json file.
```json
{
    "require": {
        ...
        "arturdoruch/http": "~3.0"
    }
}
```

## Usage

### Basic usage

Making http request is pretty straightforward.

```php
use ArturDoruch\Http\Client;

$client = new Client();
$response = $client->get('http://httpbin.org/get');

$statusCode = $response->getStatusCode();
$body = $response->getBody();

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

You can send requests with dedicated methods.
```php
$response = $client->get('http://httpbin.org/get');
$response = $client->post('http://httpbin.org/post');
$response = $client->patch('http://httpbin.org/patch');
$response = $client->put('http://httpbin.org/put');
$response = $client->delete('http://httpbin.org/delete');
```

Or create Request object before, and pass it into request() method.  
```php
use ArturDoruch\Http\Request;

$request = new Request('DELETE', 'http://httpbin.org/delete');
$response = $client->request($request);
```

### Request options

Request options allows to set request body, headers, cookie, which will be send with http request.
Those options can be pass into ArturDoruch\Http\Client::get(), 
ArturDoruch\Http\Client::post(), etc. methods as third argument,
or into ArturDoruch\Http\Client::createRequest() as fourth argument.

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