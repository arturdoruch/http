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

// Enabled or disabled throwing RequestException, when request is complete (when "complete" event is fired)
// and status code is: 0, 4xx or 5xx.
$throwExceptions = true;

// Set file, where all http session cookies should be stored.
$filename = 'cookies.txt';
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
$response = $client->post('http://httpbin.org/post');
```

Or create Request object before, and pass it into request() method.  
```php
use ArturDoruch\Http\Request;

$request = new Request('DELETE', 'http://httpbin.org/delete');
$response = $client->request($request);
```
