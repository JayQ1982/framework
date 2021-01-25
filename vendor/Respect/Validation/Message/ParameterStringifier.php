<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

declare(strict_types=1);

namespace framework\vendor\Respect\Validation\Message;

interface ParameterStringifier
{
	/**
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 * @return string
	 * @return string
	 */
    public function stringify(string $name, $value): string;
}
