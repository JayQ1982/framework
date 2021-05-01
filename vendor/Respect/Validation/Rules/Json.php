<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace framework\vendor\Respect\Validation\Rules;

use Throwable;

class Json extends AbstractRule
{
	public function validate($input)
	{
		if (!is_string($input) || '' === $input) {
			return false;
		}

		try {
			json_decode(json: $input, flags: JSON_THROW_ON_ERROR);
			return true;
		} catch(Throwable) {
			return false;
		}
	}
}