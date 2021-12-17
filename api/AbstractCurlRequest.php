<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api;

use CurlHandle;
use LogicException;

abstract class AbstractCurlRequest
{
	private const CONTENT_TYPE = 'Content-Type';
	private const CONTENT_LENGTH = 'Content-Length';
	private const PROTECTED_HTTP_HEADERS = [
		AbstractCurlRequest::CONTENT_TYPE,
		AbstractCurlRequest::CONTENT_LENGTH,
	];
	private const PROTECTED_CURL_OPTIONS = [
		CURLOPT_URL,
		CURLOPT_CONNECTTIMEOUT,
		CURLOPT_TIMEOUT,
		CURLOPT_POSTFIELDS,
		CURLOPT_CUSTOMREQUEST,
		CURLOPT_HTTPGET,
		CURLOPT_NOBODY,
		CURLOPT_HEADER,
		CURLOPT_POST,
		CURLOPT_HTTPHEADER,
		CURLOPT_SSL_VERIFYHOST,
		CURLOPT_SSL_VERIFYPEER,
		CURLOPT_HTTPAUTH,
		CURLOPT_USERPWD,
	];

	private static ?CurlHandle $curlHandle = null; // Use connection persistence for multiple requests to the same url
	private static array $instances = [];

	private int $instanceIndex;
	private array $httpHeaders = [];
	private array $curlOptions = [
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT        => 10,
		CURLOPT_RETURNTRANSFER => true,
	];
	private bool $acceptRedirectionResponseCode = false;
	private bool $isExecuted = false;
	private CurlResponse $curlResponse;

	protected function __construct(string $requestTargetUrl, array $requestTypeSpecificCurlOptions)
	{
		$this->instanceIndex = empty(AbstractCurlRequest::$instances) ? 1 : max(value: array_keys(array: AbstractCurlRequest::$instances)) + 1;
		AbstractCurlRequest::$instances[$this->instanceIndex] = $this;
		$this->curlOptions[CURLOPT_URL] = $requestTargetUrl;
		foreach ($requestTypeSpecificCurlOptions as $key => $val) {
			$this->curlOptions[$key] = $val;
		}
	}

	public function __destruct()
	{
		unset(AbstractCurlRequest::$instances[$this->instanceIndex]);
		if (empty(AbstractCurlRequest::$instances)) {
			curl_close(handle: AbstractCurlRequest::$curlHandle);
			AbstractCurlRequest::$curlHandle = null;
		}
	}

	protected function setPostBody(array $postData): void
	{
		$postFields = http_build_query(
			data: AbstractCurlRequest::convertAllDataToString(data: $postData),
			encoding_type: PHP_QUERY_RFC3986
		);
		$this->httpHeaders[AbstractCurlRequest::CONTENT_TYPE] = 'application/x-www-form-urlencoded; charset=utf-8';
		$this->httpHeaders[AbstractCurlRequest::CONTENT_LENGTH] = strlen($postFields);
		$this->curlOptions[CURLOPT_POSTFIELDS] = $postFields;
	}

	protected function setXmlBody(string $xmlString): void
	{
		$this->httpHeaders[AbstractCurlRequest::CONTENT_TYPE] = 'text/xml; charset=utf-8';
		$this->httpHeaders['HTTP_PRETTY_PRINT'] = 'TRUE';
		$this->httpHeaders[AbstractCurlRequest::CONTENT_LENGTH] = strlen($xmlString);
		$this->curlOptions[CURLOPT_POSTFIELDS] = $xmlString;
	}

	protected function setJsonBody(string $jsonString): void
	{
		$this->httpHeaders[AbstractCurlRequest::CONTENT_TYPE] = 'application/json; charset=utf-8';
		$this->httpHeaders[AbstractCurlRequest::CONTENT_LENGTH] = strlen($jsonString);
		$this->curlOptions[CURLOPT_POSTFIELDS] = $jsonString;
	}

	protected function setPlainTextBody(string $plainText): void
	{
		$this->httpHeaders[AbstractCurlRequest::CONTENT_TYPE] = 'text/plain; charset=utf-8';
		$this->httpHeaders[AbstractCurlRequest::CONTENT_LENGTH] = strlen($plainText);
		$this->curlOptions[CURLOPT_POSTFIELDS] = $plainText;
	}

	public function setConnectTimeoutInSeconds(int $connectTimeoutInSeconds): void
	{
		$this->curlOptions[CURLOPT_CONNECTTIMEOUT] = $connectTimeoutInSeconds;
	}

	public function setRequestTimeoutInSeconds(int $requestTimeoutInSeconds): void
	{
		$this->curlOptions[CURLOPT_TIMEOUT] = $requestTimeoutInSeconds;
	}

	public function setHttpHeader(string $key, string $value): void
	{
		if (in_array(needle: $key, haystack: AbstractCurlRequest::PROTECTED_HTTP_HEADERS)) {
			throw new LogicException(message: 'You are not allowed to overwrite the HTTP-Header ' . $key);
		}
		$this->httpHeaders[$key] = $value;
	}

	public function setCurlOption(int $optionIdentifier, null|string|int|bool $newValue)
	{
		if (in_array(needle: $optionIdentifier, haystack: AbstractCurlRequest::PROTECTED_CURL_OPTIONS)) {
			throw new LogicException(message: 'You are not allowed to overwrite the cURL-Option ' . $optionIdentifier);
		}
		$this->curlOptions[$optionIdentifier] = $newValue;
	}

	public function removeCurlOption(int $optionIdentifier): void
	{
		if (in_array(needle: $optionIdentifier, haystack: AbstractCurlRequest::PROTECTED_CURL_OPTIONS)) {
			throw new LogicException(message: 'You are not allowed to remove the cURL-Option ' . $optionIdentifier);
		}
		unset($this->curlOptions[$optionIdentifier]);
	}

	public function disableSslCheck(): void
	{
		$this->curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
		$this->curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
	}

	public function acceptRedirectionResponseCode(): void
	{
		$this->acceptRedirectionResponseCode = true;
	}

	public function useBasicHttpAuthentication(string $authUserNamePassword): void
	{
		$this->curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
		$this->curlOptions[CURLOPT_USERPWD] = $authUserNamePassword;
	}

	public function execute(): bool
	{
		if ($this->isExecuted) {
			throw new LogicException(message: 'This cURL-Request is already executed.');
		}
		$this->isExecuted = true;

		if (is_null(AbstractCurlRequest::$curlHandle)) {
			AbstractCurlRequest::$curlHandle = curl_init();
		} else {
			curl_reset(AbstractCurlRequest::$curlHandle);
		}

		$httpHeaders = [];
		foreach ($this->httpHeaders as $key => $val) {
			$httpHeaders[] = $key . ': ' . $val;
		}
		$this->curlOptions[CURLOPT_HTTPHEADER] = $httpHeaders;
		curl_setopt_array(handle: AbstractCurlRequest::$curlHandle, options: $this->curlOptions);
		$this->curlResponse = CurlResponse::createFromPreparedCurlHandle(
			preparedCurlHandle: AbstractCurlRequest::$curlHandle,
			acceptRedirectionResponseCode: $this->acceptRedirectionResponseCode
		);

		return !$this->curlResponse->hasErrors();
	}

	public function getCurlResponse(): CurlResponse
	{
		return $this->curlResponse;
	}

	private static function convertAllDataToString(mixed $data): array|string
	{
		if (is_bool(value: $data)) {
			return (string)(($data) ? 1 : 0);
		}

		if (is_object(value: $data)) {
			$arrPrepared = [];
			foreach (get_object_vars($data) as $strKey => $val) {
				$strKey = AbstractCurlRequest::convertAllDataToString($strKey);
				$val = AbstractCurlRequest::convertAllDataToString($val);
				$arrPrepared[$strKey] = $val;
			}

			return $arrPrepared;
		}

		if (is_array(value: $data)) {
			$arrPrepared = [];
			foreach ($data as $strKey => $val) {
				$strKey = AbstractCurlRequest::convertAllDataToString($strKey);
				$val = AbstractCurlRequest::convertAllDataToString($val);
				$arrPrepared[$strKey] = $val;
			}

			return $arrPrepared;
		}

		return (string)$data;
	}
}