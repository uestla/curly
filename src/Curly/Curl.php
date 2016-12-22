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

	/** @var bool */
	private static $initialized = FALSE;

	/** @var int */
	private static $lastErrno = NULL;

	/** @var array */
	private static $lastInfo = NULL;


	/** @var CookieMonster */
	private static $cookieMonster = NULL;


	/**
	 * @param  string $tempDir
	 * @return void
	 */
	public static function initialize($tempDir)
	{
		if (self::$initialized) {
			throw new \Exception('Curl service is already initialized.');
		}

		self::$cookieMonster = new CookieMonster($tempDir);
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
	 * @param  array $options
	 * @param  array $headers
	 * @return bool
	 */
	public static function ping($url, $redirect = TRUE, array $options = [], array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => FALSE,
			CURLOPT_NOBODY => TRUE,

		] + $options;

		if ($headers !== NULL) {
			$options[CURLOPT_HTTPHEADER] = $headers;
		}

		$res = self::request($options, $redirect);
		return $res === FALSE ? FALSE : TRUE; // ::request() returns "" on success
	}


	/**
	 * Makes GET request and returns response body
	 *
	 * @param  string $url
	 * @param  bool $redirect
	 * @param  array $options
	 * @param  array $headers
	 * @return string|FALSE
	 */
	public static function get($url, $redirect = TRUE, array $options = [], array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => FALSE,
			CURLOPT_NOBODY => FALSE,

		] + $options;

		if ($headers !== NULL) {
			$options[CURLOPT_HTTPHEADER] = $headers;
		}

		return self::request($options, $redirect);
	}


	/**
	 * Makes POST request and returns response body
	 *
	 * @param  string $url
	 * @param  mixed $values
	 * @param  bool $redirect
	 * @param  array $options
	 * @param  array $headers
	 * @return string|FALSE
	 */
	public static function post($url, $values = NULL, $redirect = TRUE, array $options = [], array $headers = NULL)
	{
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_POST => TRUE,
			CURLOPT_NOBODY => FALSE,
			CURLOPT_POSTFIELDS => $values,

		] + $options;

		if ($headers !== NULL) {
			$options[CURLOPT_HTTPHEADER] = $headers;
		}

		return self::request($options, $redirect);
	}


	/**
	 * @param  string $key
	 * @return array|string|int|NULL
	 */
	public static function getInfo($key = NULL)
	{
		self::checkInitialization();

		if ($key === NULL) {
			return self::$lastInfo;
		}

		return self::$lastInfo[$key];
	}


	/**
	 * @param  string $url
	 * @return array
	 */
	public static function getCookies($url = NULL)
	{
		self::checkInitialization();
		return self::$cookieMonster->getCookies($url);
	}


	/**
	 * @param  array $options
	 * @param  bool $redirect
	 * @return mixed|FALSE
	 */
	private static function request(array $options, $redirect = TRUE)
	{
		self::checkInitialization();

		if (self::$depth >= self::$maxRedirects) {
			self::$depth = 0;
			return FALSE;
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => self::$userAgent,
			CURLOPT_COOKIEJAR => self::$cookieMonster->getCookiesFile(),
			CURLOPT_COOKIEFILE => self::$cookieMonster->getCookiesFile(),
		]);

		curl_setopt_array($ch, $options);

		$res = curl_exec($ch);
		self::$lastErrno = curl_errno($ch);
		self::$lastInfo = curl_getinfo($ch);

		curl_close($ch);
		unset($ch); // frees memory

		self::$cookieMonster->reloadCookies();

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


	/** @return void */
	private static function checkInitialization()
	{
		if (!self::$initialized) {
			throw new \Exception('Curl service not initialized. Please call the ::initialize() method.');
		}
	}

}
