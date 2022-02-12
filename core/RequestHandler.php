<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use framework\datacheck\Sanitizer;
use framework\exception\NotFoundException;
use framework\session\AbstractSessionHandler;
use LogicException;
use stdClass;

class RequestHandler
{
	private static RequestHandler $instance;

	private array $pathParts;
	private int $countPathParts;
	private string $fileName;
	private array $defaultRoutes;
	private ?string $route;
	private ?string $fileGroup = null;
	private array $routeVariables = [];
	private ?string $area = null;
	private ?string $areaDir = null;
	private string $defaultFileName = '';
	private ?string $acceptedExtension = HttpResponse::TYPE_HTML;
	private ?string $language = null;
	private ?string $contentType = null;
	private ?string $fileTitle = null;
	private ?array $pathVars = null;
	private ?string $fileExtension = null;

	public static function getInstance(): RequestHandler
	{
		return RequestHandler::$instance;
	}

	private function __construct() { }

	public static function init(Core $core): RequestHandler
	{
		if (isset(RequestHandler::$instance)) {
			throw new LogicException(message: 'RequestHandler is already initialized');
		}
		$environmentSettingsModel = $core->getEnvironmentSettingsModel();
		$settingsHandler = $core->getSettingsHandler();
		$coreProperties = $core->getCoreProperties();
		$sessionHandler = $core->getSessionHandler();

		$requestHandler = RequestHandler::$instance = new RequestHandler();
		$requestHandler->checkDomain($environmentSettingsModel->getAllowedDomains());
		$requestHandler->pathParts = explode(separator: '/', string: Httprequest::getPath());
		$requestHandler->countPathParts = count(value: $requestHandler->pathParts);
		$requestHandler->fileName = Sanitizer::trimmedString(input: $requestHandler->pathParts[$requestHandler->countPathParts - 1]);
		$routeSettings = $settingsHandler->get(property: 'routes');
		$requestHandler->defaultRoutes = $requestHandler->initDefaultRoutes(routeSettings: $routeSettings, environmentSettingsModel: $environmentSettingsModel);
		$requestHandler->route = $requestHandler->initRoute(
			countPathParts: $requestHandler->countPathParts,
			routeSettings: $routeSettings,
			core: $core
		);
		if (is_null($requestHandler->route)) {
			throw new NotFoundException();
		}
		$requestHandler->initPropertiesFromRouteSettings($routeSettings->routes->{$requestHandler->route}, $coreProperties, $environmentSettingsModel, $sessionHandler);
		$requestHandler->setFileProperties();
		if (!is_null($requestHandler->acceptedExtension) && $requestHandler->fileExtension !== $requestHandler->acceptedExtension) {
			throw new NotFoundException();
		}

		return $requestHandler;
	}

	private function checkDomain(array $allowedDomains): void
	{
		$host = HttpRequest::getHost();

		if (count(value: $allowedDomains) === 0) {
			throw new Exception(message: 'Please define at least one allowed domain');
		}

		if (!in_array(needle: $host, haystack: $allowedDomains)) {
			throw new Exception(message: $host . ' is not set as allowed domain');
		}
	}

	private function initDefaultRoutes(stdClass $routeSettings, EnvironmentSettingsModel $environmentSettingsModel): array
	{
		$defaultRoutes = [];

		foreach ((array)$routeSettings->default as $langCode => $defaultRoute) {
			if (array_key_exists(key: $langCode, array: $environmentSettingsModel->getAvailableLanguages())) {
				$defaultRoutes[$langCode] = $defaultRoute;
			}
		}

		// Make sure there is at least one default route
		if (count(value: $defaultRoutes) === 0) {
			throw new Exception(message: 'There must be at least one default route with a language that is allowed by this domain');
		}

		return $defaultRoutes;
	}

	private function initRoute(int $countPathParts, stdClass $routeSettings, Core $core): ?string
	{
		$countDirectories = $countPathParts - 2;
		$route = '/';
		for ($x = 1; $x <= $countDirectories; $x++) {
			$route .= $this->pathParts[$x] . '/';
		}

		if (isset($routeSettings->routes->{$route})) {
			return $route;
		}

		$requestedPath = Httprequest::getPath();
		$routes = (array)$routeSettings->routes;
		foreach ($routes as $dynamicRoute => $dynamicRouteSettings) {
			if (preg_match_all(
					pattern: '#\${+(.*?)}#',
					subject: $dynamicRoute,
					matches: $matches1
				) === 0) {
				continue;
			}
			$pattern = '#^' . str_replace(search: $matches1[0], replace: '(.*?)', subject: $dynamicRoute) . '$#';
			if (preg_match(
					pattern: $pattern,
					subject: $requestedPath,
					matches: $matches2
				) === 0) {
				continue;
			}
			foreach ($matches1[1] as $nr => $variableName) {
				$nr = $nr + 1;
				$val = $matches2[$nr] ?? '';
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

		if (HttpRequest::getURI() === '/') {
			$defaultRoutes = (array)$routeSettings->default;
			$preferredLanguage = $core->getSessionHandler()->getPreferredLanguage();
			if (!is_null(value: $preferredLanguage)) {
				foreach ($defaultRoutes as $language => $defaultRoute) {
					if ($language === $preferredLanguage) {
						$core->redirect(relativeOrAbsoluteUri: $defaultRoute);
					}
				}
			}

			foreach (Httprequest::getLanguages() as $language) {
				if (array_key_exists(key: $language, array: $defaultRoutes)) {
					$core->redirect(relativeOrAbsoluteUri: $defaultRoutes[$language]);
				}
			}

			// Redirect to first default route if none is available in accepted languages
			$core->redirect(relativeOrAbsoluteUri: current(array: $defaultRoutes));
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
		if (isset($routeSettings->forceFileName) && $routeSettings->forceFileName !== '') {
			$this->fileName = $routeSettings->forceFileName;
		}

		$this->area = $routeSettings->area;
		$this->areaDir = $coreProperties->getSiteContentDir() . $routeSettings->area . '/';
		$this->defaultFileName = $routeSettings->defaultFileName ?? $this->defaultFileName;
		if (property_exists($routeSettings, 'acceptedExtension')) {
			$this->acceptedExtension = $routeSettings->acceptedExtension;
		}
		$this->language = $routeSettings->language ?? key($environmentSettingsModel->getAvailableLanguages());
		if ($sessionHandler->getPreferredLanguage() !== $this->language) {
			$sessionHandler->setPreferredLanguage(language: $this->language);
		}
		$this->contentType = $routeSettings->contentType ?? null;
	}

	private function setFileProperties(): void
	{
		$fileName = (trim(string: $this->fileName) === '') ? $this->defaultFileName : $this->fileName;
		$dotPos = strripos(haystack: $fileName, needle: '.');
		if ($dotPos === false) {
			$length = strlen(string: $fileName);
			$fileExtension = '';
		} else {
			$length = $dotPos;
			$fileExtension = substr(string: $fileName, offset: $length + 1);
		}
		$fnArr = str_replace(
			search: '__DASH__',
			replace: '-',
			subject: explode(separator: '-', string: substr(string: $fileName, offset: 0, length: $length))
		);
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
}