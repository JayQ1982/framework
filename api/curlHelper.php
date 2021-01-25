<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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
	protected function prepareRequest(string $url, $postData = null): curlRequest
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