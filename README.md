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

// GET request
$html = Curl::get($url);

// GET request with no auto-redirect
$html = Curl::get($url, FALSE);


// POST request with values
$html = Curl::post($url, [
  'foo' => 'bar',
  'hello' => 'world',
  'file' => new CURLFile($path),
]);


// HEAD request
$status = Curl::ping($url);


// last response info
$info = Curl::getInfo();

// or single info field
$httpCode = Curl::getInfo('http_code');
```


### Settings

- `Curly\Curl::$userAgent` - string with UserAgent setting sent with each request (default: [here](https://github.com/uestla/curly/blob/master/src/Curly/Curl.php#L10))

- `Curly\Curl::$maxRedirects` - max. number of redirects when auto-redirect is `TRUE` (default: 6)


### Cookies

In `curlib` (used in PHP cURL extension), cookies are not re-used when the same PHP script runs multiple times. `Curly` simulates browser cookies in the way that when one script runs multiple times it remembers previously saved cookies. Both cookie files (for `curlib` and internally for `Curly`) are saved in `$tempDir` directory that is set in `initialize()` method.
