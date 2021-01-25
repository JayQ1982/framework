<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api\response;

use framework\common\SimpleXMLExtended;

class xmlCurlResponse extends curlResponse
{
	// TODO: Add possibilities for easier access to data in expected type (int, string, ...)
	protected function convert(): SimpleXMLExtended
	{
		return new SimpleXMLExtended($this->getResponseBodyAsString(), LIBXML_NOCDATA);
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_XML;
	}
}