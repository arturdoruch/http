# Http

HTTP client for making http requests in enjoyable way.

## Usage

### Make request
Get http client.
```php
$client = new \ArturDoruch\Http\Client();
// Optional set number of maximum multi connections. Default is 8.
$client->setConnections(4);
```
Make single request.
```php
$url = 'https://getcomposer.org';
$collection = $client->makeRequest($url);
```
Make multi requests.
```php
$urls = array();
$collection = $client->makeMultiRequest($urls);
```

For send request with HTTP method different then GET or send some parameters 
use ```ArturDoruch\Http\RequestParameter``` class as second argument in 
```ArturDoruch\Http\Client::makeRequest``` or ```ArturDoruch\Http\Client::makeMultiRequest``` method.
You can set parameters like: parameters, headers, cookies, method and url.
Parameter url is uses only with single request.
```php
$requestParams = new \ArturDoruch\Http\RequestParameter();
$requestParams->setMethod('POST')
    ->setParameters('name', 'value');

$collection = $client->makeMultiRequest($urls, $requestParams);
```

### Parse and clean response body
If response content-type is type of 'text/html' you can clean HTML content.    
Method ```cleanHtmlBody``` leaves only ```<body>``` content from HTML document,
removes all whitespaces and tags like: script, noscript, image, iframe, img, meta, input. 

```php
$collection->cleanHtmlBody();
```

You can specified your own custom class to clean HTML code.
This class must implements ```ArturDoruch\Http\Response\ResponseBodyInterface```.
To using this class pass it as second parameter in ```ArturDoruch\Http\Response\ResponseCollection::cleanHtmlBody method```.
See example class link: ArturDoruch\Http\Response\Body\Html.
```php
$htmlBody = new \ArturDoruch\Http\Response\Body\Html();
$collection->cleanHtmlBody($htmlBody);
```

If response body have a json format, you can parse it into associative array.
```php
$collection->parseJsonBody();
```

### Get request response
Get all response collection. Method ```ArturDoruch\Http\Response\ResponseCollection:get```
returns ```ArturDoruch\Http\Response\Response``` objects collection.
If has been making single request ```ArturDoruch\Http\Client::makeRequest```, 
then will be returned single ```ArturDoruch\Http\Response\Response object object```.
If has been making multi request ```ArturDoruch\Http\Client::makeMultiRequest```, 
then will be returned array of ```ArturDoruch\Http\Response\Response``` objects.
```php
$responses = $collection->get();
foreach ($responses as $response) {
    var_dump($response);
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
As default are exposed properties: headers, httpCode, body.
Available properties are: headers, httpCode, body, effectiveUrl, url, contentType, redirects, errorMsg, errorNumber
```php
$collection->expose(array('httpCode', 'body', 'effectiveUrl'));
```

Exposed all property in JSON or array representation.
```php
$collection->exposeAll();
```
