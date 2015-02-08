# Http

HTTP client for making http requests in enjoyable way.

## Installation
Via composer.
```json
{
    "require": {
        ...
        "arturdoruch/http": "~1.0"
    },
    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/arturdoruch/Http"
        }
    ]
}
```

## Usage

### Get http client and set request options.
```php
// Set custom cURL options to all requests.
$options = array(
    'timeout' => 10000,
    'followlocation' => false
);

// Set number of maximum multi connections. Default is 8.
$connections = 8;

$client = new \ArturDoruch\Http\Client($options, $connections);

// Update number of multi connections. 
$client->setConnections(4);
```

### Make request
Make single request.
```php
$url = 'http://php.net';
$collection = $client->request($url);
```
Make multi requests.
```php
$urls = array(
    'https://twitter.com',
    'http://php.net'
);
$collection = $client->multiRequest($urls);
```

For send request with HTTP method different then GET or send some parameters 
use ```ArturDoruch\Http\RequestParameter``` class as second argument in 
```ArturDoruch\Http\Client::request``` or ```ArturDoruch\Http\Client::multiRequest``` method.
You can set parameters like: parameters, headers, cookies, method and url.
Parameter url is used only with single request.
```php
$requestParams = new \ArturDoruch\Http\RequestParameter();
$requestParams->setMethod('POST')
    ->addParameter('name', 'value');

$collection = $client->multiRequest($urls, $requestParams);
```

### Parse and clean response body

If response content-type is type of 'text/html' you can clean HTML content.    
Method ```ArturDoruch\Http\Response\ResponseCollection::cleanHtmlBody``` leaves only ```<body>``` content from HTML document,
removes all whitespaces and tags like: script, noscript, image, iframe, img, meta, input. 
```php
$collection->cleanHtmlBody();
```

You can specified your own custom class to clean HTML code.
This class must implements ```ArturDoruch\Http\Response\ResponseBodyInterface```.
To using this class pass it as second parameter in ```ArturDoruch\Http\Response\ResponseCollection::cleanHtmlBody``` method.
[See example class](Response/Body/Html.php).
```php
$htmlBody = new \ArturDoruch\Http\Response\Body\Html();
$collection->cleanHtmlBody($htmlBody);
```

If response body have a json format, you can parse it into associative array.
```php
$collection->parseJsonBody();
```

### Get request response
To get response data call method ```ArturDoruch\Http\Response\ResponseCollection:get```
which returns ```ArturDoruch\Http\Response\Response``` objects collection.
If has been making single request then will be returned single ```Response``` object, 
if multi request array of ```Response``` objects.
```php
$collection = $client->request($url);
$response = $collection->get();
var_dump($response);

$collection = $client->multiRequest($urls);
$responses = $collection->get();

foreach ($responses as $response) {
    var_dump($responses);
}
```

### Convert response to JSON a array representation
You can easy convert ```ArturDoruch\Http\Response\Response``` objects collection
into json representation
```php
$jsonResponse = $collection->toJson(true);
```
or array representation 
```php
$arrayResponse = $collection->toArray();
```

If you going to get response collection in json or array representation, you can
determined which property should be exposed.
As default are exposed properties:
<b>headers, httpCode, body</b>.
Available properties are: 
<b>headers, httpCode, body, effectiveUrl, url, contentType, redirects, errorMsg, errorNumber</b>.

```php
$collection->expose(array('httpCode', 'body', 'effectiveUrl'));
```

Exposed all property in JSON or array representation.
```php
$collection->exposeAll();
```
