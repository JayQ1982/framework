<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\auth\AccessRightCollection;
use framework\auth\AuthUser;
use framework\auth\UnauthorizedAccessRightException;
use framework\auth\UnauthorizedIpAddressException;
use framework\common\JsonUtils;
use framework\common\SimpleXMLExtended;
use framework\datacheck\Sanitizer;
use framework\exception\NotFoundException;
use framework\response\HttpErrorResponseContent;
use framework\response\HttpSuccessResponseContent;
use LogicException;
use stdClass;

abstract class BaseView
{
	protected function __construct(
		string                                    $requiredViewGroupName,
		array                                     $ipWhitelist,
		private readonly ?AuthUser                $authUser,
		AccessRightCollection                     $requiredAccessRights,
		private readonly InputParameterCollection $inputParameterCollection
	) {
		$viewGroup = RequestHandler::get()->route->viewGroup;
		if ($viewGroup !== $requiredViewGroupName) {
			throw new LogicException(message: 'View group needs to be ' . $requiredViewGroupName . ' instead of ' . $viewGroup);
		}
		if (count(value: $ipWhitelist) > 0 && !in_array(needle: HttpRequest::getRemoteAddress(), haystack: $ipWhitelist)) {
			throw new UnauthorizedIpAddressException();
		}
		if (
			!$requiredAccessRights->isEmpty()
			&& (is_null(value: $this->authUser) || !$authUser->hasOneOfRights(accessRightCollection: $requiredAccessRights))
		) {
			throw new UnauthorizedAccessRightException();
		}
		foreach ($this->inputParameterCollection->listRequiredParameters() as $inputParameter) {
			$name = $inputParameter->name;
			$paramValue = HttpRequest::getInputValue(keyName: $name);
			if (
				is_null(value: $paramValue)
				|| (!is_array(value: $paramValue) && trim(string: $paramValue) === '')
				|| (is_array(value: $paramValue) && count(value: $paramValue) === 0)
			) {
				if (ContentHandler::get()->getContentType()->isHtml()) {
					throw new NotFoundException();
				}
				$this->setErrorResponseContent(errorMessage: 'missing or empty mandatory parameter: ' . $name);

				return;
			}
		}
	}

	protected function setContentType(ContentType $contentType): void
	{
		ContentHandler::get()->setContentType(contentType: $contentType);
	}

	protected function setContent(string $contentString): void
	{
		ContentHandler::get()->setContent(contentString: $contentString);
	}

	abstract public function execute(): void;

	protected function getPathVar(int $nr): ?string
	{
		$pathVars = RequestHandler::get()->pathVars;

		return array_key_exists(key: $nr, array: $pathVars) ? trim(string: $pathVars[$nr]) : null;
	}

	private function onlyDefinedInputParametersAllowed(string $parameterName): void
	{
		if (!$this->inputParameterCollection->hasParameter(name: $parameterName)) {
			throw new LogicException(message: 'Access to not defined input parameter "' . $parameterName . '"');
		}
	}

	public function getInputString(string $keyName): ?string
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return HttpRequest::getInputString(keyName: $keyName);
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

		return HttpRequest::getInputInteger(keyName: $keyName);
	}

	public function getInputFloat(string $keyName): ?float
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return HttpRequest::getInputFloat(keyName: $keyName);
	}

	public function getInputArray(string $keyName): ?array
	{
		$this->onlyDefinedInputParametersAllowed(parameterName: $keyName);

		return HttpRequest::getInputArray(keyName: $keyName);
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
		$contentType = ContentHandler::get()->getContentType();
		if ($contentType->isJson()) {
			$httpErrorResponseContent = HttpErrorResponseContent::createJsonResponseContent(
				errorMessage: $errorMessage,
				errorCode: $errorCode,
				additionalInfo: $additionalInfo
			);
		} else if ($contentType->isTxt() || $contentType->isCsv()) {
			$httpErrorResponseContent = HttpErrorResponseContent::createTextResponseContent(
				errorMessage: $errorMessage,
				errorCode: $errorCode
			);
		} else {
			throw new LogicException(message: 'Invalid contentType: ' . $contentType->type);
		}

		$this->setContent(contentString: $httpErrorResponseContent->getContent());
	}

	protected function setSuccessResponseContent(stdClass $resultDataObject = new stdClass()): void
	{
		$contentType = ContentHandler::get()->getContentType();
		if ($contentType->isJson()) {
			$httpSuccessResponseContent = HttpSuccessResponseContent::createJsonResponseContent(resultDataObject: $resultDataObject);
		} else if ($contentType->isTxt() || $contentType->isCsv()) {
			$httpSuccessResponseContent = HttpSuccessResponseContent::createTextResponseContent(resultDataObject: $resultDataObject);
		} else {
			throw new LogicException(message: 'Invalid contentType: ' . $contentType->type);
		}

		$this->setContent(contentString: $httpSuccessResponseContent->getContent());
	}
}