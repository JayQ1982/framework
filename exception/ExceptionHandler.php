<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\exception;

use framework\Core;
use framework\core\ContentHandler;
use framework\core\ContentType;
use framework\core\EnvironmentSettingsModel;
use framework\core\HttpResponse;
use framework\core\HttpStatusCode;
use framework\response\HttpErrorResponseContent;
use framework\security\CspNonce;
use framework\security\CsrfToken;
use LogicException;
use Throwable;

class ExceptionHandler
{
	private static ?ExceptionHandler $registeredInstance = null;
	protected ContentType $contentType;

	public static function register(?ExceptionHandler $individualExceptionHandler): void
	{
		if (!is_null(value: ExceptionHandler::$registeredInstance)) {
			throw new LogicException(message: 'ExceptionHandler is already registered.');
		}
		ExceptionHandler::$registeredInstance = is_null(value: $individualExceptionHandler) ? new ExceptionHandler() : $individualExceptionHandler;
		set_exception_handler(callback: [
			ExceptionHandler::$registeredInstance, 'handleException',
		]);
	}

	final public function handleException(Throwable $throwable): void
	{
		$this->contentType = ContentHandler::isRegistered() ? ContentHandler::get()->getContentType() : ContentType::createHtml();
		if (EnvironmentSettingsModel::get()->debug) {
			$this->sendDebugHttpResponseAndExit(throwable: $throwable);
		}
		if ($throwable instanceof NotFoundException) {
			$this->sendNotFoundHttpResponseAndExit(throwable: $throwable);
		}
		if ($throwable instanceof UnauthorizedException) {
			$this->sendUnauthorizedHttpResponseAndExit(throwable: $throwable);
		}
		Core::get()->logger->log(message: '', exceptionToLog: $throwable);
		$this->sendDefaultHttpResponseAndExit(throwable: $throwable);
	}

	protected function sendDebugHttpResponseAndExit(Throwable $throwable): void
	{
		$realException = is_null(value: $throwable->getPrevious()) ? $throwable : $throwable->getPrevious();
		$errorCode = $realException->getCode();
		$errorMessage = $realException->getMessage();

		if ($throwable instanceof NotFoundException) {
			$httpStatusCode = HttpStatusCode::HTTP_NOT_FOUND;
			$placeholders = ['title' => 'Page not found'];
		} else if ($throwable instanceof UnauthorizedException) {
			$httpStatusCode = HttpStatusCode::HTTP_UNAUTHORIZED;
			$placeholders = ['title' => 'Unauthorized'];
		} else {
			$httpStatusCode = HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR;
			$placeholders = ['title' => 'Internal Server Error'];
		}

		$placeholders['errorType'] = get_class(object: $throwable);
		$placeholders['errorMessage'] = $errorMessage;
		$placeholders['errorFile'] = $realException->getFile();
		$placeholders['errorLine'] = $realException->getLine();
		$placeholders['errorCode'] = $realException->getCode();
		$placeholders['backtrace'] = (!$this->contentType->isJson()) ? $realException->getTraceAsString() : $realException->getTrace();
		$placeholders['vardump_get'] = isset($_GET) ? htmlentities(string: var_export(value: $_GET, return: true)) : '';
		$placeholders['vardump_post'] = isset($_POST) ? htmlentities(string: var_export(value: $_POST, return: true)) : '';
		$placeholders['vardump_file'] = isset($_FILE) ? htmlentities(string: var_export(value: $_FILE, return: true)) : '';
		$placeholders['vardump_sess'] = isset($_SESSION) ? htmlentities(string: var_export(value: $_SESSION, return: true)) : '';

		$this->sendHttpResponseAndExit(
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
			httpStatusCode: HttpStatusCode::HTTP_NOT_FOUND,
			errorMessage: $throwable->getMessage(),
			errorCode: $throwable->getCode(),
			htmlFileName: 'notFound.html',
			placeholders: []
		);
	}

	protected function sendUnauthorizedHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			httpStatusCode: HttpStatusCode::HTTP_UNAUTHORIZED,
			errorMessage: $throwable->getMessage(),
			errorCode: $throwable->getCode(),
			htmlFileName: 'unauthorized.html',
			placeholders: []
		);
	}

	protected function sendDefaultHttpResponseAndExit(Throwable $throwable): void
	{
		$this->sendHttpResponseAndExit(
			httpStatusCode: HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR,
			errorMessage: 'Internal Server Error',
			errorCode: $throwable->getCode(),
			htmlFileName: 'default.html',
			placeholders: []
		);
	}

	final protected function sendHttpResponseAndExit(
		HttpStatusCode $httpStatusCode,
		string         $errorMessage,
		string|int     $errorCode,
		string         $htmlFileName,
		array          $placeholders
	): void {
		$contentType = $this->contentType;
		if ($contentType->isJson()) {
			$httpResponse = HttpResponse::createResponseFromString(
				httpStatusCode: $httpStatusCode,
				contentString: HttpErrorResponseContent::createJsonResponseContent(
					errorMessage: $errorMessage,
					errorCode: $errorCode,
					additionalInfo: (object)$placeholders
				)->getContent(),
				contentType: $contentType
			);
			$httpResponse->sendAndExit();
		}
		if ($contentType->isTxt() || $contentType->isCsv()) {
			$httpResponse = HttpResponse::createResponseFromString(
				httpStatusCode: $httpStatusCode,
				contentString: HttpErrorResponseContent::createTextResponseContent(
					errorMessage: $errorMessage,
					errorCode: $errorCode
				)->getContent(),
				contentType: $contentType
			);
			$httpResponse->sendAndExit();
		}
		$httpResponse = HttpResponse::createHtmlResponse(
			httpStatusCode: $httpStatusCode,
			htmlContent: $this->getHtmlContent(htmlFileName: $htmlFileName, placeholders: $placeholders),
			cspPolicySettingsModel: EnvironmentSettingsModel::get()->cspPolicySettingsModel,
			nonce: CspNonce::get()
		);
		$httpResponse->sendAndExit();
	}

	private function getHtmlContent(string $htmlFileName, array $placeholders): string
	{
		$contentPath = Core::get()->errorDocsDirectory . $htmlFileName;
		if (!file_exists(filename: $contentPath)) {
			return 'Missing error html file ' . $contentPath;
		}
		$placeholders['cspNonce'] = CspNonce::get();
		$placeholders['csrfField'] = CsrfToken::renderAsHiddenPostField();
		$srcArr = [];
		$rplArr = [];
		foreach ($placeholders as $key => $val) {
			$srcArr[] = '{' . $key . '}';
			$rplArr[] = $val;
		}

		$content = file_get_contents(filename: $contentPath);

		return str_replace(search: $srcArr, replace: $rplArr, subject: $content);
	}
}