<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use framework\auth\Authenticator;
use framework\common\CSVFile;
use framework\common\SimpleXMLExtended;
use framework\common\StringUtils;
use framework\db\FrameworkDB;
use framework\exception\NotFoundException;
use framework\exception\UnauthorizedException;
use framework\html\HtmlDocument;
use framework\response\errorResponseContent;
use framework\response\successResponseContent;
use LogicException;
use stdClass;

abstract class baseView
{
	private Core $core;
	private ?Authenticator $authenticator;
	private ?string $content = null;
	private array $mandatoryParams;
	private array $optionalParams;

	protected function __construct(
		Core $core,
		array $ipWhitelist,
		?Authenticator $authenticator,
		array $requiredAccessRights,
		string $contentDescription = '',
		array $mandatoryParams = [],
		array $optionalParams = [],
		array $returnParams = [],
		?string $individualContentType = null
	) {
		$this->core = $core;
		$this->authenticator = $authenticator;
		$this->mandatoryParams = $mandatoryParams;
		$this->optionalParams = $optionalParams;

		$httpRequest = $core->getHttpRequest();
		$requestHandler = $core->getRequestHandler();

		$outputHelpContent = (!is_null($httpRequest->getInputString('help')) && $requestHandler->isDisplayHelpContent());
		if ($outputHelpContent) {
			$this->setContentType(HttpResponse::TYPE_TXT);
		} else if (!is_null($individualContentType)) {
			$this->setContentType($individualContentType);
		}

		if (count($ipWhitelist) > 0 && !in_array($httpRequest->getRemoteAddress(), $ipWhitelist)) {
			throw new UnauthorizedException();
		}

		if (count($requiredAccessRights) > 0) {
			$authenticator->checkAccess(true, $requiredAccessRights, true);
		}

		if ($outputHelpContent) {
			$this->setHelpContent($contentDescription, $mandatoryParams, $optionalParams, $returnParams, $ipWhitelist, $requiredAccessRights);

			return;
		}

		$this->checkMandatoryParameters($core, $mandatoryParams);
	}

	protected function setContentType(string $contentType): void
	{
		$this->core->getContentHandler()->setContentType($contentType);
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

	public function hasContent(): bool
	{
		return !is_null($this->content);
	}

	protected function setContent(string $contentString): void
	{
		if ($this->hasContent()) {
			throw new LogicException('Content is already set. You are not allowed to overwrite it.');
		}
		$this->content = $contentString;
	}

	public function getContent(): ?string
	{
		return $this->content;
	}

	private function checkMandatoryParameters(Core $core, array $mandatoryParams): void
	{
		$httpRequest = $core->getHttpRequest();
		$contentType = $core->getContentHandler()->getContentType();

		foreach ($mandatoryParams as $mandatoryParam => $paramDescription) {
			$paramValue = $httpRequest->getInputValue($mandatoryParam);
			if ((!is_array($paramValue) && trim($paramValue) === '') || (is_array($paramValue) && count($paramValue) === 0)) {
				if ($contentType === HttpResponse::TYPE_HTML) {
					throw new NotFoundException($core, false);
				}
				$this->setErrorResponseContent('missing or empty mandatory parameter: ' . $mandatoryParam);

				return;
			}
		}
	}

	public function getMandatoryParams(): array
	{
		return $this->mandatoryParams;
	}

	public function getOptionalParams(): array
	{
		return $this->optionalParams;
	}

	abstract public function execute(): void;

	protected function getText(string $fieldName, array $replacements = []): string
	{
		return $this->core->getLocaleHandler()->getText($fieldName, $replacements);
	}

	protected function getAuthenticator(): ?Authenticator
	{
		return $this->authenticator;
	}

	protected function getCore(): Core
	{
		return $this->core;
	}

	protected function getDB(string $name = 'default'): FrameworkDB
	{
		$core = $this->getCore();

		return FrameworkDB::getInstance($core->getEnvironmentHandler(), $core->getRequestHandler()->getLanguage(), $name);
	}

	protected function getPathVar(int $nr): ?string
	{
		$pathVars = $this->core->getRequestHandler()->getPathVars();

		return $pathVars[$nr] ?? null;
	}

	private function onlyDefinedInputParametersAllowed(string $parameterName): void
	{
		if (!array_key_exists($parameterName, $this->mandatoryParams) && !array_key_exists($parameterName, $this->optionalParams)) {
			throw new LogicException('Access to not defined input parameter "' . $parameterName . '"');
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

	protected function setContentByXmlObject(SimpleXMLExtended $xmlObject): void
	{
		$this->setContent($xmlObject->asXML());
	}

	protected function setContentByJsonObject(stdClass $jsonObject): void
	{
		$this->setContent(json_encode($jsonObject));
	}

	protected function setErrorResponseContent(string $errorMessage, null|int|string $errorCode = null, array $additionalInfo = []): void
	{
		$errorResponseContent = new errorResponseContent($this->core->getContentHandler()->getContentType(), $errorMessage, $errorCode, $additionalInfo);
		$this->setContent($errorResponseContent->getContent());
	}

	protected function setSuccessResponseContent(array $resultData = []): void
	{
		$successResponseContent = new successResponseContent($this->core->getContentHandler()->getContentType(), $resultData);
		$this->setContent($successResponseContent->getContent());
	}

	protected function setCsvContent(array $data, string $fileName, bool $forceDownload = false): void
	{
		if ($fileName === '') {
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

	/**
	 * Shortcut for direct access to the HtmlDocument
	 *
	 * @return HtmlDocument
	 */
	protected function getHtmlDocument(): HtmlDocument
	{
		return $this->getCore()->getContentHandler()->getHtmlDocument();
	}
}