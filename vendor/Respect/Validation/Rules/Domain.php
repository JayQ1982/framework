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
use framework\vendor\Respect\Validation\Validatable;

class Domain extends AbstractComposite
{
	/** @var Tld|AllOf */
    protected $tld;
    protected $checks = [];
    protected $otherParts;

	/** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($tldCheck = true)
    {
        $this->checks[] = new NoWhitespace();
        $this->checks[] = new Contains('.');
        $this->checks[] = new Length(3, null);
        $this->tldCheck($tldCheck);
        $this->otherParts = new AllOf(
            new Alnum('-'),
            new Not(new StartsWith('-')),
            new OneOf(
                new Not(new Contains('--')),
                new Callback(function ($str) {
                    return substr_count($str, '--') == 1;
                })
            ),
            new Not(new EndsWith('-'))
        );
    }

    public function tldCheck($do = true)
    {
        if ($do === true) {
            $this->tld = new Tld();
        } else {
            $this->tld = new AllOf(
                new Not(
                    new StartsWith('-')
                ),
                new NoWhitespace(),
                new Length(2, null)
            );
        }

        return true;
    }

    public function validate($input)
    {
        foreach ($this->checks as $chk) {
            if (!$chk->validate($input)) {
                return false;
            }
        }

        if (count($parts = explode('.', $input)) < 2
            || !$this->tld->validate(array_pop($parts))) {
            return false;
        }

        foreach ($parts as $p) {
            if (!$this->otherParts->validate($p)) {
                return false;
            }
        }

        return true;
    }

    public function assert($input)
    {
        $e = [];
        foreach ($this->checks as $chk) {
            $this->collectAssertException($e, $chk, $input);
        }

        if (count($parts = explode('.', $input)) >= 2) {
            $this->collectAssertException($e, $this->tld, array_pop($parts));
        }

        foreach ($parts as $p) {
            $this->collectAssertException($e, $this->otherParts, $p);
        }

        if (count($e)) {
            throw $this->reportError($input)->setRelated($e);
        }

        return true;
    }

    protected function collectAssertException(&$exceptions, Validatable $validator, $input)
    {
        try {
            $validator->assert($input);
        } catch (ValidationException $e) {
            $exceptions[] = $e;
        }
    }

    public function check($input)
    {
        foreach ($this->checks as $chk) {
            $chk->check($input);
        }

        if (count($parts = explode('.', $input)) >= 2) {
            $this->tld->check(array_pop($parts));
        }

        foreach ($parts as $p) {
            $this->otherParts->check($p);
        }

        return true;
    }
}
