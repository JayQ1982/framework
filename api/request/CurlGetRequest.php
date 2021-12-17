<?php
/**
 * @author    METANET <entwicklung@metanet.ch>
 * @copyright METANET AG, www.metanet.ch
 */

namespace framework\api\request;

use framework\api\AbstractCurlRequest;

/**
 * Requests a representation of the specified resource. Requests using GET should only retrieve data.
 */
class CurlGetRequest extends AbstractCurlRequest
{
	private function __construct(string $requestTargetUrl)
	{
		parent::__construct(
			requestTargetUrl: $requestTargetUrl,
			requestTypeSpecificCurlOptions: [CURLOPT_HTTPGET => true]
		);
	}

	public static function prepare(string $requestTargetUrl): CurlGetRequest
	{
		return new CurlGetRequest(requestTargetUrl: $requestTargetUrl);
	}
}