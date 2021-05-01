<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\exception;

use Exception;
use framework\core\Core;
use framework\core\HttpStatusCodes;
use framework\core\RedirectRoute;

class NotFoundException extends Exception
{
	public function __construct(Core $core, bool $withRedirectCheck, string $message = '', int $code = 0)
	{
		if ($withRedirectCheck) {
			(new RedirectRoute($core))->redirectIfRouteExists();
		}

		if ($message === '') {
			$message = 'Not Found';
		}
		if ($code === 0) {
			$code = HttpStatusCodes::HTTP_NOT_FOUND;
		}

		parent::__construct($message, $code);
	}
}