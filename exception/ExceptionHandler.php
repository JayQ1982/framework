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
	private string $contentType;

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
		$this->contentType = $this->initContentType($core);
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

		$placeholders['errorType'] = get_class($throwable);
		$placeholders['errorMessage'] = $errorMessage;
		$placeholders['errorFile'] = $realException->getFile();
		$placeholders['errorLine'] = $realException->getLine();
		$placeholders['errorCode'] = $realException->getCode();
		$placeholders['backtrace'] = ($this->contentType === HttpResponse::TYPE_HTML) ? $realException->getTraceAsString() : $realException->getTrace();
		$placeholders['vardump_get'] = isset($_GET) ? htmlentities(var_export($_GET, true)) : '';
		$placeholders['vardump_post'] = isset($_POST) ? htmlentities(var_export($_POST, true)) : '';
		$placeholders['vardump_file'] = isset($_FILE) ? htmlentities(var_export($_FILE, true)) : '';
		$placeholders['vardump_sess'] = isset($_SESSION) ? htmlentities(var_export($_SESSION, true)) : '';

		$this->sendHttpResponseAndExit(
			core: $core,
			httpStatusCode: $httpStatusCode,
			errorMessage: $errorMessage,
			errorCode: $errorCode,
			htmlFileName: 'debug.html',
			placeholders: $placeholders
		);
	}

	protected function sendNotFoundHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			core: $this->core,
			httpStatusCode: HttpStatusCodes::HTTP_NOT_FOUND,
			errorMessage: $throwable->getMessage(),
			errorCode: $throwable->getCode(),
			htmlFileName: 'notFound.html',
			placeholders: []
		);
	}

	protected function sendUnauthorizedHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			core: $this->core,
			httpStatusCode: HttpStatusCodes::HTTP_UNAUTHORIZED,
			errorMessage: $throwable->getMessage(),
			errorCode: $throwable->getCode(),
			htmlFileName: 'unauthorized.html',
			placeholders: []
		);
	}

	protected function sendDefaultHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			core: $this->core,
			httpStatusCode: HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR,
			errorMessage: 'Internal Server Error',
			errorCode: $throwable->getCode(),
			htmlFileName: 'default.html',
			placeholders: []
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
		$contentType = $this->contentType;
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

	private function initContentType(Core $core): string
	{
		$contentHandler = $core->getContentHandler();
		if (is_null($contentHandler)) {
			return HttpResponse::TYPE_HTML;
		}

		$contentType = $contentHandler->getContentType();
		if (in_array($contentType, [
			HttpResponse::TYPE_HTML,
			HttpResponse::TYPE_JSON,
			HttpResponse::TYPE_XML,
			HttpResponse::TYPE_TXT,
			HttpResponse::TYPE_CSV,
		])) {
			return $contentType;
		}

		$contentHandler->setContentType(HttpResponse::TYPE_HTML);

		return HttpResponse::TYPE_HTML;
	}
}