<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use LogicException;
use framework\common\CSVFile;
use framework\common\StringUtils;
use framework\response\errorResponseContent;
use framework\response\successResponseContent;

abstract class baseView
{
	private Core $core;
	private array $mandatoryParams;
	private array $optionalParams;
	private array $placeholders = [];
	private array $navigationLevels = [];
	private ?string $content = null;
	private ?string $individualHtmlFileName = null;

	protected function __construct(
		Core $core,
		array $ipWhitelist,
		array $requiredAccessRights,
		string $contentDescription = '',
		array $mandatoryParams = [],
		array $optionalParams = [],
		array $returnParams = [],
		?string $individualContentType = null
	) {
		$this->core = $core;
		$this->mandatoryParams = $mandatoryParams;
		$this->optionalParams = $optionalParams;

		$httpRequest = $core->getHttpRequest();
		$requestHandler = $core->getRequestHandler();
		$errorHandler = $core->getErrorHandler();
		$authenticator = $core->getAuthenticator();
		$contentHandler = $core->getContentHandler();
		$settingsHandler = $core->getSettingsHandler();

		$outputHelpContent = (!is_null($httpRequest->getInputString('help')) && $requestHandler->isDisplayHelpContent());
		if ($outputHelpContent) {
			$this->setContentType($contentHandler, HttpResponse::TYPE_TXT);
		} else if (!is_null($individualContentType)) {
			$this->setContentType($contentHandler, $individualContentType);
		}

		if (!empty($ipWhitelist) && !in_array($httpRequest->getRemoteAddress(), $ipWhitelist)) {
			$errorHandler->display_error(HttpStatusCodes::HTTP_UNAUTHORIZED);
		}

		if (!empty($requiredAccessRights)) {
			$authenticator->checkAccess(true, $requiredAccessRights, true);
		}

		if ($outputHelpContent) {
			$this->setHelpContent($contentDescription, $mandatoryParams, $optionalParams, $returnParams, $ipWhitelist, $requiredAccessRights);

			return;
		}

		$this->checkMandatoryParameters($contentHandler, $httpRequest, $errorHandler, $mandatoryParams);

		$this->placeholders = $contentHandler->getPlaceholders();
		$this->navigationLevels = $contentHandler->getNavigationLevels();

		if ($settingsHandler->exists('versions')) {
			$versions = $settingsHandler->get('versions');

			foreach (get_object_vars($versions) as $key => $val) {
				$this->setPlaceholder($key . 'Version', $val);
			}
		}
	}

	abstract public function execute(): void;

	protected function setContentType(ContentHandler $contentHandler, string $contentType): void
	{
		$contentHandler->setContentType($contentType);
	}

	private function setHelpContent(
		string $contentDescription,
		array $mandatoryParams,
		array $optionalParams,
		array $returnParams,
		array $ipWhitelist,
		array $requiredAccessRights
	): void {
		$helpLines = ['--- HELP ---'];

		if ($contentDescription !== '') {
			$helpLines[] = $contentDescription;
			$helpLines[] = '';
		}

		foreach ($mandatoryParams as $mandatoryParam => $paramDescription) {
			$helpLines[] = "@param string '" . $mandatoryParam . "': (Mandatory) " . $paramDescription;
		}

		foreach ($optionalParams as $optionalParam => $paramDescription) {
			$helpLines[] = "@param string '" . $optionalParam . "': (optional) " . $paramDescription;
		}

		foreach ($returnParams as $returnParam => $paramDescription) {
			$helpLines[] = "@return string '" . $returnParam . "': " . $paramDescription;
		}

		if (!empty($ipWhitelist)) {
			$helpLines[] = '';
			$helpLines[] = 'IP restrictions: ' . implode(', ', $ipWhitelist);
		}

		if (!empty($requiredAccessRights)) {
			$helpLines[] = '';
			$helpLines[] = 'Required access rights: ' . implode(', ', $requiredAccessRights);
		}

		$this->setContent(implode(PHP_EOL, $helpLines));
	}

	protected function setContent(string $contentString): void
	{
		if ($this->hasContent()) {
			throw new LogicException('Content is already set. You are not allowed to overwrite it.');
		}
		$this->content = $contentString;
	}

	public function hasContent(): bool
	{
		return !is_null($this->content);
	}

	private function checkMandatoryParameters(ContentHandler $contentHandler, HttpRequest $httpRequest, ErrorHandler $errorHandler, array $mandatoryParams): void
	{
		$contentType = $contentHandler->getContentType();

		foreach ($mandatoryParams as $mandatoryParam => $paramDescription) {
			$paramValue = $httpRequest->getInputValue($mandatoryParam);
			if ((!is_array($paramValue) && trim($paramValue) === '') || (is_array($paramValue) && count($paramValue) === 0)) {
				if ($contentType === HttpResponse::TYPE_HTML) {
					$errorHandler->display_error(HttpStatusCodes::HTTP_NOT_FOUND);
				}
				$this->setErrorResponseContent('missing or empty mandatory parameter: ' . $mandatoryParam);

				return;
			}
		}
	}

	protected function setErrorResponseContent(string $errorMessage, $errorCode = null, array $additionalInfo = []): void
	{
		$errorResponseContent = new errorResponseContent($this->core->getContentHandler()->getContentType(), $errorMessage, $errorCode, $additionalInfo);
		$this->setContent($errorResponseContent->getContent());
	}

	protected function setSuccessResponseContent(array $result = []): void
	{
		$successResponseContent = new successResponseContent($this->core->getContentHandler()->getContentType(), $result);
		$this->setContent($successResponseContent->getContent());
	}

	public function getIndividualHtmlFileName(): ?string
	{
		return $this->individualHtmlFileName;
	}

	protected function setIndividualHtmlFileName(string $individualHtmlFileName): void
	{
		$this->individualHtmlFileName = $individualHtmlFileName;
	}

	public function getContent(): ?string
	{
		return $this->content;
	}

	public function getPlaceholders(): array
	{
		return $this->placeholders;
	}

	public function getNavigationLevels(): array
	{
		return $this->navigationLevels;
	}

	protected function setIndividualTemplate(string $templateName): void
	{
		$this->core->getContentHandler()->setTemplate($templateName);
	}

	public function getMandatoryParams(): array
	{
		return $this->mandatoryParams;
	}

	public function getOptionalParams(): array
	{
		return $this->optionalParams;
	}

	/**
	 * @param string $key
	 * @param mixed  $val : Usually string, but can also be an array or object (will be handled by template engine rendering)
	 */
	protected function setPlaceholder(string $key, $val): void
	{
		$this->placeholders[$key] = $val;
	}

	protected function checkPlaceholder(string $placeholderName): bool
	{
		return array_key_exists($placeholderName, $this->placeholders);
	}

	protected function getPlaceholder(string $placeholderName)
	{
		return array_key_exists($placeholderName, $this->placeholders) ? $this->placeholders[$placeholderName] : '';
	}

	protected function setNavigationLevel(int $key, string $val): void
	{
		$this->navigationLevels[$key] = $val;
	}

	protected function checkNavigationLevel($key): bool
	{
		return array_key_exists($key, $this->navigationLevels);
	}

	protected function getNavigationLevel($key): string
	{
		return isset($this->navigationLevels[$key]) ? $this->navigationLevels[$key] : '';
	}

	protected function getGenerationTime(): float
	{
		return round(microtime(true) - REQUEST_TIME, 4);
	}

	protected function getCore(): Core
	{
		return $this->core;
	}

	protected function getPathVar(int $nr): ?string
	{
		$pathVars = $this->core->getRequestHandler()->getPathVars();

		return $pathVars[$nr] ?? null;
	}

	private function onlyDefinedInputParametersAllowed(string $parameterName): void
	{
		if (!array_key_exists($parameterName, $this->mandatoryParams) && !array_key_exists($parameterName,
				$this->optionalParams)) {
			throw new LogicException('Access to not defined input parameter "' . $parameterName .
				'"');
		}
	}

	public function getInputString(string $keyName): ?string
	{
		$this->onlyDefinedInputParametersAllowed($keyName);

		return $this->core->getHttpRequest()->getInputString($keyName);
	}

	public function getInputDomain(string $keyName): ?string
	{
		$this->onlyDefinedInputParametersAllowed($keyName);
		$value = $this->getInputString($keyName);
		if (is_null($value)) {
			return null;
		}

		return StringUtils::sanitizeDomain($value);
	}

	public function getInputInteger(string $keyName): ?int
	{
		$this->onlyDefinedInputParametersAllowed($keyName);

		return $this->core->getHttpRequest()->getInputInteger($keyName);
	}

	public function getInputFloat(string $keyName): ?float
	{
		$this->onlyDefinedInputParametersAllowed($keyName);

		return $this->core->getHttpRequest()->getInputFloat($keyName);
	}

	public function getInputArray(string $keyName): ?array
	{
		$this->onlyDefinedInputParametersAllowed($keyName);

		return $this->core->getHttpRequest()->getInputArray($keyName);
	}

	protected function setCsvContent(array $data, string $fileName, bool $forceDownload = false): void
	{
		if (empty($fileName)) {
			$fileName = 'csv_data_' . date('YmdHis') . '.csv';
		}
		$csv = new CSVFile($fileName, true);
		foreach ($data as $rowNumber => $rowData) {
			foreach ((array)$rowData as $cellNumber => $cellContent) {
				$csv->addField($rowNumber, $cellNumber, $cellContent);
			}
		}
		$path = $csv->load();
		$httpResponse = HttpResponse::createResponseFromFilePath($csv->load(), $forceDownload, null, 0);
		if (file_exists($path)) {
			unlink($path);
		}
		$httpResponse->sendAndExit();
	}
}
/* EOF */