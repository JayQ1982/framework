<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use framework\common\StringUtils;

class HttpRequest
{
	public const PROTOCOL_HTTP = 'http';
	public const PROTOCOL_HTTPS = 'https';

	private static ?string $host = null;
	private static ?string $protocol = null;
	private static ?array $languages = null;

	public static function hasScalarInputValue(string $keyName): bool
	{
		$inputData = HttpRequest::getInputData();

		return (isset($inputData[$keyName]) && is_scalar(value: $inputData[$keyName]));
	}

	public static function getInputString(string $keyName): ?string
	{
		$inputData = HttpRequest::getInputData();

		return (HttpRequest::hasScalarInputValue(keyName: $keyName)) ? trim(string: $inputData[$keyName]) : null;
	}

	public static function getInputInteger(string $keyName): ?int
	{
		$inputData = HttpRequest::getInputData();

		return (HttpRequest::hasScalarInputValue(keyName: $keyName)) ? (int)$inputData[$keyName] : null;
	}

	public static function getInputFloat(string $keyName): ?float
	{
		$inputData = HttpRequest::getInputData();

		return (HttpRequest::hasScalarInputValue(keyName: $keyName)) ? (float)$inputData[$keyName] : null;
	}

	public static function getInputArray(string $keyName): ?array
	{
		$inputData = HttpRequest::getInputData();

		return (isset($inputData[$keyName]) && is_array(value: $inputData[$keyName])) ? $inputData[$keyName] : null;
	}

	public static function getInputValue(string $keyName)
	{
		$inputData = HttpRequest::getInputData();

		return $inputData[$keyName] ?? null;
	}

	public static function getCookies(): array
	{
		return $_COOKIE;
	}

	public static function getHost(): string
	{
		if (!is_null(HttpRequest::$host)) {
			return HttpRequest::$host;
		}
		if (isset($_SERVER['HTTP_HOST'])) {
			return HttpRequest::$host = $_SERVER['HTTP_HOST'];
		}

		if (isset($_SERVER['SERVER_NAME'])) {
			return HttpRequest::$host = $_SERVER['SERVER_NAME'];
		}
		throw new Exception(message: 'HTTP_HOST and SERVER_NAME are not defined');
	}

	public static function getURI(): string
	{
		return $_SERVER['REQUEST_URI'];
	}

	public static function getPath(): string
	{
		return StringUtils::beforeFirst(str: HttpRequest::getURI(), before: '?');
	}

	public static function getPort(): int
	{
		return (int)$_SERVER['SERVER_PORT'];
	}

	public static function getProtocol(): string
	{
		if (!is_null(HttpRequest::$protocol)) {
			return HttpRequest::$protocol;
		}
		if (isset($_SERVER['HTTPS']) && (int)$_SERVER['HTTPS'] === 1) {
			// Apache
			return HttpRequest::$protocol = HttpRequest::PROTOCOL_HTTPS;
		}

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
			// IIS
			return HttpRequest::$protocol = HttpRequest::PROTOCOL_HTTPS;
		}

		if (HttpRequest::getPort() === 443) {
			// Others
			return HttpRequest::$protocol = HttpRequest::PROTOCOL_HTTPS;
		}

		return HttpRequest::$protocol = HttpRequest::PROTOCOL_HTTP;
	}

	public static function getQuery(): string
	{
		return $_SERVER['QUERY_STRING'];
	}

	public static function getRequestMethod(): string
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	public static function getUserAgent(): string
	{
		return $_SERVER['HTTP_USER_AGENT'] ?? '';
	}

	public static function getLanguages(): array
	{
		if (!is_null(HttpRequest::$languages)) {
			return HttpRequest::$languages;
		}
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

		return HttpRequest::$languages = $languages;
	}

	public static function getRemoteAddress(): string
	{
		return $_SERVER['REMOTE_ADDR'] ?? '';
	}

	private static function getInputData(): array
	{
		return array_merge($_GET, $_POST);
	}

	public static function getReferrer(): string
	{
		return $_SERVER['HTTP_REFERER'] ?? '';
	}

	public static function getURL(?string $protocol = null): string
	{
		if (is_null(value: $protocol)) {
			$protocol = HttpRequest::getProtocol();
		}

		return $protocol . '://' . HttpRequest::getHost() . HttpRequest::getURI();
	}

	/**
	 * Returns the details about a file
	 *
	 * @param string $name The name of the file field
	 *
	 * @return array|null Returns the information about the file or null if it does not exist
	 */
	public static function getFile(string $name): ?array
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
	public static function getFiles(string $name): array
	{
		$filesArr = HttpRequest::getFile(name: $name);

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