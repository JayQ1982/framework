<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\api\response;

use framework\common\JsonUtils;
use stdClass;
use Exception;

class jsonCurlResponse extends curlResponse
{
	/**
	 * @return array|stdClass|null
	 */
	protected function convert()
	{
		$response = JsonUtils::deJson($this->getResponseBodyAsString(), false);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('Received data was no valid JSON. Json error: ' . json_last_error_msg());
		}

		return $response;
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_JSON;
	}
}
/* EOF */