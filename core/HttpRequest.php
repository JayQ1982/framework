<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use DateTime;
use Exception;
use framework\common\StringUtils;

class HttpRequest
{
	public const PROTOCOL_HTTP = 'http';
	public const PROTOCOL_HTTPS = 'https';

	private static ?HttpRequest $instance = null;

	private array $inputData;
	private array $cookies;
	private string $host;
	private string $uri;
	private string $path;
	private int $port;
	private string $protocol;
	private string $query;
	private DateTime $requestTime;
	private string $requestMethod;
	private string $userAgent;
	private array $languages;
	private string $remoteAddress;
	private string $referrer;

	public static function getInstance(): HttpRequest
	{
		if (is_null(HttpRequest::$instance)) {
			HttpRequest::$instance = new HttpRequest();
		}

		return HttpRequest::$instance;
	}

	private function __construct()
	{
		$this->inputData = array_merge($_GET, $_POST);
		$this->cookies = $_COOKIE;
		$this->host = $this->initHost();
		$this->uri = $_SERVER['REQUEST_URI'];
		$this->path = StringUtils::beforeFirst(str: $this->uri, before: '?');
		$this->port = (int)$_SERVER['SERVER_PORT'];
		$this->protocol = $this->initProtocol();
		$this->query = $_SERVER['QUERY_STRING'];
		$this->requestTime = new DateTime();
		$this->requestTime->setTimestamp(timestamp: $_SERVER['REQUEST_TIME']);
		$this->requestMethod = $_SERVER['REQUEST_METHOD'];
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$this->languages = $this->initLanguages();
		$this->remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
		$this->referrer = $_SERVER['HTTP_REFERER'] ?? '';
	}

	private function initHost(): string
	{
		if (isset($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		}

		if (isset($_SERVER['SERVER_NAME'])) {
			return $_SERVER['SERVER_NAME'];
		}
		throw new Exception(message: 'HTTP_HOST and SERVER_NAME are not defined');
	}

	private function initProtocol(): string
	{
		if (isset($_SERVER['HTTPS']) && (int)$_SERVER['HTTPS'] === 1) {
			// Apache
			return HttpRequest::PROTOCOL_HTTPS;
		}

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
			// IIS
			return HttpRequest::PROTOCOL_HTTPS;
		}

		if ($this->port === 443) {
			// Others
			return HttpRequest::PROTOCOL_HTTPS;
		}

		return HttpRequest::PROTOCOL_HTTP;
	}

	private function initLanguages(): array
	{
		$languages = [];
		$langsRates = explode(separator: ',', string: $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

		foreach ($langsRates as $lr) {
			$lrParts = explode(separator: ';', string: $lr);
			$language = StringUtils::beforeFirst(str: $lrParts[0], before: '-');
			$priority = isset($lrParts[1]) ? ((float)StringUtils::afterFirst(str: $lrParts[1], after: 'q=')) * 100 : 100;
			if (!isset($languages[$priority])) {
				$languages[$priority] = $language;
			}
		}
		krsort(array: $languages);

		return $languages;
	}

	public function hasScalarInputValue(string $keyName): bool
	{
		return (isset($this->inputData[$keyName]) && is_scalar(value: $this->inputData[$keyName]));
	}

	public function getInputString(string $keyName): ?string
	{
		return ($this->hasScalarInputValue(keyName: $keyName)) ? trim(string: $this->inputData[$keyName]) : null;
	}

	public function getInputInteger(string $keyName): ?int
	{
		return ($this->hasScalarInputValue(keyName: $keyName)) ? (int)$this->inputData[$keyName] : null;
	}

	public function getInputFloat(string $keyName): ?float
	{
		return ($this->hasScalarInputValue(keyName: $keyName)) ? (float)$this->inputData[$keyName] : null;
	}

	public function getInputArray(string $keyName): ?array
	{
		return (isset($this->inputData[$keyName]) && is_array(value: $this->inputData[$keyName])) ? $this->inputData[$keyName] : null;
	}

	public function getInputValue(string $keyName)
	{
		return $this->inputData[$keyName] ?? null;
	}

	public function getCookies(): array
	{
		return $this->cookies;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getURI(): string
	{
		return $this->uri;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getProtocol(): string
	{
		return $this->protocol;
	}

	public function getQuery(): string
	{
		return $this->query;
	}

	public function getRequestTime(): DateTime
	{
		return $this->requestTime;
	}

	public function getRequestMethod(): string
	{
		return $this->requestMethod;
	}

	public function getUserAgent(): string
	{
		return $this->userAgent;
	}

	public function getLanguages(): array
	{
		return $this->languages;
	}

	public function getRemoteAddress(): string
	{
		return $this->remoteAddress;
	}

	public function getReferrer(): string
	{
		return $this->referrer;
	}

	public function getURL(?string $protocol = null): string
	{
		if (is_null(value: $protocol)) {
			$protocol = $this->protocol;
		}

		return $protocol . '://' . $this->host . $this->uri;
	}

	/**
	 * Returns the details about a file
	 *
	 * @param string $name The name of the file field
	 *
	 * @return array|null Returns the information about the file or null if it does not exist
	 */
	public function getFile(string $name): ?array
	{
		return $_FILES[$name] ?? null;
	}

	/**
	 * Returns a normalized array with file information where each entry of the array
	 * is a set of all information known about one file if the FILES field has an array markup
	 * like field_name[]
	 *
	 * @param string $name The name of the file field
	 *
	 * @return array Returns an array with the information about the files
	 */
	public function getFiles(string $name): array
	{
		$filesArr = $this->getFile(name: $name);

		$files = [];
		$filesCount = count(value: $filesArr['name']);

		for ($i = 0; $i < $filesCount; ++$i) {
			$file = [
				'name'     => $filesArr['name'][$i],
				'type'     => $filesArr['type'][$i],
				'tmp_name' => $filesArr['tmp_name'][$i],
				'error'    => $filesArr['error'][$i],
				'size'     => $filesArr['size'][$i],
			];

			$files[] = $file;
		}

		return $files;
	}
}