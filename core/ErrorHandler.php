<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\exception\PhpException;

class ErrorHandler
{
	public function __construct()
	{
		error_reporting(error_level: E_ALL);
		set_error_handler(callback: [$this, 'handlePHPError']);
	}

	public function handlePHPError(int $errorCode, string $errorMessage, string $errorFile, int $errorLine): bool
	{
		throw new PhpException(message: $errorMessage, code: $errorCode, file: $errorFile, line: $errorLine);
	}
}