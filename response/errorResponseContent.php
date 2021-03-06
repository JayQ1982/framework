<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\response;

use framework\common\JsonUtils;
use framework\core\HttpResponse;

class errorResponseContent extends responseContent
{
	public function __construct(string $contentType, string $errorMessage, null|int|string $errorCode = null, array $additionalInfo = [])
	{
		parent::__construct([HttpResponse::TYPE_JSON, HttpResponse::TYPE_TXT, HttpResponse::TYPE_CSV], $contentType);

		$status = 'error';
		$errorData = [
			'message' => $errorMessage,
			'code'    => $errorCode,
		];
		if (count($additionalInfo) !== 0) {
			$errorData['additionalInfo'] = $additionalInfo;
		}

		switch ($this->contentType) {
			case HttpResponse::TYPE_JSON:
				$this->setContent(JsonUtils::convertToJsonString([
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