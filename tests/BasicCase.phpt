<?php

use Curly\Curl;
use Tester\Assert;

require_once __DIR__ . '/bootstrap.php';


$googleURL = 'http://google.com';

$googleHTML = Curl::get($googleURL);
Assert::type('string', $googleHTML);
Assert::contains('<title>Google</title>', $googleHTML);

Curl::get($googleURL, FALSE); // no auto-redirect
Assert::same(302, Curl::getInfo('http_code')); // 302 - permanently moved

Assert::true(Curl::ping($googleURL)); // no body response
Assert::false(Curl::ping('nonexistingURL'));
