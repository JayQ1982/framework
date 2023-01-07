<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\api\request;

use framework\api\AbstractCurlRequest;

/**
 * Apply partial modifications to a resource.
 */
class CurlPatchRequest extends AbstractCurlRequest
{
	private function __construct(string $requestTargetUrl)
	{
		parent::__construct(
			requestTargetUrl: $requestTargetUrl,
			requestTypeSpecificCurlOptions: [CURLOPT_CUSTOMREQUEST => 'PATCH']
		);
	}

	public static function prepareWithPostBody(string $requestTargetUrl, array $postData): CurlPatchRequest
	{
		$curlPatchRequest = new CurlPatchRequest(requestTargetUrl: $requestTargetUrl);
		$curlPatchRequest->setPostBody(postData: $postData);

		return $curlPatchRequest;
	}

	public static function prepareWithXmlBody(string $requestTargetUrl, string $xmlString): CurlPatchRequest
	{
		$curlPatchRequest = new CurlPatchRequest(requestTargetUrl: $requestTargetUrl);
		$curlPatchRequest->setXmlBody(xmlString: $xmlString);

		return $curlPatchRequest;
	}

	public static function prepareWithJsonBody(string $requestTargetUrl, string $jsonString): CurlPatchRequest
	{
		$curlPatchRequest = new CurlPatchRequest(requestTargetUrl: $requestTargetUrl);
		$curlPatchRequest->setJsonBody(jsonString: $jsonString);

		return $curlPatchRequest;
	}

	public static function prepareJsonApiRequest(string $requestTargetUrl, string $jsonString): CurlPatchRequest
	{
		$curlPatchRequest = new CurlPatchRequest(requestTargetUrl: $requestTargetUrl);
		$curlPatchRequest->setJsonApiBody(jsonString: $jsonString);

		return $curlPatchRequest;
	}

	public static function prepareWithPlainTextBody(string $requestTargetUrl, string $plainText): CurlPatchRequest
	{
		$curlPatchRequest = new CurlPatchRequest(requestTargetUrl: $requestTargetUrl);
		$curlPatchRequest->setPlainTextBody(plainText: $plainText);

		return $curlPatchRequest;
	}
}