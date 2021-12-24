<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use LogicException;
use framework\auth\Authenticator;
use framework\common\CSVFile;
use framework\common\JsonUtils;
use framework\common\SimpleXMLExtended;
use framework\datacheck\Sanitizer;
use framework\exception\NotFoundException;
use framework\exception\UnauthorizedException;
use framework\response\HttpErrorResponseContent;
use framework\response\HttpSuccessResponseContent;
use stdClass;

abstract class BaseView
{
	public const PARAM_HELP = 'help';
	private Core $core;
	private HttpRequest $httpRequest;
	private ?Authenticator $authenticator;
	private array $mandatoryParams;
	private array $optionalParams;

	protected function __construct(
		Core           $core,
		array          $ipWhitelist,
		?Authenticator $authenticator,
		array          $requiredAccessRights,
		string         $contentDescription = '',
		array          $mandatoryParams = [],
		array          $optionalParams = [],
		array          $returnParams = [],
		?string        $individualContentType = null
	) {
		$this->core = $core;
		$this->httpRequest = HttpRequest::getInstance();
		$this->authenticator = $authenticator;
		$this->mandatoryParams = $mandatoryParams;
		$this->optionalParams = $optionalParams;

		$requestHandler = $core->getRequestHandler();

		$outputHelpContent = (
			!is_null(value: $this->httpRequest->getInputString(keyName: BaseView::PARAM_HELP))
			&& $requestHandler->isDisplayHelpContent()
		);
		if ($outputHelpContent) {
			$this->setContentType(contentType: HttpResponse::TYPE_TXT);
		} else if (!is_null($individualContentType)) {
			$this->setContentType($individualContentType);
		}

		if (count(value: $ipWhitelist) > 0 && !in_array(needle: $this->httpRequest->getRemoteAddress(), haystack: $ipWhitelist)) {
			throw new UnauthorizedException();
		}

		if (count(value: $requiredAccessRights) > 0) {
			$authenticator->checkAccess(
				accessOnlyForLoggedInUsers: true,
				requiredAccessRights: $requiredAccessRights,
				autoRedirect: true
			);
		}

		if ($outputHelpContent) {
			$this->setHelpContent(
				contentDescription: $contentDescription,
				mandatoryParams: $mandatoryParams,
				optionalParams: $optionalParams,
				returnParams: $returnParams,
				ipWhitelist: $ipWhitelist,
				requiredAccessRights: $requiredAccessRights
			);

			return;
		}

		$this->checkMandatoryParameters(core: $core, mandatoryParams: $mandatoryParams);
	}

	protected function setContentType(string $contentType): void
	{
		$this->core->getContentHandler()->setContentType(contentType: $contentType);
	}

	private function setHelpContent(
		string $contentDescription,
		array  $mandatoryParams,
		array  $optionalParams,
		array  $returnParams,
		array  $ipWhitelist,
		array  $requiredAccessRights
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

		$this->setContent(contentString: implode(separator: PHP_EOL, array: $helpLines));
	}

	protected function setContent(string $contentString): void
	{
		$this->getCore()->getContentHandler()->setContent(contentString: $contentString);
	}

	private function checkMandatoryParameters(Core $core, array $mandatoryParams): void
	{
		$httpRequest = $this->httpRequest;
		$contentType = $core->getContentHandler()->getContentType();

		foreach ($mandatoryParams as $mandatoryParam => $paramDescription) {
			$paramValue = $httpRequest->getInputValue(keyName: $mandatoryParam);
			if (
				is_null($paramValue)
				|| (!is_array(value: $paramValue) && Sanitizer::trimmedString(input: $paramValue) === '')
				|| (is_array(value: $paramValue) && count(value: $paramValue) === 0)
			) {
				if ($contentType === HttpResponse::TYPE_HTML) {
					throw new NotFoundException();
				}
				$this->setErrorResponseContent(errorMessage: 'missing or empty mandatory parameter: ' . $mandatoryParam);

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

	protected function getPathVar(int $nr): ?string
	{
		$pathVars = $this->core->getRequestHandler()->getPathVars();

		return $pathVars[$nr] ?? null;
	}

	private function onlyDefinedInputParametersAllowed(string $parameterName): void
	{
		if (!array_key_exists(key: $parameterName, array: $this->mandatoryParams) && !array_key_exists(key: $parameterName, array: $this->optionalParams)) {
			throw new LogicException(message: 'Access to not defined input parameter "' . $parameterName . '"');
		}
	}

	public function getInputString(string $keyName): ?string
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return $this->httpRequest->getInputString(keyName: $keyName);
	}

	public function getInputDomain(string $keyName): ?string
	{
		$this->onlyDefinedInputParametersAllowed($keyName);
		$value = $this->getInputString(keyName: $keyName);
		if (is_null(value: $value)) {
			return null;
		}

		return Sanitizer::domain(input: $value);
	}

	public function getInputInteger(string $keyName): ?int
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return $this->httpRequest->getInputInteger(keyName: $keyName);
	}

	public function getInputFloat(string $keyName): ?float
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return $this->httpRequest->getInputFloat(keyName: $keyName);
	}

	public function getInputArray(string $keyName): ?array
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return $this->httpRequest->getInputArray(keyName: $keyName);
	}

	protected function setContentByXmlObject(SimpleXMLExtended $xmlObject): void
	{
		$this->setContent(contentString: $xmlObject->asXML());
	}

	protected function setContentByJsonObject(stdClass $jsonObject): void
	{
		$this->setContent(contentString: JsonUtils::convertToJsonString(valueToConvert: $jsonObject));
	}

	protected function setErrorResponseContent(string $errorMessage, null|int|string $errorCode = null, ?stdClass $additionalInfo = null): void
	{
		$contentType = $this->core->getContentHandler()->getContentType();
		$httpErrorResponseContent = match ($contentType) {
			HttpResponse::TYPE_JSON => HttpErrorResponseContent::createJsonResponseContent(
				errorMessage: $errorMessage,
				errorCode: $errorCode,
				additionalInfo: $additionalInfo
			),
			HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV => HttpErrorResponseContent::createTextResponseContent(
				errorMessage: $errorMessage,
				errorCode: $errorCode
			),
			default => throw new LogicException('Invalid contentType: ' . $contentType),
		};

		$this->setContent(contentString: $httpErrorResponseContent->getContent());
	}

	protected function setSuccessResponseContent(?stdClass $resultDataObject = null): void
	{
		if (is_null($resultDataObject)) {
			// @todo: With PHP 8.1 it is possible to set stdClass instead of null as default value for the resultDataObject argument
			$resultDataObject = new stdClass();
		}

		$contentType = $this->core->getContentHandler()->getContentType();
		$httpSuccessResponseContent = match ($contentType) {
			HttpResponse::TYPE_JSON => HttpSuccessResponseContent::createJsonResponseContent(resultDataObject: $resultDataObject),
			HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV => HttpSuccessResponseContent::createTextResponseContent(resultDataObject: $resultDataObject),
			default => throw new LogicException('Invalid contentType: ' . $contentType),
		};

		$this->setContent(contentString: $httpSuccessResponseContent->getContent());
	}

	protected function setCsvContent(array $data, string $fileName, bool $forceDownload = false): void
	{
		if ($fileName === '') {
			$fileName = 'csv_data_' . date(format: 'YmdHis') . '.csv';
		}

		$csv = new CSVFile(fileName: $fileName, utf8Encode: true);
		foreach ($data as $rowNumber => $rowData) {
			foreach ((array)$rowData as $cellNumber => $cellContent) {
				$csv->addField(rowNumber: $rowNumber, colName: $cellNumber, content: $cellContent);
			}
		}
		$path = $csv->load();
		$httpResponse = HttpResponse::createResponseFromFilePath(
			absolutePathToFile: $csv->load(),
			forceDownload: $forceDownload,
			individualFileName: null,
			maxAge: 0
		);
		if (file_exists(filename: $path)) {
			unlink(filename: $path);
		}
		$httpResponse->sendAndExit();
	}
}