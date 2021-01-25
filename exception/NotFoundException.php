<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\exception;

use Exception;
use framework\core\Core;
use framework\core\RedirectRoute;

class NotFoundException extends Exception
{
	private array $individualResponsePlaceholders;

	public function __construct(Core $core, bool $withRedirectCheck, array $individualResponsePlaceholders = [])
	{
		if ($withRedirectCheck) {
			(new RedirectRoute($core))->redirectIfRouteExists();
		}
		if (!isset($individualResponsePlaceholders['title'])) {
			$individualResponsePlaceholders['title'] = 'Page not found';
		}
		$this->individualResponsePlaceholders = $individualResponsePlaceholders;

		parent::__construct($individualResponsePlaceholders['message'] ?? 'Page not found');
	}

	public function getIndividualPlaceholders(): array
	{
		return $this->individualResponsePlaceholders;
	}
}