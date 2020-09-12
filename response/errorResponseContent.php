<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\response;

use framework\common\JsonUtils;
use framework\core\HttpResponse;

class errorResponseContent extends responseContent
{
	public function __construct(string $contentType, string $errorMessage, $errorCode = null, array $additionalInfo = [])
	{
		parent::__construct([HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV], $contentType);

		$status = 'error';
		$errorData = [
			'message' => $errorMessage,
			'code'    => $errorCode,
		];
		if (!empty($additionalInfo)) {
			$errorData['additionalInfo'] = (array)$additionalInfo;
		}

		switch ($contentType) {
			case HttpResponse::TYPE_JSON:
				$this->setContent(JsonUtils::enJson([
					'status' => $status,
					'error'  => $errorData,
				]));
				break;
			case HttpResponse::TYPE_TXT:
			case HttpResponse::TYPE_CSV:
				$this->setContent('ERROR: ' . $errorMessage . ' (' . $errorCode . ')');
				break;
			default:
				break;
		}
	}
}
/* EOF */