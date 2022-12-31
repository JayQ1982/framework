<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\common\FileHandler;
use framework\common\UrlHelper;
use framework\security\CspPolicySettingsModel;
use framework\session\AbstractSessionHandler;
use LogicException;

class HttpResponse
{
	private array $headers = [];

	private function __construct(
		string                   $eTag,
		int                      $lastModifiedTimeStamp,
		private HttpStatusCode   $httpStatusCode,
		?string                  $downloadFileName,
		ContentType              $contentType,
		private readonly ?string $contentString = null,
		private readonly ?string $contentFilePath = null,
		int                      $maxAge = 31536000 // one year
	)
	{
		$this->setHeader(key: 'Etag', val: $eTag);
		$this->setHeader(key: 'Last-Modified', val: gmdate(format: 'r', timestamp: $lastModifiedTimeStamp));
		$this->setHeader(key: 'Cache-Control', val: 'private, must-revalidate');
		if (!is_null(value: $downloadFileName)) {
			$this->setHeader(key: 'Content-Description', val: 'File Transfer');
			$this->setHeader(key: 'Content-Disposition', val: 'attachment; filename="' . $downloadFileName . '"');
		}
		if ($this->notModifiedCheck(eTag: $eTag, lastModifiedTimeStamp: $lastModifiedTimeStamp)) {
			$this->httpStatusCode = HttpStatusCode::HTTP_NOT_MODIFIED;
			$this->setHeader(key: 'Connection', val: 'Close'); // Prevent keep-alive
			$this->sendAndExit();
		}
		$this->setHeader(key: 'Content-Type', val: $contentType->getHttpHeaderString());
		if (!is_null(value: $contentType->languageCode)) {
			$this->setHeader(key: 'Content-Language', val: $contentType->languageCode);
		}
		$this->setHeader(key: 'Strict-Transport-Security', val: 'max-age=' . $maxAge);
	}

	public static function redirectAndExit(
		string         $relativeOrAbsoluteUri,
		HttpStatusCode $httpStatusCode = HttpStatusCode::HTTP_SEE_OTHER,
		bool           $setSameSiteCookieTemporaryToLax = false
	): void {
		if ($setSameSiteCookieTemporaryToLax) {
			AbstractSessionHandler::getSessionHandler()->changeCookieSameSiteToLax();
		}
		header(header: $httpStatusCode->getStatusHeader());
		header(header: 'Location: ' . UrlHelper::generateAbsoluteUri(relativeOrAbsoluteUri: $relativeOrAbsoluteUri));
		exit;
	}

	public static function createHtmlResponse(
		HttpStatusCode          $httpStatusCode,
		string                  $htmlContent,
		?CspPolicySettingsModel $cspPolicySettingsModel,
		?string                 $nonce
	): HttpResponse {
		$httpResponse = new HttpResponse(
			eTag: md5($htmlContent),
			lastModifiedTimeStamp: time(),
			httpStatusCode: $httpStatusCode,
			downloadFileName: null,
			contentType: ContentType::createHtml(),
			contentString: $htmlContent,
			contentFilePath: null
		);
		if (!is_null(value: $cspPolicySettingsModel)) {
			$httpResponse->setHeader(key: 'Content-Security-Policy', val: $cspPolicySettingsModel->getHttpHeaderDataString(nonce: $nonce));
		}

		return $httpResponse;
	}

	public static function createResponseFromString(HttpStatusCode $httpStatusCode, string $contentString, ContentType $contentType): HttpResponse
	{
		if ($contentType->isHtml()) {
			throw new LogicException(message: 'Use HttpResponse::createHtmlResponse() instead');
		}

		return new HttpResponse(
			eTag: md5(string: $contentString),
			lastModifiedTimeStamp: time(),
			httpStatusCode: $httpStatusCode,
			downloadFileName: null,
			contentType: $contentType,
			contentString: $contentString,
			contentFilePath: null
		);
	}

	public static function createResponseFromFilePath(string $absolutePathToFile, ?bool $forceDownload, ?string $individualFileName, int $maxAge): HttpResponse
	{
		$realPath = realpath(path: $absolutePathToFile);

		if (!is_readable(filename: $realPath)) {
			header(header: HttpStatusCode::HTTP_FORBIDDEN->getStatusHeader());
			exit;
		}

		if ($realPath === false || !is_file(filename: $realPath)) {
			header(header: HttpStatusCode::HTTP_NOT_FOUND->getStatusHeader());
			exit;
		}

		$lastModifiedTimeStamp = filemtime(filename: $realPath);
		$fileName = is_null(value: $individualFileName) ? basename(path: $realPath) : $individualFileName;

		$contentType = ContentType::createFromFileExtension(extension: FileHandler::getExtension(filename: $fileName));
		if (is_null(value: $forceDownload)) {
			$forceDownload = $contentType->forceDownloadByDefault;
		}

		$httpResponse = new HttpResponse(
			eTag: md5(string: $lastModifiedTimeStamp . $realPath),
			lastModifiedTimeStamp: $lastModifiedTimeStamp,
			httpStatusCode: HttpStatusCode::HTTP_OK,
			downloadFileName: ($forceDownload ? $fileName : null),
			contentType: $contentType,
			contentString: null,
			contentFilePath: $realPath,
			maxAge: $maxAge
		);
		$httpResponse->setHeader(key: 'Content-Length', val: filesize(filename: $realPath));
		$httpResponse->setHeader(key: 'Expires', val: gmdate(format: 'r', timestamp: time() + $maxAge));

		return $httpResponse;
	}

	public function setHeader(string $key, string $val): void
	{
		$this->headers[$key] = $val;
	}

	public function removeHeader(string $key): bool
	{
		if (array_key_exists(key: $key, array: $this->headers)) {
			unset($this->headers[$key]);

			return true;
		}

		return false;
	}

	public function sendAndExit(): void
	{
		header(header: $this->httpStatusCode->getStatusHeader());
		foreach ($this->headers as $key => $val) {
			header(header: $key . ': ' . $val);
		}

		if (!is_null(value: $this->contentString)) {
			echo $this->contentString;
		} else if (!is_null(value: $this->contentFilePath)) {
			readfile(filename: $this->contentFilePath);
		}
		exit;
	}

	private function notModifiedCheck(string $eTag, int $lastModifiedTimeStamp): bool
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