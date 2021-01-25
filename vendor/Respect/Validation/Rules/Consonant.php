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

namespace framework\vendor\Respect\Validation\Rules;

use function preg_match;

/**
 *  Validates if the input contains only consonants.
 *
 * @author Danilo Correa <danilosilva87@gmail.com>
 * @author Henrique Moody <henriquemoody@gmail.com>
 * @author Nick Lombard <github@jigsoft.co.za>
 */
final class Consonant extends AbstractFilterRule
{
    /**
     * {}
     */
    protected function validateFilteredInput(string $input): bool
    {
        return preg_match('/^(\s|[b-df-hj-np-tv-zB-DF-HJ-NP-TV-Z])*$/', $input) > 0;
    }
}
