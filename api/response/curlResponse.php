<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\api\response;

use framework\api\curlRequest;
use Throwable;

abstract class curlResponse
{
	const RESPONSE_RAW = 'raw';
	const RESPONSE_XML = 'xml';
	const RESPONSE_JSON = 'json';
	const RESPONSE_ARRAY = 'array';
	const RESPONSE_OBJECT = 'object';
	const ALLOWED_RESPONSE_FORMATS = [
		self::RESPONSE_RAW,
		self::RESPONSE_XML,
		self::RESPONSE_JSON,
		self::RESPONSE_ARRAY,
		self::RESPONSE_OBJECT,
	];

	/** @var curlRequest */
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

	/**
	 * @return mixed
	 */
	abstract protected function convert();

	abstract protected function getFormat(): string;

	/**
	 * @return false|mixed
	 */
	public function get()
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
/* EOF */