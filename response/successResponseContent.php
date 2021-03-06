<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\response;

use framework\common\JsonUtils;
use framework\core\HttpResponse;

class successResponseContent extends responseContent
{
	public function __construct(string $contentType, array $resultData = [])
	{
		parent::__construct([HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV], $contentType);

		$status = 'success';

		switch ($this->contentType) {
			case HttpResponse::TYPE_JSON:
				$this->setContent(JsonUtils::convertToJsonString(['status' => $status, 'result' => $resultData]));
				break;
			case HttpResponse::TYPE_TXT:
			case HttpResponse::TYPE_CSV:
				$content = $status;
				if (count($resultData) !== 0) {
					$content .= print_r($resultData, true);
				}
				$this->setContent($content);
				break;
			default:
				break;
		}
	}
}