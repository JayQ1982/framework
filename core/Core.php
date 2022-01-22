<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\autoloader\Autoloader;
use framework\autoloader\AutoloaderPathModel;
use framework\exception\ExceptionHandler;
use framework\exception\NotFoundException;
use framework\security\CspNonce;
use framework\session\AbstractSessionHandler;
use LogicException;

class Core
{
	private static ?Core $instance = null;

	private bool $isHttpResponsePrepared = false;
	private Autoloader $autoloader;
	private CoreProperties $coreProperties;
	private HttpRequest $httpRequest;
	private SettingsHandler $settingsHandler;
	private EnvironmentSettingsModel $environmentSettingsModel;
	private Logger $logger;
	private ErrorHandler $errorHandler;
	private AbstractSessionHandler $sessionHandler;
	private RequestHandler $requestHandler;
	private ?LocaleHandler $localeHandler = null;
	private ?ContentHandler $contentHandler = null;

	public static function init(string $defaultTimeZone = 'Europe/Zurich'): Core
	{
		if (!is_null(value: Core::$instance)) {
			throw new LogicException(message: 'Core is already initialized');
		}

		// Make sure we display all errors that occur during initialization
		error_reporting(error_level: E_ALL);
		@ini_set(option: 'display_errors', value: '1');

		date_default_timezone_set(timezoneId: $defaultTimeZone);

		// Use directory separator from system in documentRoot
		$documentRoot = str_replace(search: '/', replace: DIRECTORY_SEPARATOR, subject: $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

		// Make sure there is only one trailing slash
		$documentRoot = str_replace(search: DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, replace: DIRECTORY_SEPARATOR, subject: $documentRoot);

		// Framework specific paths
		$fwRoot = $documentRoot . 'framework' . DIRECTORY_SEPARATOR;
		$siteRoot = $documentRoot . 'site' . DIRECTORY_SEPARATOR;

		// Initialize autoloader for classes and interfaces
		/** @noinspection PhpIncludeInspection */
		require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'Autoloader.php');
		$autoloader = Autoloader::register($siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'cache.autoload');
		/** @noinspection PhpIncludeInspection */
		require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'AutoloaderPathModel.php');
		$autoloader->addPath(autoloaderPathModel: new AutoloaderPathModel(
			name: 'fw-logic',
			path: $documentRoot,
			mode: AutoloaderPathModel::MODE_NAMESPACE,
			classSuffixList: ['.class.php', '.php', '.interface.php']
		));

		$coreProperties = new CoreProperties(documentRoot: $documentRoot, fwRoot: $fwRoot, siteRoot: $siteRoot);

		return Core::$instance = new Core(autoloader: $autoloader, coreProperties: $coreProperties);
	}

	private function __construct(Autoloader $autoloader, CoreProperties $coreProperties)
	{
		$this->autoloader = $autoloader;
		$this->coreProperties = $coreProperties;
		$this->httpRequest = HttpRequest::getInstance();
		if ($this->httpRequest->getProtocol() === HttpRequest::PROTOCOL_HTTP) {
			$this->redirect($this->httpRequest->getURL(protocol: HttpRequest::PROTOCOL_HTTPS));
		}
	}

	public function prepareHttpResponse(EnvironmentSettingsModel $environmentSettingsModel, ?ExceptionHandler $individualExceptionHandler): void
	{
		if ($this->isHttpResponsePrepared) {
			throw new LogicException(message: 'The response is already prepared');
		}
		$this->isHttpResponsePrepared = true;

		$this->settingsHandler = new SettingsHandler(coreProperties: $this->coreProperties, host: $this->httpRequest->getHost());
		$this->environmentSettingsModel = $environmentSettingsModel;
		$this->logger = new Logger(environmentSettingsModel: $this->environmentSettingsModel, coreProperties: $this->coreProperties);
		set_exception_handler(callback: [
			is_null(value: $individualExceptionHandler) ? new ExceptionHandler(core: $this) : $individualExceptionHandler, 'handleException',
		]);
		$this->errorHandler = new ErrorHandler();
		$this->sessionHandler = AbstractSessionHandler::getSessionHandler(environmentSettingsModel: $this->environmentSettingsModel);

		$this->requestHandler = RequestHandler::init(core: $this);

		$this->localeHandler = LocaleHandler::getInstance();
		$this->localeHandler->init(environmentSettingsModel: $this->environmentSettingsModel, requestHandler: $this->requestHandler);

		$this->contentHandler = ContentHandler::getInstance(core: $this);
		$this->contentHandler->init();
	}

	public function getAutoloader(): Autoloader
	{
		return $this->autoloader;
	}

	public function getCoreProperties(): CoreProperties
	{
		return $this->coreProperties;
	}

	public function getSettingsHandler(): SettingsHandler
	{
		return $this->settingsHandler;
	}

	public function getEnvironmentSettingsModel(): EnvironmentSettingsModel
	{
		return $this->environmentSettingsModel;
	}

	public function getLogger(): Logger
	{
		return $this->logger;
	}

	public function getErrorHandler(): ErrorHandler
	{
		return $this->errorHandler;
	}

	public function getSessionHandler(): AbstractSessionHandler
	{
		return $this->sessionHandler;
	}

	public function getLocaleHandler(): ?LocaleHandler
	{
		return $this->localeHandler;
	}

	public function getContentHandler(): ?ContentHandler
	{
		return $this->contentHandler;
	}

	public function redirect(
		string $relativeOrAbsoluteUri,
		int    $httpStatusCode = HttpStatusCodes::HTTP_SEE_OTHER,
		bool   $setSameSiteCookieTemporaryToLax = false
	): void {
		if ($setSameSiteCookieTemporaryToLax) {
			$this->getSessionHandler()->changeCookieSameSiteToLax();
		}

		HttpResponse::redirectAndExit(
			absoluteUri: $this->generateAbsoluteUri(relativeOrAbsoluteUri: $relativeOrAbsoluteUri),
			httpStatusCode: $httpStatusCode
		);
	}

	public function generateAbsoluteUri(string $relativeOrAbsoluteUri): string
	{
		$c = parse_url(url: $relativeOrAbsoluteUri);

		if (!array_key_exists(key: 'host', array: $c)) {

			if (isset($relativeOrAbsoluteUri[0]) && $relativeOrAbsoluteUri[0] == '/') {
				$directory = '';
			} else if (!str_contains(haystack: $relativeOrAbsoluteUri, needle: '/')) {
				$directory = $this->requestHandler->getRoute();
			} else {
				$directory = dirname(path: $this->httpRequest->getURI());
				$directory = ($directory === '/' || $directory === '\\') ? '/' : $directory . '/';
			}
			$absoluteUri = $this->httpRequest->getProtocol() . '://' . $this->httpRequest->getHost() . $directory . $relativeOrAbsoluteUri;
		} else {
			$absoluteUri = $relativeOrAbsoluteUri;
		}

		if (defined(constant_name: 'SID') && SID !== '') {
			$absoluteUri .= ((preg_match(pattern: '/(.*)\?(.+)/', subject: $absoluteUri)) ? '&' : '?') . SID;
		}

		return $absoluteUri;
	}

	public function sendHttpResponse(): void
	{
		if (!$this->isHttpResponsePrepared) {
			throw new LogicException(message: 'The response is not prepared, call Core->prepareHttpResponse()');
		}

		$contentHandler = $this->contentHandler;
		$contentType = $contentHandler->getContentType();
		if (!$contentHandler->hasContent()) {
			throw new NotFoundException();
		}
		$content = $contentHandler->getContent();
		$httpStatusCode = $contentHandler->getHttpStatusCode();
		if ($contentType === HttpResponse::TYPE_HTML) {
			$cspPolicySettingsModel = $contentHandler->isSuppressCspHeader() ? null : $this->environmentSettingsModel->getCspPolicySettingsModel();
			$httpResponse = HttpResponse::createHtmlResponse(
				httpStatusCode: $httpStatusCode,
				htmlContent: $content,
				cspPolicySettingsModel: $cspPolicySettingsModel,
				nonce: CspNonce::get()
			);
		} else {
			$httpResponse = HttpResponse::createResponseFromString(
				httpStatusCode: $httpStatusCode,
				contentString: $content,
				contentType: $contentType
			);
		}
		$httpResponse->sendAndExit();
	}
}