<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\api;

use stdClass;

/**
 * This class provides some helpers to execute curl requests
 */
abstract class curlHelper
{
	/**
	 * @param string                     $url
	 * @param array|stdClass|string|null $postData     : If "null", a GET-Request will be performed, otherwise a
	 *                                                 POST-request with given data will be performed
	 *                                                 If $postData is an array or object, it will automatically be
	 *                                                 converted into a url-encoded query string and sent with content
	 *                                                 type
	 *                                                 'text/plan' If $postData is a string beginning with '<', the
	 *                                                 string will be sent unaltered with content type 'text/xml' If
	 *                                                 $postData is a string beginning with '{', the string will be
	 *                                                 sent unaltered with content type 'application/json' If $postData
	 *                                                 is a plain string, the string will be sent unaltered with
	 *                                                 content type 'text/plain'
	 *
	 * @return curlRequest
	 */
	protected function prepareRequest(string $url, $postData = null)
	{
		if (is_null($postData)) {
			return $this->prepareGetRequest($url);
		}

		if (is_array($postData)) {
			return $this->preparePostRequestFromArray($url, $postData);
		}

		if (is_object($postData)) {
			return $this->preparePostRequestFromObject($url, $postData);
		}

		return $this->preparePostRequestFromString($url, $postData, true);
	}

	/**
	 * @param string $url
	 *
	 * @return curlRequest
	 */
	protected function prepareGetRequest(string $url)
	{
		return new curlRequest($url);
	}

	/**
	 * @param string $url
	 * @param array  $postData
	 *
	 * @return curlRequest
	 */
	protected function preparePostRequestFromArray(string $url, array $postData)
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromArray($postData);

		return $curlRequest;
	}

	/**
	 * @param string   $url
	 * @param stdClass $object
	 *
	 * @return curlRequest
	 */
	protected function preparePostRequestFromObject(string $url, stdClass $object)
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromObject($object);

		return $curlRequest;
	}

	/**
	 * @param string $url
	 * @param string $xmlString
	 *
	 * @return curlRequest
	 */
	protected function preparePostRequestFromXml(string $url, string $xmlString)
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromXML($xmlString);

		return $curlRequest;
	}

	/**
	 * @param string $url
	 * @param string $jsonString
	 *
	 * @return curlRequest
	 */
	protected function preparePostRequestFromJson(string $url, string $jsonString)
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromJson($jsonString);

		return $curlRequest;
	}

	/**
	 * @param string $url
	 * @param string $dataString
	 * @param bool   $detectContentType
	 *
	 * @return curlRequest
	 */
	protected function preparePostRequestFromString(string $url, string $dataString, bool $detectContentType = false)
	{
		$curlRequest = new curlRequest($url);
		$curlRequest->setPostDataFromString($dataString, $detectContentType);

		return $curlRequest;
	}
}
/* EOF */