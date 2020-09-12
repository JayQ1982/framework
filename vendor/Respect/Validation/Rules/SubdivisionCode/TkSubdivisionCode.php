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
 * Validator for Tokelau subdivision code.
 *
 * ISO 3166-1 alpha-2: TK
 *
 * @link http://www.geonames.org/TK/administrative-division-tokelau.html
 */
class TkSubdivisionCode extends AbstractSearcher
{
    public $haystack = [
        'A', // Atafu
        'F', // Fakaofo
        'N', // Nukunonu
    ];

    public $compareIdentical = true;
}
