<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use Exception;
use LogicException;
use framework\exception\NotFoundException;
use framework\session\AbstractSessionHandler;
use stdClass;

class RequestHandler
{
	private static ?RequestHandler $instance = null;
	private bool $isInitialized = false;

	private array $pathParts;
	private int $countPathParts;
	private string $fileName;
	private array $defaultRoutes;
	private ?string $route;
	private ?string $fileGroup = null;
	private array $routeVariables = [];
	private ?string $area = null;
	private ?string $areaDir = null;
	private string $defaultFileName = 'home.html';
	private ?string $acceptedExtension = HttpResponse::TYPE_HTML;
	private ?string $language = null;
	private ?string $contentType = null;
	private ?string $fileTitle = null;
	private ?array $pathVars = null;
	private ?string $fileExtension = null;
	private bool $displayHelpContent = false;

	public static function getInstance(): RequestHandler
	{
		if (is_null(RequestHandler::$instance)) {
			RequestHandler::$instance = new RequestHandler();
		}

		return RequestHandler::$instance;
	}

	private function __construct() { }

	public function init(Core $core)
	{
		if ($this->isInitialized) {
			throw new LogicException('RequestHandler is already initialized');
		}
		$this->isInitialized = true;
		$httpRequest = $core->getHttpRequest();
		$environmentSettingsModel = $core->getEnvironmentSettingsModel();
		$settingsHandler = $core->getSettingsHandler();
		$coreProperties = $core->getCoreProperties();
		$sessionHandler = $core->getSessionHandler();

		$this->checkDomain($httpRequest, $environmentSettingsModel->getAllowedDomains());
		$this->pathParts = explode('/', $httpRequest->getPath());
		$this->countPathParts = count($this->pathParts);
		$this->fileName = trim($this->pathParts[$this->countPathParts - 1]);
		$routeSettings = $settingsHandler->get('routes');
		$this->defaultRoutes = $this->initDefaultRoutes($routeSettings, $environmentSettingsModel);
		$this->route = $this->initRoute($this->countPathParts, $httpRequest, $routeSettings, $core);
		if (is_null($this->route)) {
			throw new NotFoundException($core, true);
		}
		$this->initPropertiesFromRouteSettings($routeSettings->routes->{$this->route}, $coreProperties, $environmentSettingsModel, $sessionHandler);
		$this->setFileProperties();
		if ($this->fileExtension !== $this->acceptedExtension) {
			throw new NotFoundException($core, true);
		}
	}

	private function checkDomain(HttpRequest $httpRequest, array $allowedDomains): void
	{
		$host = $httpRequest->getHost();

		if (count($allowedDomains) === 0) {
			throw new Exception('Please define at least one allowed domain');
		}

		if (!in_array($host, $allowedDomains)) {
			throw new Exception($host . ' is not set as allowed domain');
		}
	}

	private function initDefaultRoutes(stdClass $routeSettings, EnvironmentSettingsModel $environmentSettingsModel): array
	{
		$defaultRoutes = [];

		foreach ((array)$routeSettings->default as $langCode => $defaultRoute) {
			if (array_key_exists($langCode, $environmentSettingsModel->getAvailableLanguages())) {
				$defaultRoutes[$langCode] = $defaultRoute;
			}
		}

		// Make sure there is at least one default route
		if (count($defaultRoutes) === 0) {
			throw new Exception('There must be at least one default route with a language that is allowed by this domain');
		}

		return $defaultRoutes;
	}

	private function initRoute(int $countPathParts, HttpRequest $httpRequest, stdClass $routeSettings, Core $core): ?string
	{
		$countDirectories = $countPathParts - 2;
		$route = '/';
		for ($x = 1; $x <= $countDirectories; $x++) {
			$route .= $this->pathParts[$x] . '/';
		}

		if (isset($routeSettings->routes->{$route})) {
			return $route;
		}

		$requestedPath = $httpRequest->getPath();
		$routes = (array)$routeSettings->routes;
		foreach ($routes as $dynamicRoute => $dynamicRouteSettings) {
			if (preg_match_all('#\${+(.*?)}#', $dynamicRoute, $matches1) === 0) {
				continue;
			}
			$pattern = '#^' . str_replace($matches1[0], '(.*)?', $dynamicRoute) . '#';
			if (preg_match($pattern, $requestedPath, $matches2) === 0) {
				continue;
			}
			foreach ($matches1[1] as $nr => $variableName) {
				$nr = $nr + 1;
				$val = isset($matches2[$nr]) ? $matches2[$nr] : '';
				if ($variableName === 'fileName') {
					$this->fileName = $val;
				} else if ($variableName == 'fileGroup') {
					$this->fileGroup = $val;
				} else {
					$this->routeVariables[$variableName] = $val;
				}
			}

			return $dynamicRoute;
		}

		if ($httpRequest->getURI() === '/') {
			$defaultRoutes = (array)$routeSettings->default;
			$preferredLanguage = $core->getSessionHandler()->getPreferredLanguage();
			if (!is_null($preferredLanguage)) {
				foreach ($defaultRoutes as $language => $defaultRoute) {
					if ($language === $preferredLanguage) {
						$core->redirect($defaultRoute);
					}
				}
			}

			foreach ($httpRequest->getLanguages() as $language) {
				if (array_key_exists($language, $defaultRoutes)) {
					$core->redirect($defaultRoutes[$language]);
				}
			}

			// Redirect to first default route if none is available in accepted languages
			$core->redirect(current($defaultRoutes));
		}

		return null;
	}

	private function initPropertiesFromRouteSettings(stdClass $routeSettings, CoreProperties $coreProperties, EnvironmentSettingsModel $environmentSettingsModel, AbstractSessionHandler $sessionHandler): void
	{
		// Force fileGroup if defined
		if (isset($routeSettings->fileGroup) && $routeSettings->fileGroup !== '') {
			$this->fileGroup = $routeSettings->fileGroup;
		}

		// Force filename if defined
		if (isset($routeSettings->forceFilename) && $routeSettings->forceFilename !== '') {
			$this->fileName = $routeSettings->forceFilename;
		}

		$this->area = $routeSettings->area;
		$this->areaDir = $coreProperties->getSiteContentDir() . $routeSettings->area . '/';
		$this->defaultFileName = $routeSettings->defaultFileName ?? $this->defaultFileName;
		$this->acceptedExtension = $routeSettings->acceptedExtension ?? $this->acceptedExtension;
		$this->language = $routeSettings->language ?? key($environmentSettingsModel->getAvailableLanguages());
		if ($sessionHandler->getPreferredLanguage() !== $this->language) {
			$sessionHandler->setPreferredLanguage($this->language);
		}
		$this->contentType = $routeSettings->contentType ?? null;
		$this->displayHelpContent = $routeSettings->displayHelpContent ?? ($routeSettings->area === 'api');
	}

	private function setFileProperties(): void
	{
		$fileName = (trim($this->fileName) === '') ? $this->defaultFileName : $this->fileName;
		$dotPos = strripos($fileName, '.');
		if ($dotPos === false) {
			$length = strlen($fileName);
			$fileExtension = '';
		} else {
			$length = $dotPos;
			$fileExtension = substr($fileName, $length + 1);
		}
		$fnArr = str_replace('__DASH__', '-', explode('-', substr($fileName, 0, $length)));
		$this->fileName = $fileName;
		$this->fileTitle = $fnArr[0];
		$this->pathVars = $fnArr;
		$this->fileExtension = $fileExtension;
	}

	public function getPathParts(): array
	{
		return $this->pathParts;
	}

	public function getCountPathParts(): int
	{
		return $this->countPathParts;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getDefaultRoutes(): array
	{
		return $this->defaultRoutes;
	}

	public function getRoute(): ?string
	{
		return $this->route;
	}

	public function getFileGroup(): ?string
	{
		return $this->fileGroup;
	}

	public function getRouteVariables(): array
	{
		return $this->routeVariables;
	}

	public function getArea(): ?string
	{
		return $this->area;
	}

	public function getAreaDir(): ?string
	{
		return $this->areaDir;
	}

	public function getDefaultFileName(): string
	{
		return $this->defaultFileName;
	}

	public function getAcceptedExtension(): ?string
	{
		return $this->acceptedExtension;
	}

	public function getLanguage(): ?string
	{
		return $this->language;
	}

	public function getContentType(): ?string
	{
		return $this->contentType;
	}

	public function getFileTitle(): ?string
	{
		return $this->fileTitle;
	}

	public function getPathVars(): ?array
	{
		return $this->pathVars;
	}

	public function getFileExtension(): ?string
	{
		return $this->fileExtension;
	}

	public function isDisplayHelpContent(): bool
	{
		return $this->displayHelpContent;
	}
}