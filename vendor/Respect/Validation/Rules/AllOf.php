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

use framework\vendor\Respect\Validation\Exceptions\ValidationException;

class AllOf extends AbstractComposite
{
    public function assert($input)
    {
        $exceptions = $this->validateRules($input);
        $numRules = count($this->rules);
        $numExceptions = count($exceptions);
        $summary = [
            'total' => $numRules,
            'failed' => $numExceptions,
            'passed' => $numRules - $numExceptions,
        ];
        if (!empty($exceptions)) {
            throw $this->reportError($input, $summary)->setRelated($exceptions);
        }

        return true;
    }

	/**
	 * @param $input
	 *
	 * @return bool|mixed
	 * @throws ValidationException
	 */
    public function check($input)
    {
        foreach ($this->getRules() as $rule) {
            $rule->check($input);
        }

        return true;
    }

    public function validate($input)
    {
        foreach ($this->getRules() as $rule) {
            if (!$rule->validate($input)) {
                return false;
            }
        }

        return true;
    }
}
