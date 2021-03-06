<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace framework\vendor\Respect\Validation\Rules\SubdivisionCode;

use framework\vendor\Respect\Validation\Rules\AbstractSearcher;

/**
 * Validator for Curacao subdivision code.
 *
 * ISO 3166-1 alpha-2: CW
 *
 * @link http://www.geonames.org/CW/administrative-division-curacao.html
 */
class CwSubdivisionCode extends AbstractSearcher
{
    public $haystack = [null, ''];

    public $compareIdentical = true;
}
