<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\exception;

use Exception;
use framework\core\HttpStatusCode;

class NotFoundException extends Exception
{
	public function __construct(string $message = '', HttpStatusCode $code = HttpStatusCode::HTTP_NOT_FOUND)
	{
		if ($message === '') {
			$message = 'Not Found';
		}
		parent::__construct(message: $message, code: $code->value);
	}
}