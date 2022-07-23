<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\exception;

use Exception;
use framework\core\HttpStatusCode;

class UnauthorizedException extends Exception
{
	public function __construct($message = 'Unauthorized', HttpStatusCode $code = HttpStatusCode::HTTP_UNAUTHORIZED)
	{
		parent::__construct(message: $message, code: $code->value);
	}
}