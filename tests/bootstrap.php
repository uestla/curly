<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Curly/Curl.php';

Tester\Environment::setup();
date_default_timezone_set('UTC');

Curly\Curl::initialize(__DIR__ . '/temp');
