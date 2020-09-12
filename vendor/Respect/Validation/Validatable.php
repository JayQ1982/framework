<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace framework\vendor\Respect\Validation;

use framework\vendor\Respect\Validation\Exceptions\ValidationException;

/** Interface for validation rules */
interface Validatable
{
	/**
	 * @param $input
	 *
	 * @return mixed
	 * @throws ValidationException
	 */
    public function assert($input);

	/**
	 * @param $input
	 *
	 * @return mixed
	 * @throws ValidationException
	 */
    public function check($input);

    public function getName();

    public function reportError($input, array $relatedExceptions = []);

    public function setName($name);

    public function setTemplate($template);

    public function validate($input);
}
