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
 * Validator for French Southern Territories subdivision code.
 *
 * ISO 3166-1 alpha-2: TF
 *
 * @link http://www.geonames.org/TF/administrative-division-french-southern-territories.html
 */
class TfSubdivisionCode extends AbstractSearcher
{
    public $haystack = [null, ''];

    public $compareIdentical = true;
}
