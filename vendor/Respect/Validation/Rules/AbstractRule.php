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

use framework\vendor\Respect\Validation\Exceptions\ValidationException;
use framework\vendor\Respect\Validation\Factory;
use framework\vendor\Respect\Validation\Validatable;

/**
 * @author Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 * @author Henrique Moody <henriquemoody@gmail.com>
 * @author Nick Lombard <github@jigsoft.co.za>
 * @author Vicente Mendoza <vicentemmor@yahoo.com.mx>
 */
abstract class AbstractRule implements Validatable
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $template;

    /**
     * {@inheritDoc}
     */
    public function assert($input): void
    {
        if ($this->validate($input)) {
            return;
        }

        throw $this->reportError($input);
    }

    /**
     * {@inheritDoc}
     */
    public function check($input): void
    {
        $this->assert($input);
    }

    /**
     * {}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function reportError($input, array $extraParameters = []): ValidationException
    {
        return Factory::getDefaultInstance()->exception($this, $input, $extraParameters);
    }

    /**
     * {}
     */
    public function setName(string $name): Validatable
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {}
     */
    public function setTemplate(string $template): Validatable
    {
        $this->template = $template;

        return $this;
    }

	/**
	 * @param mixed $input
	 *
	 * @return bool
	 * @return bool
	 */
    public function __invoke($input): bool
    {
        return $this->validate($input);
    }
}
