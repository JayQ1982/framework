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

use framework\vendor\Respect\Validation\Exceptions\SfException;
use framework\vendor\Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function trim;

/**
 * Validate the input with a Symfony Validator (>=4.0 or >=3.0) Constraint.
 *
 * @author Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 * @author Augusto Pascutti <augusto@phpsp.org.br>
 * @author Henrique Moody <henriquemoody@gmail.com>
 * @author Hugo Hamon <hugo.hamon@sensiolabs.com>
 */
final class Sf extends AbstractRule
{
    /**
     * @var Constraint
     */
    private $constraint;

    /**
     * @var ValidatorInterface
     */
    private $validator;

	/**
	 * Initializes the rule with the Constraint and the Validator.
	 * In the the Validator is not defined, tries to create one.
	 *
	 * @param Constraint              $constraint
	 * @param ValidatorInterface|null $validator
	 */
    public function __construct(Constraint $constraint, ?ValidatorInterface $validator = null)
    {
        $this->constraint = $constraint;
        $this->validator = $validator ?: Validation::createValidator();
    }

    /**
     * {@inheritDoc}
     */
    public function assert($input): void
    {
        /** @var ConstraintViolationList $violations */
        $violations = $this->validator->validate($input, $this->constraint);
        if ($violations->count() === 0) {
            return;
        }

        if ($violations->count() === 1) {
            throw $this->reportError($input, ['violations' => $violations[0]->getMessage()]);
        }

        throw $this->reportError($input, ['violations' => trim($violations->__toString())]);
    }

    /**
     * {@inheritDoc}
     */
    public function reportError($input, array $extraParameters = []): ValidationException
    {
        $exception = parent::reportError($input, $extraParameters);
        if (isset($extraParameters['violations'])) {
            $exception->updateTemplate($extraParameters['violations']);
        }

        return $exception;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($input): bool
    {
        try {
            $this->assert($input);
        } catch (SfException) {
            return false;
        }

        return true;
    }
}
