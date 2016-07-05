<?php

namespace Curly;


class CookieMonster
{

	/** @var string */
	private $tempDir;

	/** @var array */
	private $cookies = NULL;


	const FILE_CURLIB = 'curly-curlib';
	const FILE_INTERNAL = 'curly-internal';


	/** @param  string $tempDir */
	public function __construct($tempDir)
	{
		$this->tempDir = (string) $tempDir;
		$this->loadCookies();
	}


	/**
	 * Formats cookies into the HTTP header string (values separated by "; ")
	 *
	 * @param  string $url
	 * @return string
	 */
	public function prepareCookies($url)
	{
		$info = @parse_url($url);
		if ($info === FALSE) {
			return '';
		}

		$host = isset($info['host']) ? rawurldecode($info['host']) : '';
		$path = '/' . (isset($info['path']) ? $info['path'] : '');

		$s = [];
		foreach ($this->cookies as $domain => $paths) {
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


	/**
	 * Merges $this->cookies & cookies from curlib file
	 * and saves them JSON-encoded in internal cookies file
	 *
	 * @return void
	 */
	public function updateCookies()
	{
		$file = $this->getCurlibCookiesFile();
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

		file_put_contents($this->getInternalCookiesFile(), json_encode($this->cookies));
	}


	/** @return string */
	public function getCurlibCookiesFile()
	{
		return $this->tempDir . '/' . self::FILE_CURLIB;
	}


	/**
	 * Loads JSON-encoded cookies from internal cookies file
	 *
	 * @return void
	 */
	private function loadCookies()
	{
		$this->cookies = [];
		$content = @file_get_contents($this->getInternalCookiesFile());

		if ($content !== FALSE) {
			$this->cookies = json_decode($content, TRUE);
		}
	}


	/** @return string */
	private function getInternalCookiesFile()
	{
		return $this->tempDir . '/' . self::FILE_INTERNAL;
	}

}
