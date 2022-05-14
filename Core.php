<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework;

use framework\autoloader\Autoloader;
use framework\autoloader\AutoloaderPathModel;
use framework\core\ContentHandler;
use framework\core\EnvironmentSettingsModel;
use framework\core\ErrorHandler;
use framework\core\HttpRequest;
use framework\core\HttpResponse;
use framework\core\Locale;
use framework\core\Logger;
use framework\core\Request;
use framework\core\RouteCollection;
use framework\exception\ExceptionHandler;
use framework\exception\NotFoundException;
use framework\security\CspNonce;
use framework\session\AbstractSessionHandler;
use LogicException;

class Core
{
	private static Core $instance;
	private static HttpResponse $httpResponse;

	public readonly string $documentRoot;
	public readonly string $frameworkDirectory;
	public readonly string $siteDirectory;
	public readonly string $cacheDirectory;
	public readonly string $logDirectory;
	public readonly string $settingsDirectory;
	public readonly string $viewDirectory;
	private Logger $logger;

	public static function get(): Core
	{
		return Core::$instance;
	}

	public function __construct(
		public readonly string $siteDirectoryName = 'site',
		public readonly string $timezone = 'Europe/Zurich',
		string                 $frameworkFilePathRemove = ''
	) {
		if (isset(Core::$instance)) {
			throw new LogicException(message: 'Core is already initialized. Use Core::get().');
		}
		Core::$instance = $this;
		error_reporting(error_level: E_ALL);
		date_default_timezone_set(timezoneId: $timezone);
		$this->documentRoot = str_replace(
			search: DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
			replace: DIRECTORY_SEPARATOR,
			subject: $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR
		);
		$this->frameworkDirectory = $frameworkDirectory = dirname(path: __FILE__) . DIRECTORY_SEPARATOR;
		$this->siteDirectory = $this->documentRoot . $siteDirectoryName . DIRECTORY_SEPARATOR;
		$this->cacheDirectory = $this->siteDirectory . 'cache' . DIRECTORY_SEPARATOR;
		$this->logDirectory = $this->siteDirectory . 'logs' . DIRECTORY_SEPARATOR;
		$this->settingsDirectory = $this->siteDirectory . 'settings' . DIRECTORY_SEPARATOR;
		$this->viewDirectory = $this->siteDirectory . 'view' . DIRECTORY_SEPARATOR;

		require_once $frameworkDirectory . 'autoloader' . DIRECTORY_SEPARATOR . 'Autoloader.php';
		$autoloader = Autoloader::register(cacheFilePath: $this->siteDirectory . 'cache' . DIRECTORY_SEPARATOR . 'cache.autoload');
		require_once $frameworkDirectory . 'autoloader' . DIRECTORY_SEPARATOR . 'AutoloaderPathModel.php';
		$autoloader->addPath(autoloaderPathModel: new AutoloaderPathModel(
			name: 'fw-logic',
			path: $this->documentRoot,
			mode: AutoloaderPathModel::MODE_NAMESPACE,
			fileSuffixList: ['.class.php', '.php', '.interface.php'],
			phpFilePathRemove: $frameworkFilePathRemove
		));
		ErrorHandler::register();
		if (!HttpRequest::isSSL()) {
			HttpResponse::redirectAndExit(relativeOrAbsoluteUri: HttpRequest::getURL(protocol: HttpRequest::PROTOCOL_HTTPS));
		}
	}

	public function prepareHttpResponse(
		EnvironmentSettingsModel $environmentSettingsModel,
		RouteCollection          $routeCollection,
		?ExceptionHandler        $individualExceptionHandler,
		?AbstractSessionHandler  $individualSessionHandler
	): HttpResponse {
		if (isset(Core::$httpResponse)) {
			throw new LogicException(message: 'The HttpResponse is already prepared');
		}
		$this->logger = new Logger(logEmailRecipient: $environmentSettingsModel->errorLogRecipientEmail, logDirectory: $this->logDirectory);
		ExceptionHandler::register(individualExceptionHandler: $individualExceptionHandler);
		AbstractSessionHandler::register(individualSessionHandler: $individualSessionHandler);
		Request::register(routeCollection: $routeCollection);
		Locale::register();
		$contentHandler = ContentHandler::register();
		if (!$contentHandler->hasContent()) {
			throw new NotFoundException();
		}
		$content = $contentHandler->getContent();
		$httpStatusCode = $contentHandler->getHttpStatusCode();
		$contentType = $contentHandler->getContentType();
		if ($contentType->isHtml()) {
			return Core::$httpResponse = HttpResponse::createHtmlResponse(
				httpStatusCode: $httpStatusCode,
				htmlContent: $content,
				cspPolicySettingsModel: $contentHandler->isSuppressCspHeader() ? null : EnvironmentSettingsModel::get()->cspPolicySettingsModel,
				nonce: CspNonce::get()
			);
		}

		return Core::$httpResponse = HttpResponse::createResponseFromString(
			httpStatusCode: $httpStatusCode,
			contentString: $content,
			contentType: $contentType
		);
	}

	public function getLogger(): Logger
	{
		return $this->logger;
	}
}