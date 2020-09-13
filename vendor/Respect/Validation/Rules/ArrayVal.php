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

use ArrayAccess;

class ArrayVal extends AbstractRule
{
    public function validate($input)
    {
        return is_array($input) || $input instanceof ArrayAccess;
    }
}
