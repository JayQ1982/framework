<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
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