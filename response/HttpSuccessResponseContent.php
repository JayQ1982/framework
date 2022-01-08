<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\response;

use framework\common\JsonUtils;
use stdClass;

class HttpSuccessResponseContent extends HttpResponseContent
{
	private const SUCCESS_STATUS = 'success';

	private function __construct(string $content)
	{
		parent::__construct(content: $content);
	}

	public static function createJsonResponseContent(stdClass $resultDataObject): HttpResponseContent
	{
		return new HttpSuccessResponseContent(content: JsonUtils::convertToJsonString([
			'status' => HttpSuccessResponseContent::SUCCESS_STATUS,
			'result' => $resultDataObject,
		]));
	}

	public static function createTextResponseContent(stdClass $resultDataObject): HttpResponseContent
	{
		return new HttpSuccessResponseContent(
			content: HttpSuccessResponseContent::SUCCESS_STATUS . PHP_EOL . print_r($resultDataObject, true)
		);
	}
}