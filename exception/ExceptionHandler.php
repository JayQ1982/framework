<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\exception;

use framework\core\Core;
use framework\core\HttpResponse;
use framework\core\HttpStatusCodes;
use framework\response\errorResponseContent;
use framework\security\CspNonce;
use framework\security\CsrfToken;
use Throwable;

class ExceptionHandler
{
	const ERRORS = [
		HttpStatusCodes::HTTP_BAD_REQUEST                   => [
			"title"   => "Bad Request",
			"message" => "You sent bad data.",
		],
		HttpStatusCodes::HTTP_UNAUTHORIZED                  => [
			"title"   => "Unauthorized",
			"message" => "You are not authorized to access the requested content.",
		],
		HttpStatusCodes::HTTP_PAYMENT_REQUIRED              => [
			"title"   => "Payment required",
			"message" => "Payment required.",
		],
		HttpStatusCodes::HTTP_FORBIDDEN                     => [
			"title"   => "Forbidden",
			"message" => "You are not allowed to access the requested content.",
		],
		HttpStatusCodes::HTTP_NOT_FOUND                     => [
			"title"   => "Page not found",
			"message" => "The requested page was not found.",
		],
		HttpStatusCodes::HTTP_METHOD_NOT_ALLOWED            => [
			"title"   => "Method Not Allowed",
			"message" => "Method Not Allowed.",
		],
		HttpStatusCodes::HTTP_NOT_ACCEPTABLE                => [
			"title"   => "Not Acceptable",
			"message" => "Not Acceptable (encoding).",
		],
		HttpStatusCodes::HTTP_PROXY_AUTHENTICATION_REQUIRED => [
			"title"   => "Proxy Authentication Required",
			"message" => "Proxy Authentication Required.",
		],
		HttpStatusCodes::HTTP_REQUEST_TIME_OUT              => [
			"title"   => "Request Time-out",
			"message" => "Request Timed Out.",
		],
		HttpStatusCodes::HTTP_CONFLICT                      => [
			"title"   => "Conflict",
			"message" => "Conflicting Request.",
		],
		HttpStatusCodes::HTTP_GONE                          => [
			"title"   => "Gone",
			"message" => "Gone.",
		],
		HttpStatusCodes::HTTP_LENGTH_REQUIRED               => [
			"title"   => "Length Required",
			"message" => "Content Length Required.",
		],
		HttpStatusCodes::HTTP_PRECONDITION_FAILED           => [
			"title"   => "Precondition Failed",
			"message" => "Precondition Failed.",
		],
		HttpStatusCodes::HTTP_REQUEST_ENTITY_TOO_LARGE      => [
			"title"   => "Request Entity Too Large",
			"message" => "Request Entity Too Long.",
		],
		HttpStatusCodes::HTTP_REQUEST_URL_TOO_LONG          => [
			"title"   => "Request-URI Too Long",
			"message" => "Request URI Too Long.",
		],
		HttpStatusCodes::HTTP_UNSUPPORTED_MEDIA_TYPE        => [
			"title"   => "Unsupported Media Type",
			"message" => "Unsupported Media Type.",
		],
		HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR         => [
			"title"   => "Internal Server Error",
			"message" => "An internal server error occurred.",
		],
		HttpStatusCodes::HTTP_NOT_IMPLEMENTED               => [
			"title"   => "501 Not Implemented",
			"message" => "Not Implemented.",
		],
		HttpStatusCodes::HTTP_BAD_GATEWAY                   => [
			"title"   => "Bad Gateway",
			"message" => "Bad Gateway.",
		],
		HttpStatusCodes::HTTP_SERVICE_UNAVAILABLE           => [
			"title"   => "Service Unavailable",
			"message" => "Service Unavailable.",
		],
		HttpStatusCodes::HTTP_GATEWAY_TIME_OUT              => [
			"title"   => "Gateway Time-out",
			"message" => "Gateway Timeout.",
		],
		HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED         => [
			"title"   => "HTTP Version not supported",
			"message" => "HTTP Version Not Supported.",
		],
	];

	private Core $core;

	public function __construct(Core $core)
	{
		$this->core = $core;
		set_exception_handler([$this, 'handleException']);
	}

	public function handleException(Throwable $throwable)
	{
		if ($this->core->getEnvironmentHandler()->isDebug()) {
			$this->returnErrorResponse(true, $throwable);
		}

		$logger = $this->core->getLogger();
		if (!is_null($logger) && !in_array($throwable->getCode(), [401, 404])) {
			$logger->log('', $throwable);
		}

		$this->returnErrorResponse(false, $throwable);
	}

	private function returnErrorResponse(bool $debug, Throwable $throwable): void
	{
		$core = $this->core;
		$errorCode = $throwable->getCode();
		$errorMessage = $throwable->getMessage();

		// Unfortunately, some exceptions (e.g. PDOException) can have non-INTEGER error codes.
		// See http://php.net/manual/de/class.pdoexception.php -> comments
		$intErrorCode = intval($errorCode);

		if (((string)$intErrorCode) !== ((string)$errorCode)) {
			$errorCode = 0;
			$errorMessage = '[' . $intErrorCode . '] ' . $errorMessage;
		} else {
			// It was an integer representation, fixing type (INT expected, not STRING)
			$errorCode = $intErrorCode;
		}

		if ($errorCode === 0) {
			$errorCode = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR;
		}

		$settingsHandler = $core->getSettingsHandler();

		$placeholders = [];
		$placeholders['title'] = self::ERRORS[$errorCode]['title'] ?? self::ERRORS[HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR]['title'];
		$placeholders['errorMessage'] = self::ERRORS[$errorCode]['message'] ?? self::ERRORS[HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR]['message'];

		if ($settingsHandler->exists('errors')) {
			$errorCodes = $settingsHandler->get('errors');
			if (isset($errorCodes->{$errorCode})) {
				$placeholders['title'] = $errorCodes->{$errorCode}->title;
				$placeholders['errorMessage'] = $errorCodes->{$errorCode}->message;
			}
		}

		$contentHandler = $core->getContentHandler();
		$contentType = is_null($contentHandler) ? HttpResponse::TYPE_HTML : $contentHandler->getContentType();

		if ($debug) {
			$placeholders['errorType'] = get_class($throwable);
			$placeholders['errorMessage'] = $errorMessage;
			$placeholders['errorFile'] = $throwable->getFile();
			$placeholders['errorLine'] = $throwable->getLine();
			$placeholders['errorCode'] = $errorCode;
			$placeholders['backtrace'] = ($contentType === HttpResponse::TYPE_HTML) ? $throwable->getTraceAsString() : $throwable->getTrace();
		}

		if ($contentType === HttpResponse::TYPE_HTML) {
			$httpResponse = HttpResponse::createHtmlResponse(
				$errorCode,
				$this->getHtmlContent($core, $debug, $placeholders),
				$core->getEnvironmentHandler()->getCspPolicySettings(),
				CspNonce::get()
			);
			$httpResponse->sendAndExit();
		}

		$content = '';
		if (in_array($contentType, [HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV])) {
			$errorResponseContent = new errorResponseContent($contentType, $placeholders['title'], $errorCode, $placeholders);
			$content = $errorResponseContent->getContent();
		}
		$httpResponse = HttpResponse::createResponseFromString($errorCode, $content, $contentType);
		$httpResponse->sendAndExit();
	}

	private function getHtmlContent(Core $core, bool $debug, array $placeholders): string
	{
		$contentFile = $debug ? 'debug.html' : 'public.html';
		$contentPath = $core->getCoreProperties()->getSiteRoot() . 'error_docs' . DIRECTORY_SEPARATOR . $contentFile;
		if (!file_exists($contentPath)) {
			return $placeholders['title'] . '<br>' . $placeholders['errorMessage'];
		}

		$placeholders['cspNonce'] = CspNonce::get();
		$placeholders['csrfField'] = CsrfToken::renderAsHiddenPostField();

		$settingsHandler = $core->getSettingsHandler();
		if ($settingsHandler->exists('versions')) {
			$versions = $settingsHandler->get('versions');

			foreach (get_object_vars($versions) as $key => $val) {
				$placeholders[$key . 'Version'] = $val;
			}
		}

		$srcArr = [];
		$rplArr = [];
		foreach ($placeholders as $key => $val) {
			$srcArr[] = '{' . $key . '}';
			$rplArr[] = $val;
		}

		$content = file_get_contents($contentPath);

		return str_replace($srcArr, $rplArr, $content);
	}
}
/* EOF */