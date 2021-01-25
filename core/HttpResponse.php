<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use framework\common\FileHandler;
use framework\common\MimeTypeHandler;
use LogicException;
use stdClass;

class HttpResponse
{
	const TYPE_HTML = 'html';
	const TYPE_JSON = 'json';
	const TYPE_XML = 'xml';
	const TYPE_TXT = 'txt';
	const TYPE_CSV = 'csv';
	const TYPE_JS = 'js';

	const CONTENT_TYPES_WITH_CHARSET = [
		HttpResponse::TYPE_HTML,
		HttpResponse::TYPE_JSON,
		HttpResponse::TYPE_XML,
		HttpResponse::TYPE_TXT,
		HttpResponse::TYPE_CSV,
		HttpResponse::TYPE_JS,
	];
	const defaultMaxAge = 31536000; // One year

	private int $httpStatusCode;
	private array $headers = [];
	private ?string $contentString = null;
	private ?string $contentFilePath = null;

	private function __construct(
		string $eTag,
		int $lastModifiedTimeStamp,
		int $httpStatusCode,
		?string $downloadFileName,
		string $responseType,
		?string $contentString,
		?string $contentFilePath,
		?int $maxAge
	) {
		$this->httpStatusCode = $httpStatusCode;
		$this->setHeader('Etag', $eTag);
		$this->setHeader('Last-Modified', gmdate('r', $lastModifiedTimeStamp));
		$this->setHeader('Cache-Control', 'private, must-revalidate');

		if (!is_null($downloadFileName)) {
			$this->setHeader('Content-Description', 'File Transfer');
			$this->setHeader('Content-Disposition', 'attachment; filename="' . $downloadFileName . '"');
		}

		if ($this->notModifiedCheck($eTag, $lastModifiedTimeStamp)) {
			$this->httpStatusCode = HttpStatusCodes::HTTP_NOT_MODIFIED;
			$this->setHeader('Connection', 'Close'); // Prevent keep-alive
			$this->sendAndExit();
		}

		if (!array_key_exists($this->httpStatusCode, HttpStatusCodes::getAllStatusCodes())) {
			// Just default to internal server error on unknown http status code
			$this->httpStatusCode = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR;
		}

		$responseType = mb_strtolower($responseType);
		$contentType = MimeTypeHandler::mimeTypeByExtension($responseType);
		if (in_array($responseType, HttpResponse::CONTENT_TYPES_WITH_CHARSET)) {
			$contentType .= '; charset=UTF-8';
		}
		$this->setHeader('Content-Type', $contentType);
		if ($responseType === HttpResponse::TYPE_HTML) {
			$this->setHeader('Content-Language', 'de');
		}

		$this->setHeader('Strict-Transport-Security', 'max-age=' . (is_null($maxAge) ? HttpResponse::defaultMaxAge : $maxAge));
		$this->setContentStringAndContentFilePath($contentString, $contentFilePath);
	}

	private function setContentStringAndContentFilePath(?string $contentString, ?string $contentFilePath): void
	{
		$this->contentString = $contentString;
		$this->contentFilePath = $contentFilePath;
	}

	public static function redirectAndExit(string $absoluteUri, int $httpStatusCode = HttpStatusCodes::HTTP_SEE_OTHER): void
	{
		header(HttpStatusCodes::getStatusHeader($httpStatusCode));
		header('Location: ' . $absoluteUri);
		exit;
	}

	public static function createHtmlResponse(int $httpStatusCode, string $htmlContent, ?stdClass $cspPolicySettings, ?string $nonce): HttpResponse
	{
		$httpResponse = new HttpResponse(md5($htmlContent), time(), $httpStatusCode, null, HttpResponse::TYPE_HTML, $htmlContent, null, null);
		if (!is_null($cspPolicySettings)) {
			$httpResponse->setContentSecurityPolicy($cspPolicySettings, $nonce);
		}

		return $httpResponse;
	}

	public static function createResponseFromString(int $httpStatusCode, string $contentString, string $contentType): HttpResponse
	{
		$contentType = mb_strtolower($contentType);
		if ($contentType === HttpResponse::TYPE_HTML) {
			throw new LogicException('Use HttpResponse::createHtmlResponse() instead');
		}

		return new HttpResponse(md5($contentString), time(), $httpStatusCode, null, $contentType, $contentString, null, null);
	}

	public static function createResponseFromFilePath(string $absolutePathToFile, ?bool $forceDownload, ?string $individualFileName, ?int $maxAge): HttpResponse
	{
		$realPath = realpath($absolutePathToFile);

		if (!is_readable($realPath)) {
			header(HttpStatusCodes::getStatusHeader(HttpStatusCodes::HTTP_FORBIDDEN));
			exit;
		}

		if ($realPath === false || !is_file($realPath)) {
			header(HttpStatusCodes::getStatusHeader(HttpStatusCodes::HTTP_NOT_FOUND));
			exit;
		}

		$lastModifiedTimeStamp = filemtime($realPath);
		$fileName = is_null($individualFileName) ? basename($realPath) : $individualFileName;

		$fileExtension = FileHandler::getExtension($fileName);
		if (is_null($forceDownload)) {
			$forceDownload = MimeTypeHandler::forceDownloadByDefault($fileExtension);
		}

		$httpResponse = new HttpResponse(
			md5($lastModifiedTimeStamp . $realPath),
			$lastModifiedTimeStamp,
			HttpStatusCodes::HTTP_OK,
			($forceDownload ? $fileName : null),
			$fileExtension,
			null,
			$realPath,
			$maxAge
		);
		$httpResponse->setHeader('Content-Length', filesize($realPath));
		$httpResponse->setHeader('Expires', gmdate('r', time() + $maxAge));

		return $httpResponse;
	}

	public function setHeader(string $key, string $val): void
	{
		$this->headers[$key] = $val;
	}

	public function removeHeader(string $key): bool
	{
		if (array_key_exists($key, $this->headers)) {
			unset($this->headers[$key]);

			return true;
		}

		return false;
	}

	private function setContentSecurityPolicy(stdClass $cspPolicySettings, string $nonce): void
	{
		$csp = '';
		foreach (get_object_vars($cspPolicySettings) as $key => $val) {
			if ($key === 'script-src' && !str_contains($val, "'none'")) {
				$val .= " 'nonce-" . $nonce . "'";
			}
			$csp .= $key . ' ' . $val . '; ';
		}
		$this->setHeader('Content-Security-Policy', trim($csp));
	}

	public function sendAndExit(): void
	{
		header(HttpStatusCodes::getStatusHeader($this->httpStatusCode));
		foreach ($this->headers as $key => $val) {
			header($key . ': ' . $val);
		}

		if (!is_null($this->contentString)) {
			echo $this->contentString;
		} else if (!is_null($this->contentFilePath)) {
			readfile($this->contentFilePath, false);
		}
		exit;
	}

	private function notModifiedCheck(string $eTag, string $lastModifiedTimeStamp): bool
	{
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $eTag) {
			return true;
		}

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModifiedTimeStamp) {
			return true;
		}

		return false;
	}
}