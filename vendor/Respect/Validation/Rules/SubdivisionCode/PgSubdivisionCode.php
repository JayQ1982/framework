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
 * Validator for Papua New Guinea subdivision code.
 *
 * ISO 3166-1 alpha-2: PG
 *
 * @link http://www.geonames.org/PG/administrative-division-papua-new-guinea.html
 */
class PgSubdivisionCode extends AbstractSearcher
{
    public $haystack = [
        'CPK', // Chimbu
        'CPM', // Central
        'EBR', // East New Britain
        'EHG', // Eastern Highlands
        'EPW', // Enga
        'ESW', // East Sepik
        'GPK', // Gulf
        'MBA', // Milne Bay
        'MPL', // Morobe
        'MPM', // Madang
        'MRL', // Manus
        'NCD', // National Capital
        'NIK', // New Ireland
        'NPP', // Northern
        'NSA', // Bougainville
        'SAN', // Sandaun
        'SHM', // Southern Highlands
        'WBK', // West New Britain
        'WHM', // Western Highlands
        'WPD', // Western
    ];

    public $compareIdentical = true;
}
