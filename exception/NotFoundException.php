<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\exception;

use Exception;
use framework\core\HttpStatusCodes;

class NotFoundException extends Exception
{
	public function __construct(string $message = '', int $code = 0)
	{
		if ($message === '') {
			$message = 'Not Found';
		}
		if ($code === 0) {
			$code = HttpStatusCodes::HTTP_NOT_FOUND;
		}

		parent::__construct($message, $code);
	}
}