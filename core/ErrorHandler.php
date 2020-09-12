<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;
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

	public function display_error(int $errorCode = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR, string $errorMessage = '', bool $withRedirectCheck = false): void
	{
		if ($errorCode === HttpStatusCodes::HTTP_NOT_FOUND && $withRedirectCheck) {
			(new RedirectRoute($this->core))->redirectIfRouteExists();
		}
		throw new Exception($errorMessage, $errorCode);
	}
}
/* EOF */