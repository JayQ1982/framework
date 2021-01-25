<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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
	private Core $core;

	public function __construct(Core $core)
	{
		$this->core = $core;
		set_exception_handler([$this, 'handleException']);
	}

	public function handleException(Throwable $throwable): void
	{
		$core = $this->core;
		if ($core->getEnvironmentHandler()->isDebug()) {
			$this->sendDebugHttpResponseAndExit($throwable);
		}

		if ($throwable instanceof NotFoundException) {
			$this->sendHttpResponseAndExit(
				$core,
				HttpStatusCodes::HTTP_NOT_FOUND,
				$throwable->getMessage(),
				$throwable->getCode(),
				'notFound.html',
				$throwable->getIndividualPlaceholders()
			);
		}

		if ($throwable instanceof UnauthorizedException) {
			$this->sendHttpResponseAndExit(
				$core,
				HttpStatusCodes::HTTP_UNAUTHORIZED,
				$throwable->getMessage(),
				$throwable->getCode(),
				'unauthorized.html',
				$throwable->getIndividualPlaceholders()
			);
		}

		$this->core->getLogger()->log('', $throwable);
		$this->sendHttpResponseAndExit(
			$core,
			HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR,
			'Internal Server Error',
			$throwable->getCode(),
			'default.html',
			[]
		);
	}

	private function sendDebugHttpResponseAndExit(Throwable $throwable): void
	{
		$core = $this->core;

		$realException = is_null($throwable->getPrevious()) ? $throwable : $throwable->getPrevious();
		$errorCode = $realException->getCode();
		$errorMessage = $realException->getMessage();

		if ($throwable instanceof NotFoundException) {
			$httpStatusCode = HttpStatusCodes::HTTP_NOT_FOUND;
			$placeholders = $throwable->getIndividualPlaceholders();
		} else if ($throwable instanceof UnauthorizedException) {
			$httpStatusCode = HttpStatusCodes::HTTP_UNAUTHORIZED;
			$placeholders = $throwable->getIndividualPlaceholders();
		} else {
			$httpStatusCode = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR;
			$placeholders = ['title' => 'Internal Server Error'];
		}

		$contentHandler = $core->getContentHandler();
		$contentType = is_null($contentHandler) ? HttpResponse::TYPE_HTML : $contentHandler->getContentType();

		$placeholders['errorType'] = get_class($throwable);
		$placeholders['errorMessage'] = $errorMessage;
		$placeholders['errorFile'] = $realException->getFile();
		$placeholders['errorLine'] = $realException->getLine();
		$placeholders['errorCode'] = $realException->getCode();
		$placeholders['backtrace'] = ($contentType === HttpResponse::TYPE_HTML) ? $realException->getTraceAsString() : $realException->getTrace();

		$this->sendHttpResponseAndExit($core, $httpStatusCode, $errorMessage, $errorCode, 'debug.html', $placeholders);
	}

	private function sendHttpResponseAndExit(
		Core $core,
		int $httpStatusCode,
		string $errorMessage,
		int $errorCode,
		string $htmlFileName,
		array $placeholders
	): void {
		$contentHandler = $core->getContentHandler();
		$contentType = is_null($contentHandler) ? HttpResponse::TYPE_HTML : $contentHandler->getContentType();

		if ($contentType === HttpResponse::TYPE_HTML) {
			$httpResponse = HttpResponse::createHtmlResponse(
				$httpStatusCode,
				$this->getHtmlContent($core, $htmlFileName, $placeholders),
				$core->getEnvironmentHandler()->getCspPolicySettings(),
				CspNonce::get()
			);
			$httpResponse->sendAndExit();
		}

		$contentString = '';
		if (in_array($contentType, [HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV])) {
			$errorResponseContent = new errorResponseContent($contentType, $errorMessage, $errorCode, $placeholders);
			$contentString = $errorResponseContent->getContent();
		}

		$httpResponse = HttpResponse::createResponseFromString($httpStatusCode, $contentString, $contentType);
		$httpResponse->sendAndExit();
	}

	private function getHtmlContent(Core $core, string $htmlFileName, array $placeholders): string
	{
		$contentPath = $core->getCoreProperties()->getSiteRoot() . 'error_docs/' . $htmlFileName;
		if (!file_exists($contentPath)) {
			return 'Missing error html file ' . $htmlFileName;
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