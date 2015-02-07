# Http

HTTP client for making http requests in enjoyable way.

## Usage

### Make request
Get http client.
```php
$client = new \ArturDoruch\Http\Client();
```

Make single request.
```php
$url = 'https://getcomposer.org';
$collection = $client->makeRequest($url);
```

### Parse and clean response body
If response resource content-type is type of 'text/html' you can clean HTML content.    
Method writing below leaves only tag <body> content from HTML document.
Removes all whitespaces, and tags like: script, noscript, image, iframe, img, meta, input. 
```php
$collection->cleanHtmlBody();
```

If response body have a json format, you can parse it into associative array.
```php
$collection->parseJsonBody();
```

### Get request response
Get all response collection. Method ArturDoruch\Http\Response\ResponseCollection:get
returns ArturDoruch\Http\Response\Response objects collection.
If has been making single request ArturDoruch\Http\Client::makeRequest, 
then will be returned single ArturDoruch\Http\Response\Response object object.
If has been making multi request ArturDoruch\Http\Client::makeMultiRequest, 
then will be returned array of ArturDoruch\Http\Response\Response objects.
```php
$responses = $collection->get();
foreach ($responses as $response) {
    var_dump($response);
}
```

### Convert response to JSON a array representation
You can easy convert ArturDoruch\Http\Response\Response objects collection
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

## Example
