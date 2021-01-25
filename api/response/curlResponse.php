<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api\response;

use framework\api\curlRequest;
use framework\common\SimpleXMLExtended;
use SimpleXMLElement;
use stdClass;
use Throwable;

abstract class curlResponse
{
	const RESPONSE_RAW = 'raw';
	const RESPONSE_XML = 'xml';
	const RESPONSE_JSON = 'json';
	const RESPONSE_OBJECT = 'object';
	const ALLOWED_RESPONSE_FORMATS = [
		curlResponse::RESPONSE_RAW,
		curlResponse::RESPONSE_XML,
		curlResponse::RESPONSE_JSON,
		curlResponse::RESPONSE_OBJECT,
	];

	private curlRequest $curlRequest;
	private string $convertErrorMessage = '';

	public function __construct(curlRequest $curlRequest)
	{
		$this->curlRequest = $curlRequest;
	}

	protected function getResponseBodyAsString(): string
	{
		$originalRawResponseBody = $this->curlRequest->getRawResponseBody();

		return is_string($originalRawResponseBody) ? $originalRawResponseBody : '';
	}

	abstract protected function convert(): array|stdClass|SimpleXMLExtended;

	abstract protected function getFormat(): string;

	public function get(): false|array|stdClass|SimpleXMLElement
	{
		if (!$this->curlRequest->wasSuccessful()) {
			return false;
		}

		try {
			return $this->convert();
		} catch (Throwable $t) {
			$this->setConvertErrorMessage('An error occurred while converting to desired format: "' . $this->getFormat() . '" -> ' . $t->getMessage());

			return false;
		}
	}

	protected function setConvertErrorMessage(string $convertErrorMessage): void
	{
		$this->convertErrorMessage = $convertErrorMessage;
	}

	public function getConvertErrorMessage(): string
	{
		return $this->convertErrorMessage;
	}
}