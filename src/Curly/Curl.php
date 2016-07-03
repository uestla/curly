<?php

namespace Curly;


class Curl
{

	/** @var int */
	public static $maxRedirects = 6;

	/** @var string */
	public static $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';


	/** @var int */
	private static $depth = 0;

	/** @var int */
	private static $tempDir = NULL;

	/** @var array */
	private static $cookies = NULL;

	/** @var bool */
	private static $initialized = FALSE;

	/** @var int */
	private static $lastErrno = NULL;

	/** @var array */
	private static $lastInfo = NULL;


	const FILE_CURLIB = 'curly-curlib';
	const FILE_INTERNAL = 'curly-internal';


	/**
	 * @param  string $tempDir
	 * @return void
	 */
	public static function initialize($tempDir)
	{
		if (self::$initialized) {
			throw new \Exception('Curl service is already initialized.');
		}

		self::$tempDir = (string) $tempDir;
		self::loadCookies();

		self::$initialized = TRUE;
	}


	/** @return bool */
	public static function isInitialized()
	{
		return self::$initialized;
	}


	/**
	 * Makes GET request without downloading response body
	 *
	 * @param  string $url
	 * @param  bool $redirect
	 * @param  array $headers
	 * @return bool
	 */
	public static function ping($url, $redirect = TRUE, array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => FALSE,
			CURLOPT_NOBODY => TRUE,
		];

		$headers !== NULL && ($options[CURLOPT_HTTPHEADER] = $headers);

		$res = self::request($options, $redirect);
		return $res === FALSE ? FALSE : TRUE; // ::request() returns "" on success
	}


	/**
	 * Makes GET request and returns response body
	 *
	 * @param  string $url
	 * @param  bool $redirect
	 * @param  array $headers
	 * @return string|FALSE
	 */
	public static function get($url, $redirect = TRUE, array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => FALSE,
			CURLOPT_NOBODY => FALSE,
		];

		$headers !== NULL && ($options[CURLOPT_HTTPHEADER] = $headers);
		return self::request($options, $redirect);
	}


	/**
	 * Makes POST request and returns response body
	 *
	 * @param  string $url
	 * @param  mixed $values
	 * @param  bool $redirect
	 * @param  array $headers
	 * @return string|FALSE
	 */
	public static function post($url, $values = NULL, $redirect = TRUE, array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => TRUE,
			CURLOPT_NOBODY => FALSE,
			CURLOPT_POSTFIELDS => $values,
		];

		$headers !== NULL && ($options[CURLOPT_HTTPHEADER] = $headers);
		return self::request($options, $redirect);
	}


	/**
	 * @param  string $key
	 * @return array|string|int|NULL
	 */
	public static function getInfo($key = NULL)
	{
		if ($key === NULL) {
			return self::$lastInfo;
		}

		return self::$lastInfo[$key];
	}


	/**
	 * @param  array $options
	 * @param  bool $redirect
	 * @return mixed|FALSE
	 */
	private static function request(array $options, $redirect = TRUE)
	{
		if (!self::$initialized) {
			throw new \Exception('Curl service not initialized. Please call the ::initialize() method.');
		}

		if (self::$depth >= self::$maxRedirects) {
			self::$depth = 0;
			return FALSE;
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_COOKIESESSION => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_USERAGENT => self::$userAgent,
			CURLOPT_COOKIEJAR => self::getCurlibCookiesFile(),
			CURLOPT_COOKIEFILE => self::getCurlibCookiesFile(),
			CURLOPT_COOKIE => self::formatCookies($options[CURLOPT_URL]),
		]);

		curl_setopt_array($ch, $options);

		$res = curl_exec($ch);
		self::$lastErrno = curl_errno($ch);
		self::$lastInfo = curl_getinfo($ch);

		curl_close($ch);
		unset($ch); // frees memory

		self::updateCookies();

		if (self::$lastErrno !== 0) {
			return FALSE;
		}

		if ($redirect && strlen(self::$lastInfo['redirect_url'])) { // make request to redirect URL
			self::$depth++;
			$method = isset($options[CURLOPT_NOBODY]) && $options[CURLOPT_NOBODY] ? 'ping' : 'get'; // call proper method

			return self::{$method}(self::$lastInfo['redirect_url'], $redirect);
		}

		self::$depth = 0;
		return $res;
	}


	/**
	 * Loads JSON-encoded cookies from internal cookies file
	 *
	 * @return void
	 */
	private static function loadCookies()
	{
		self::$cookies = [];
		$content = @file_get_contents(self::getInternalCookiesFile());

		if ($content !== FALSE) {
			self::$cookies = json_decode($content, TRUE);
		}
	}


	/**
	 * Merges self::$cookies & cookies in curlib file
	 * and saves them JSON-encoded in internal cookies file
	 *
	 * @return void
	 */
	private static function updateCookies()
	{
		$file = self::getCurlibCookiesFile();
		$lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if ($lines !== FALSE) {
			foreach ($lines as $line) {
				if (isset($line[0]) && $line[0] === '#') { // # comment
					continue ;
				}

				$parts = explode("\t", $line);

				if (count($parts) < 7) {
					continue ;
				}

				list ($domain, $flag, $path, $secure, $expiration, $name, $value) = $parts;

				!isset(self::$cookies[$domain]) && (self::$cookies[$domain] = []);
				!isset(self::$cookies[$domain][$path]) && (self::$cookies[$domain][$path] = []);

				self::$cookies[$domain][$path][$name] = [
					'value' => $value,
					'expiration' => $expiration,
				];
			}
		}

		file_put_contents(self::getInternalCookiesFile(), json_encode(self::$cookies));
	}


	/**
	 * Formats cookies into the HTTP header string (values separated by "; ")
	 *
	 * @param  string $url
	 * @return string
	 */
	private static function formatCookies($url)
	{
		$info = @parse_url($url);
		if ($info === FALSE) {
			return '';
		}

		$host = isset($info['host']) ? rawurldecode($info['host']) : '';
		$path = '/' . (isset($info['path']) ? $info['path'] : '');

		$s = [];
		foreach (self::$cookies as $domain => $paths) {
			if (substr($host, -strlen($domain)) === $domain) { // $host ends with $domain
				foreach ($paths as $p => $values) {
					if (strncmp($path, $p, strlen($p)) === 0) { // path matches
						foreach ($values as $name => $value) {
							if (!$value['expiration'] || time() < $value['expiration']) { // not expired yet
								$s[] = $name . '=' . $value['value'];
							}
						}
					}
				}
			}
		}

		return implode('; ', $s);
	}


	/** @return string */
	private static function getCurlibCookiesFile()
	{
		return (self::$tempDir ?: __DIR__) . '/' . self::FILE_CURLIB;
	}


	/** @return string */
	private static function getInternalCookiesFile()
	{
		return (self::$tempDir ?: __DIR__) . '/' . self::FILE_INTERNAL;
	}

}
