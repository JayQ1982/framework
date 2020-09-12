<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\api\response;

use SimpleXMLElement;

class xmlCurlResponse extends curlResponse
{
	/**
	 * @return SimpleXMLElement|false
	 */
	protected function convert()
	{
		return simplexml_load_string($this->getResponseBodyAsString(), 'SimpleXMLElement', LIBXML_NOCDATA);
	}

	protected function getFormat(): string
	{
		return curlResponse::RESPONSE_XML;
	}
}
/* EOF */