<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\exception;

use Exception;

class UnauthorizedException extends Exception
{
	private array $individualResponsePlaceholders;

	public function __construct(array $individualResponsePlaceholders = [])
	{
		if (!isset($individualResponsePlaceholders['title'])) {
			$individualResponsePlaceholders['title'] = 'Unauthorized';
		}
		$this->individualResponsePlaceholders = $individualResponsePlaceholders;

		parent::__construct($individualResponsePlaceholders['message'] ?? 'Unauthorized');
	}

	public function getIndividualPlaceholders(): array
	{
		return $this->individualResponsePlaceholders;
	}
}