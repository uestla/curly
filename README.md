# Curly\Curl

Single-class PHP cURL wrapper

## Installation

```
$ composer require uestla/curly
```


## Usage

```php
use Curly\Curl;

// initialize first
Curl::initialize(__DIR__ . '/temp');

// simple GET request
$html = Curl::get('http://example.com');

// POST request with values
$html = Curl::post('http://example.com/upload', [
  'foo' => 'bar',
  'hello' => 'world',
]);

// simple HEAD request
$status = Curl::ping('http://example.com');
```


### Features

- Simple API
- re-usage of cookies (that's why initialization is needed - to set temp dir for cookie files)
- `Curl::$userAgent` & `Curl::$maxRedirects` properties
