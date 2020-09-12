<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\response;

use framework\common\JsonUtils;
use framework\core\HttpResponse;

class successResponseContent extends responseContent
{
	public function __construct(string $contentType, array $result = [])
	{
		parent::__construct([HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV], $contentType);

		$status = 'success';

		switch ($contentType) {
			case HttpResponse::TYPE_JSON:
				$this->setContent(JsonUtils::enJson(['status' => $status, 'result' => $result]));
				break;
			case HttpResponse::TYPE_TXT:
			case HttpResponse::TYPE_CSV:
				$content = $status;
				if (!empty($data)) {
					$content .= print_r($data, true);
				}
				$this->setContent($content);
				break;
			default:
				break;
		}
	}
}
/* EOF */