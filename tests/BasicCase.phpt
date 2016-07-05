<?php

use Curly\Curl;
use Tester\Assert;

require_once __DIR__ . '/bootstrap.php';


$bingURL = 'http://bing.com';

// GET request WITH auto-redirect
$bingHTML = Curl::get($bingURL);
Assert::type('string', $bingHTML);
Assert::contains('<title>Bing</title>', $bingHTML);
Assert::same(0, strlen(Curl::getInfo('redirect_url')));

// GET request WITHOUT auto-redirect
Curl::get($bingURL, FALSE);
Assert::same(301, Curl::getInfo('http_code')); // 301 Moved Permanently (redirect to "www." version)

// HEAD request
Assert::true(Curl::ping($bingURL));
Assert::false(Curl::ping('nonexistingURL'));

// cookies
$cookies = Curl::getCookies('http://www.bing.com');
Assert::true(isset($cookies['SRCHUID']));
