<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api\response;

use framework\common\JsonUtils;
use stdClass;

class jsonCurlResponse extends curlResponse
{
	protected function convert(): stdClass|array
	{
		return JsonUtils::decodeJsonString(jsonString: $this->getResponseBodyAsString(), returnAssociativeArray: false);
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_JSON;
	}
}