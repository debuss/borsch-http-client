# Borsch - HTTP Client

A simple implementation of [PSR-18 HTTP Client](https://www.php-fig.org/psr/psr-18/).

This package is part of the Borsch framework.

## Installation

Via [Composer](https://getcomposer.org/) :
```
$ composer require borsch/http-client 
```

## Usage

```php
require 'vendor/autoload.php';

use Borsch\Http\Client;
use Borsch\Http\Request; // PSR-7 Request implementation from any package (see below)

$client = new Client();
$client->setResponseClassName(
    \Borsch\Http\Response::class
);

$request = new Request('GET', 'https://dog.ceo/api/breeds/list/all');
$response = $client->sendRequest($request);
```

## Methods

### ``->setResponseClassName(): Client``

You _**MUST**_ provide an implementation of a PSR-7 ResponseInterface.  
**Borsch\Http\Client** will then use this implementation to create a response instance.

For example, if you are using [Slim PHP PSR-7 implementation](https://github.com/slimphp/Slim-Psr7), your code should look like this :
```php
require 'vendor/autoload.php';

use Borsch\Http\Client;
use Slim\Psr7\Response as SlimPhpResponse;

$client = new Client();
$client->setResponseClassName(SlimPhpResponse::class);
```

An implementation of a PSR-17 ResponseFactoryInterface can also be provided, example with [Nyholm/psr7](https://github.com/Nyholm/psr7) :
```php
require 'vendor/autoload.php';

use Borsch\Http\Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$client = new Client();
$client->setResponseClassName(Psr17Factory::class);
```
**Borsch\Http\Client** will call method `createResponse()` to get the ResponseInterface instance.


### `->getResponseClassName(): string`

Return the ResponseInterface or ResponseFactoryInterface provided in method `setResponseClassName()`.

### `->setCurlOption(int $option, $value): Client`

**Borsch\Http\Client** uses cURL, therefore you can supply some option before sending the request.
**Borsch\Http\Client** already sets these options with values from the request so you will not be able to override them :
* CURLOPT_CUSTOMREQUEST
* CURLOPT_URL
* CURLOPT_HTTP_VERSION
* CURLOPT_POSTFIELDS
* CURLOPT_HTTPHEADER
* CURLOPT_RETURNTRANSFER
* CURLOPT_ENCODING
* CURLOPT_HEADERFUNCTION

Example :
```php
require 'vendor/autoload.php';

use Borsch\Http\Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$client = new Client();
$client->setResponseClassName(Psr17Factory::class);

$request = (new Psr17Factory())->createRequest('GET', 'https://dog.ceo/api/breeds/list/all');
$response = $client
    ->setCurlOption(CURLOPT_CAINFO, dirname(__FILE__).'/cacert.pem') // will work
    ->setCurlOption(CURLOPT_CUSTOMREQUEST, 'POST') // will not work as already used by Borsch\Http\Client
    ->sendRequest($request);
```

### `->setCurlOptions(array $options): Client`

Set many cURL options at once with the provided array.  
Must be of the form :
```php
$options = [
    CURLOPT_CAINFO => dirname(__FILE__).'/cacert.pem',
    // etc.
]
```

### `->getCurlOptions(): array`

Get cURL options you've set.

## License

```
MIT License

Copyright (c) 2018 Alexandre DEBUSSCHERE

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
