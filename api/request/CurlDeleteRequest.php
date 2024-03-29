<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\api\request;

use framework\api\AbstractCurlRequest;

/**
 * Deletes the specified resource.
 */
class CurlDeleteRequest extends AbstractCurlRequest
{
	private function __construct(string $requestTargetUrl)
	{
		parent::__construct(
			requestTargetUrl: $requestTargetUrl,
			requestTypeSpecificCurlOptions: [CURLOPT_CUSTOMREQUEST => 'DELETE']
		);
	}

	public static function prepare(string $requestTargetUrl): CurlDeleteRequest
	{
		return new CurlDeleteRequest(requestTargetUrl: $requestTargetUrl);
	}
}