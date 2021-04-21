<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api;

use LogicException;
use framework\api\response\curlResponse;
use SimpleXMLElement;
use stdClass;

class FrameworkAPI
{
	/** @var FrameworkAPI[] */
	private static array $instances = [];

	private ?string $lastErrMsg = null;
	private string $url;
	private bool $useSsl;
	private ?string $httpAuthString;

	public function __construct(string $uniqueIdentifier, string $url, bool $useSsl = true, ?string $httpAuthString = null)
	{
		if (array_key_exists($uniqueIdentifier, FrameworkAPI::$instances)) {
			throw new LogicException('It is not allowed to instantiate this class multiple times with the same unique identifier ' . $uniqueIdentifier);
		}

		$this->url = $url;
		$this->useSsl = $useSsl;
		$this->httpAuthString = $httpAuthString;

		FrameworkAPI::$instances[$uniqueIdentifier] = $this;
	}

	public function curlRequest(
		string $path,
		array|string|null $postData = null,
		string $expectedResponseFormat = curlResponse::RESPONSE_OBJECT,
		int $requestTimeoutInSeconds = 10,
		array $curlOptions = [],
		bool $returnFalseOnError = true
	): false|string|stdClass|curlRequest|SimpleXMLElement {
		$url = $this->url . $path;
		$curlRequest = $this->prepareRequest($url, $postData);
		$curlRequest->setRequestTimeoutInSeconds($requestTimeoutInSeconds);
		foreach ($curlOptions as $key => $val) {
			$curlRequest->setCurlOption($key, $val);
		}

		if (!$this->useSsl) {
			$curlRequest->disableSSLcheck();
		}

		if (!is_null($this->httpAuthString)) {
			$curlRequest->httpAuth($this->httpAuthString);
		}

		$executionResult = $curlRequest->execute();
		if ($executionResult === false) {
			$this->lastErrMsg = $curlRequest->getLastErrMsg();

			if ($returnFalseOnError) {
				return false;
			}
		}

		$expectedResponseFormat = mb_strtolower($expectedResponseFormat);
		switch ($expectedResponseFormat) {
			case curlResponse::RESPONSE_OBJECT:
				return $curlRequest;
			case curlResponse::RESPONSE_XML:
				$result = $curlRequest->getResponseBodyAsXml();
				break;
			case curlResponse::RESPONSE_JSON:
				$result = $curlRequest->getResponseBodyAsJson();
				break;
			default:
				$result = $curlRequest->getRawResponseBody();
				break;
		}
		if ($result === false) {
			$this->lastErrMsg = $curlRequest->getLastErrMsg();

			if ($returnFalseOnError) {
				return false;
			}
		}

		return $result;
	}

	public function getLastErrMsg(): ?string
	{
		return $this->lastErrMsg;
	}

	protected function prepareRequest(string $url, array|stdClass|string|null $postData = null): curlRequest
	{
		if (is_null($postData)) {
			return $this->prepareGetRequest($url);
		}

		// If $postData is an array or object, it will automatically be converted into a url-encoded query string and sent with content type 'text/plan'
		if (is_array($postData)) {
			return $this->preparePostRequestFromArray($url, $postData);
		}

		if (is_object($postData)) {
			return $this->preparePostRequestFromObject($url, $postData);
		}

		// If $postData is a string beginning with '<', the string will be sent unaltered with content type 'text/xml'
		// If $postData is a string beginning with '{', the string will be sent unaltered with content type 'application/json'
		// If $postData is a plain string, the string will be sent unaltered with content type 'text/plain'
		return $this->preparePostRequestFromString($url, $postData, true);
	}

	protected function prepareGetRequest(string $url): curlRequest
	{
		return new curlRequest($url);
	}

	protected function preparePostRequestFromArray(string $url, array $postData): curlRequest
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromArray($postData);

		return $curlRequest;
	}

	protected function preparePostRequestFromObject(string $url, stdClass $object): curlRequest
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromObject($object);

		return $curlRequest;
	}

	protected function preparePostRequestFromString(string $url, string $dataString, bool $detectContentType = false): curlRequest
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromString($dataString, $detectContentType);

		return $curlRequest;
	}
}