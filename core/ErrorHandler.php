<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\exception\PhpException;
use LogicException;

class ErrorHandler
{
	private static ErrorHandler $registeredInstance;

	public static function register(): void
	{
		if (isset(ErrorHandler::$registeredInstance)) {
			throw new LogicException(message: 'ErrorHandler is already registered.');
		}
		ErrorHandler::$registeredInstance = new ErrorHandler();
		set_error_handler(callback: [ErrorHandler::$registeredInstance, 'handlePHPError']);
	}

	public function handlePHPError(int $errorCode, string $errorMessage, string $errorFile, int $errorLine): bool
	{
		throw new PhpException(message: $errorMessage, code: $errorCode, file: $errorFile, line: $errorLine);
	}
}