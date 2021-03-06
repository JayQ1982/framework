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
 * Validator for Christmas Island subdivision code.
 *
 * ISO 3166-1 alpha-2: CX
 *
 * @link http://www.geonames.org/CX/administrative-division-christmas-island.html
 */
class CxSubdivisionCode extends AbstractSearcher
{
    public $haystack = [null, ''];

    public $compareIdentical = true;
}
