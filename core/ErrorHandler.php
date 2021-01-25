<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use framework\exception\PhpException;

class ErrorHandler
{
	private Core $core;

	public function __construct(Core $core)
	{
		$this->core = $core;
		error_reporting(E_ALL);
		set_error_handler([$this, 'handlePHPError']);
	}

	public function handlePHPError(int $errorCode, string $errorMessage, string $errorFile, int $errorLine): bool
	{
		throw new PhpException($errorMessage, $errorCode, $errorFile, $errorLine);
	}
}