<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\api\response;

use framework\common\JsonUtils;
use Throwable;

class arrayCurlResponse extends curlResponse
{
	protected function convert()
	{
		$responseBodyAsString = $this->getResponseBodyAsString();

		// Return response body as array ("enforcing format", extended handling, expects structured response)
		$defaultReturnValue = [$responseBodyAsString];
		switch (mb_substr($responseBodyAsString, 0, 1)) {
			case '<':
				// Looks like a xml response
				return ($this->convertXmlToArray($responseBodyAsString) ?? $defaultReturnValue);

			case '{': // intentionally fallthrough
			case '[':
				// Looks like a json response
				return (JsonUtils::deJson($responseBodyAsString) ?? $defaultReturnValue);

			default:
				return $defaultReturnValue;
		}
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_JSON;
	}

	/**
	 * Converts XML to a nested array for standardized output
	 *
	 * @param string $xml : XML as string, which will be converted
	 *
	 * @return array|bool : (nested) array on success, "false" otherwise
	 */
	private function convertXmlToArray(string $xml)
	{
		try {
			$tmp = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		} catch (Throwable $e) {
			return false;
		}
		if ($tmp === false) {
			return false;
		}
		// actual conversion
		$arr = JsonUtils::deJson(JsonUtils::enJson((array)$tmp));
		if (is_null($arr)) {
			return false;
		}
		self::stringifyEmptyInnerArrays($arr);

		return $arr;
	}

	/**
	 * Sets an empty array recursively within an nested array as empty string
	 *
	 * @param     $data            : The array to clean
	 * @param int $instanceCounter : DO NOT USE THAT PARAMETER! Ignore it. It is used for inner recursion only.
	 */
	private static function stringifyEmptyInnerArrays(&$data, int $instanceCounter = 1)
	{
		if (is_array($data)) {
			if (count($data) == 0 && $instanceCounter != 1) {
				$data = '';
			} else {
				foreach ($data as $key => &$value) {
					self::stringifyEmptyInnerArrays($value, $instanceCounter++);
				}
			}
		}
	}
}
/* EOF */