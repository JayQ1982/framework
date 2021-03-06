<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\exception;

use Exception;

class PhpException extends Exception
{
	public function __construct(string $message, int $code, string $file, int $line)
	{
		parent::__construct($message, $code);
		$this->file = $file;
		$this->line = $line;
	}
}