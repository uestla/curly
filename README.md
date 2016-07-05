# Curly\Curl

Single-class PHP cURL wrapper

## Installation

```
$ composer require uestla/curly
```


## Basic usage

```php
use Curly\Curl;

// initialize first - set temp directory for cookie files
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

- `Curly\Curl::$userAgent` - string with UserAgent header sent with each request (default: [here](https://github.com/uestla/curly/blob/master/src/Curly/Curl.php#L12-L13))

- `Curly\Curl::$maxRedirects` - max. number of redirects when auto-redirect is `TRUE` (default: 6)


### Cookies

`Curly` offers basic cookie-reading support:

```php
// all cookies across all domains
$cookies = Curl::getCookies();

// cookies for specific domain
$cookies = Curl::getCookies('http://example.com');

// cookies for specific domain and path
$cookies = Curl::getCookies('http://example.com/foo/bar');
```
