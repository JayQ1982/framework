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
	}

	protected function getCore(): Core
	{
		return $this->core;
	}

	final public function handleException(Throwable $throwable): void
	{
		$core = $this->core;
		if ($core->getEnvironmentSettingsModel()->isDebug()) {
			$this->sendDebugHttpResponseAndExit($throwable);
		}

		if ($throwable instanceof NotFoundException) {
			$this->sendNotFoundHttpResponseAndExit($throwable);
		}

		if ($throwable instanceof UnauthorizedException) {
			$this->sendUnauthorizedHttpResponseAndExit($throwable);
		}

		$this->core->getLogger()->log('', $throwable);
		$this->sendDefaultHttpResponseAndExit($throwable);
	}

	protected function sendDebugHttpResponseAndExit(Throwable $throwable): void
	{
		$core = $this->core;

		$realException = is_null($throwable->getPrevious()) ? $throwable : $throwable->getPrevious();
		$errorCode = $realException->getCode();
		$errorMessage = $realException->getMessage();

		if ($throwable instanceof NotFoundException) {
			$httpStatusCode = HttpStatusCodes::HTTP_NOT_FOUND;
			$placeholders = ['title' => 'Page not found'];
		} else if ($throwable instanceof UnauthorizedException) {
			$httpStatusCode = HttpStatusCodes::HTTP_UNAUTHORIZED;
			$placeholders = ['title' => 'Unauthorized'];
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

	protected function sendNotFoundHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			$this->core,
			HttpStatusCodes::HTTP_NOT_FOUND,
			$throwable->getMessage(),
			$throwable->getCode(),
			'notFound.html',
			['title' => 'Page not found']
		);
	}

	protected function sendUnauthorizedHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			$this->core,
			HttpStatusCodes::HTTP_UNAUTHORIZED,
			$throwable->getMessage(),
			$throwable->getCode(),
			'unauthorized.html',
			['title' => 'Unauthorized']
		);
	}

	protected function sendDefaultHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			$this->core,
			HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR,
			'Internal Server Error',
			$throwable->getCode(),
			'default.html',
			['title' => 'Internal Server Error']
		);
	}

	final protected function sendHttpResponseAndExit(
		Core $core,
		int $httpStatusCode,
		string $errorMessage,
		string|int $errorCode,
		string $htmlFileName,
		array $placeholders
	): void {
		$contentHandler = $core->getContentHandler();
		$contentType = is_null($contentHandler) ? HttpResponse::TYPE_HTML : $contentHandler->getContentType();

		if ($contentType === HttpResponse::TYPE_HTML) {
			$httpResponse = HttpResponse::createHtmlResponse(
				$httpStatusCode,
				$this->getHtmlContent($core, $htmlFileName, $placeholders),
				$core->getEnvironmentSettingsModel()->getCspPolicySettingsModel(),
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