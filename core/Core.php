<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use LogicException;
use framework\autoloader\Autoloader;
use framework\autoloader\AutoloaderPathModel;
use framework\exception\ExceptionHandler;
use framework\exception\NotFoundException;
use framework\security\CspNonce;
use framework\session\AbstractSessionHandler;

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
		if (!is_null(Core::$instance)) {
			throw new LogicException('Core is already initialized');
		}

		// Make sure we display all errors that occur during initialization
		error_reporting(E_ALL);
		@ini_set('display_errors', '1');

		date_default_timezone_set($defaultTimeZone);

		// Use directory separator from system in documentRoot
		$documentRoot = str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

		// Make sure there is only one trailing slash
		$documentRoot = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $documentRoot);

		// Framework specific paths
		$fwRoot = $documentRoot . 'framework' . DIRECTORY_SEPARATOR;
		$siteRoot = $documentRoot . 'site' . DIRECTORY_SEPARATOR;

		// Initialize autoloader for classes and interfaces
		/** @noinspection PhpIncludeInspection */
		require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'Autoloader.php');
		$autoloader = Autoloader::register($siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'cache.autoload');
		/** @noinspection PhpIncludeInspection */
		require_once($fwRoot . 'autoloader' . DIRECTORY_SEPARATOR . 'AutoloaderPathModel.php');
		$autoloader->addPath(new AutoloaderPathModel(
			'fw-logic',
			$documentRoot,
			AutoloaderPathModel::MODE_NAMESPACE,
			['.class.php', '.php', '.interface.php']
		));

		$coreProperties = new CoreProperties($documentRoot, $fwRoot, $siteRoot);

		return Core::$instance = new Core($autoloader, $coreProperties);
	}

	private function __construct(Autoloader $autoloader, CoreProperties $coreProperties)
	{
		$this->autoloader = $autoloader;
		$this->coreProperties = $coreProperties;
		$this->httpRequest = new HttpRequest();
		if ($this->httpRequest->getProtocol() === HttpRequest::PROTOCOL_HTTP) {
			$this->redirect($this->httpRequest->getURL(HttpRequest::PROTOCOL_HTTPS));
		}
	}

	public function prepareHttpResponse(EnvironmentSettingsModel $environmentSettingsModel, ?ExceptionHandler $individualExceptionHandler): void
	{
		if ($this->isHttpResponsePrepared) {
			throw new LogicException('The response is already prepared');
		}
		$this->isHttpResponsePrepared = true;

		$this->settingsHandler = new SettingsHandler($this->coreProperties, $this->httpRequest->getHost());
		$this->environmentSettingsModel = $environmentSettingsModel;
		$this->logger = new Logger($this->environmentSettingsModel, $this->coreProperties);
		set_exception_handler([is_null($individualExceptionHandler) ? new ExceptionHandler($this) : $individualExceptionHandler, 'handleException']);
		$this->errorHandler = new ErrorHandler($this);
		$this->sessionHandler = AbstractSessionHandler::getSessionHandler($this->environmentSettingsModel, $this->httpRequest);

		$this->requestHandler = RequestHandler::getInstance();
		$this->requestHandler->init($this);

		$this->localeHandler = LocaleHandler::getInstance();
		$this->localeHandler->init($this->environmentSettingsModel, $this->requestHandler);

		if ($this->settingsHandler->exists('autoloader')) {
			foreach ((array)$this->settingsHandler->get('autoloader') as $library => $settings) {
				$this->autoloader->addPath(new AutoloaderPathModel(
					$library,
					$settings->path,
					$settings->mode,
					$settings->class_suffix
				));
			}
		}
		$this->contentHandler = ContentHandler::getInstance($this);
		$this->contentHandler->init();
	}

	public function getCoreProperties(): CoreProperties
	{
		return $this->coreProperties;
	}

	public function getHttpRequest(): HttpRequest
	{
		return $this->httpRequest;
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

	public function getRequestHandler(): RequestHandler
	{
		return $this->requestHandler;
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
		int $httpStatusCode = HttpStatusCodes::HTTP_SEE_OTHER,
		bool $setSameSiteCookieTemporaryToLax = false
	): void {
		if ($setSameSiteCookieTemporaryToLax) {
			$this->getSessionHandler()->changeCookieSameSiteToLax();
		}

		HttpResponse::redirectAndExit($this->generateAbsoluteUri($relativeOrAbsoluteUri), $httpStatusCode);
	}

	public function generateAbsoluteUri(string $relativeOrAbsoluteUri): string
	{
		$c = parse_url($relativeOrAbsoluteUri);

		if (!array_key_exists('host', $c)) {

			if (isset($relativeOrAbsoluteUri[0]) && $relativeOrAbsoluteUri[0] == '/') {
				$directory = '';
			} else if (!str_contains($relativeOrAbsoluteUri, '/')) {
				$directory = $this->requestHandler->getRoute();
			} else {
				$directory = dirname($this->httpRequest->getURI());
				$directory = ($directory === '/' || $directory === '\\') ? '/' : $directory . '/';
			}
			$absoluteUri = $this->httpRequest->getProtocol() . '://' . $this->httpRequest->getHost() . $directory . $relativeOrAbsoluteUri;
		} else {
			$absoluteUri = $relativeOrAbsoluteUri;
		}

		if (defined('SID') && SID !== '') {
			$absoluteUri .= ((preg_match('/(.*)\?(.+)/', $absoluteUri)) ? '&' : '?') . SID;
		}

		return $absoluteUri;
	}

	public function sendHttpResponse(): void
	{
		if (!$this->isHttpResponsePrepared) {
			throw new LogicException('The response is not prepared, call Core->prepareHttpResponse()');
		}

		$contentHandler = $this->contentHandler;
		$contentType = $contentHandler->getContentType();
		if (!$contentHandler->hasContent()) {
			throw new NotFoundException($this, false);
		}
		$content = $contentHandler->getContent();
		$httpStatusCode = $contentHandler->getHttpStatusCode();
		if ($contentType === HttpResponse::TYPE_HTML) {
			$cspPolicySettingsModel = $contentHandler->isSuppressCspHeader() ? null : $this->environmentSettingsModel->getCspPolicySettingsModel();
			$httpResponse = HttpResponse::createHtmlResponse($httpStatusCode, $content, $cspPolicySettingsModel, CspNonce::get());
		} else {
			$httpResponse = HttpResponse::createResponseFromString($httpStatusCode, $content, $contentType);
		}
		$httpResponse->sendAndExit();
	}
}