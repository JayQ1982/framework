<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use framework\autoloader\Autoloader;
use framework\autoloader\AutoloaderPathModel;
use framework\exception\ExceptionHandler;
use framework\security\CspNonce;

class Core
{
	private CoreProperties $coreProperties;
	private HttpRequest $httpRequest;
	private SettingsHandler $settingsHandler;
	private EnvironmentHandler $environmentHandler;
	private Logger $logger;
	private ExceptionHandler $exceptionHandler;
	private ErrorHandler $errorHandler;
	private SessionHandler $sessionHandler;
	private RequestHandler $requestHandler;
	private LocaleHandler $localeHandler;
	private ?ContentHandler $contentHandler = null;

	public function __construct(string $documentRoot, string $fwRoot, string $siteRoot, Autoloader $autoloader)
	{
		$this->coreProperties = new CoreProperties($documentRoot, $fwRoot, $siteRoot);
		$this->httpRequest = new HttpRequest();
		if ($this->httpRequest->getProtocol() === HttpRequest::PROTOCOL_HTTP) {
			$this->redirect($this->httpRequest->getURL(HttpRequest::PROTOCOL_HTTPS));

			return;
		}

		$this->settingsHandler = new SettingsHandler($this->coreProperties, $this->httpRequest->getHost());
		$this->environmentHandler = new EnvironmentHandler($this->settingsHandler);
		$this->logger = new Logger($this->environmentHandler, $this->coreProperties);
		$this->exceptionHandler = new ExceptionHandler($this);
		$this->errorHandler = new ErrorHandler($this);
		$this->sessionHandler = new SessionHandler($this->environmentHandler, $this->httpRequest);
		$this->sessionHandler->start($this->httpRequest);
		$this->requestHandler = new RequestHandler($this);
		$this->localeHandler = new LocaleHandler($this->environmentHandler, $this->requestHandler);

		if ($this->settingsHandler->exists('autoloader')) {
			foreach ((array)$this->settingsHandler->get('autoloader') as $library => $settings) {
				$autoloader->addPath(new AutoloaderPathModel(
					$library,
					$settings->path,
					$settings->mode,
					$settings->class_suffix
				));
			}
		}
		$this->contentHandler = new ContentHandler($this);
		$this->contentHandler->setContent();
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

	public function getEnvironmentHandler(): EnvironmentHandler
	{
		return $this->environmentHandler;
	}

	public function getLogger(): Logger
	{
		return $this->logger;
	}

	public function getExceptionHandler(): ExceptionHandler
	{
		return $this->exceptionHandler;
	}

	public function getErrorHandler(): ErrorHandler
	{
		return $this->errorHandler;
	}

	public function getSessionHandler(): SessionHandler
	{
		return $this->sessionHandler;
	}

	public function getRequestHandler(): RequestHandler
	{
		return $this->requestHandler;
	}

	public function getLocaleHandler(): LocaleHandler
	{
		return $this->localeHandler;
	}

	public function getContentHandler(): ?ContentHandler
	{
		return $this->contentHandler;
	}

	public function redirect(string $relativeOrAbsoluteUri, int $httpStatusCode = HttpStatusCodes::HTTP_SEE_OTHER): void
	{
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

	public function sendResponse(): void
	{
		$contentHandler = $this->contentHandler;
		$contentType = $contentHandler->getContentType();
		$content = $contentHandler->getContent();
		$httpStatusCode = $contentHandler->getHttpStatusCode();
		if ($contentType === HttpResponse::TYPE_HTML) {
			$cspPolicySettings = $contentHandler->isSuppressCspHeader() ? null : $this->environmentHandler->getCspPolicySettings();
			$httpResponse = HttpResponse::createHtmlResponse($httpStatusCode, $content, $cspPolicySettings, CspNonce::get());
		} else {
			$httpResponse = HttpResponse::createResponseFromString($httpStatusCode, $content, $contentType);
		}
		$httpResponse->sendAndExit();
	}
}