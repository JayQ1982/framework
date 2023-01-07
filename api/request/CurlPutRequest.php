<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\api\request;

use framework\api\AbstractCurlRequest;

/**
 * Replaces all current representations of the target resource with the request payload.
 */
class CurlPutRequest extends AbstractCurlRequest
{
	private function __construct(string $requestTargetUrl)
	{
		parent::__construct(
			requestTargetUrl: $requestTargetUrl,
			requestTypeSpecificCurlOptions: [CURLOPT_CUSTOMREQUEST => 'PUT']
		);
	}

	public static function prepareWithPostBody(string $requestTargetUrl, array $postData): CurlPutRequest
	{
		$curlPutRequest = new CurlPutRequest(requestTargetUrl: $requestTargetUrl);
		$curlPutRequest->setPostBody(postData: $postData);

		return $curlPutRequest;
	}

	public static function prepareWithXmlBody(string $requestTargetUrl, string $xmlString): CurlPutRequest
	{
		$curlPutRequest = new CurlPutRequest(requestTargetUrl: $requestTargetUrl);
		$curlPutRequest->setXmlBody(xmlString: $xmlString);

		return $curlPutRequest;
	}

	public static function prepareWithJsonBody(string $requestTargetUrl, string $jsonString): CurlPutRequest
	{
		$curlPutRequest = new CurlPutRequest(requestTargetUrl: $requestTargetUrl);
		$curlPutRequest->setJsonBody(jsonString: $jsonString);

		return $curlPutRequest;
	}

	public static function prepareJsonApiRequest(string $requestTargetUrl, string $jsonString): CurlPutRequest
	{
		$curlPutRequest = new CurlPutRequest(requestTargetUrl: $requestTargetUrl);
		$curlPutRequest->setJsonApiBody(jsonString: $jsonString);

		return $curlPutRequest;
	}

	public static function prepareWithPlainTextBody(string $requestTargetUrl, string $plainText): CurlPutRequest
	{
		$curlPutRequest = new CurlPutRequest(requestTargetUrl: $requestTargetUrl);
		$curlPutRequest->setPlainTextBody(plainText: $plainText);

		return $curlPutRequest;
	}
}