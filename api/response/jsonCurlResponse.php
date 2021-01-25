<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api\response;

use Exception;
use framework\common\JsonUtils;
use stdClass;

class jsonCurlResponse extends curlResponse
{
	protected function convert(): stdClass|array
	{
		$response = JsonUtils::deJson($this->getResponseBodyAsString(), false);
		if (json_last_error() !== JSON_ERROR_NONE || is_null($response)) {
			throw new Exception('Received data was no valid JSON. Json error: ' . json_last_error_msg());
		}

		return $response;
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_JSON;
	}
}