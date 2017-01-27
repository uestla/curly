<?php

/**
 * This file is part of the Curly package
 *
 * @license  MIT
 * @author   Petr Kessler (https://kesspess.cz)
 * @link     https://github.com/uestla/curly
 */

namespace Curly;


class CookieMonster
{

	/** @var string */
	private $tempDir;

	/** @var array */
	private $cookies = NULL;


	const FILE_COOKIES = 'curly-cookies';


	/** @param  string $tempDir */
	public function __construct($tempDir)
	{
		$this->tempDir = (string) $tempDir;
	}


	/**
	 * @param  string $url
	 * @return array
	 */
	public function getCookies($url = NULL)
	{
		$this->loadCookies();

		if ($url === NULL) {
			return $this->cookies;
		}

		$info = @parse_url($url);
		if ($info === FALSE) {
			return [];
		}

		$host = isset($info['host']) ? rawurldecode($info['host']) : '';
		$path = '/' . (isset($info['path']) ? rtrim($info['path'], '/') : '');

		$cookies = [];
		foreach ($this->cookies as $domain => $paths) {
			if (substr($host, -strlen($domain)) === $domain) { // $host ends with $domain
				foreach ($paths as $p => $values) {
					if (strncmp($path, $p, strlen($p)) === 0) { // path matches
						foreach ($values as $name => $value) {
							if (!$value['expiration'] || time() < $value['expiration']) { // not expired yet
								$cookies[$name] = $value['value'];
							}
						}
					}
				}
			}
		}

		return $cookies;
	}


	/** @return void */
	public function loadCookies()
	{
		if ($this->cookies === NULL) {
			$this->reloadCookies();
		}
	}


	/** @return void */
	public function reloadCookies()
	{
		$this->cookies = [];
		$file = $this->getCookiesFile();
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

				!isset($this->cookies[$domain]) && ($this->cookies[$domain] = []);
				!isset($this->cookies[$domain][$path]) && ($this->cookies[$domain][$path] = []);

				$this->cookies[$domain][$path][$name] = [
					'value' => $value,
					'expiration' => $expiration,
				];
			}
		}
	}


	/** @return string */
	public function getCookiesFile()
	{
		return $this->tempDir . '/' . self::FILE_COOKIES;
	}

}
