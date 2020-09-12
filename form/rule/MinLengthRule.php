<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use ArrayObject;
use framework\form\component\FormField;
use framework\form\FormRule;
use UnexpectedValueException;

class MinLengthRule extends FormRule
{
	protected int $minLength;

	function __construct(int $minLength, string $errorMessage)
	{
		$this->minLength = $minLength;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue)) {
			return $this->checkValueLengthAgainst(mb_strlen($fieldValue));
		}
		if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			return $this->checkValueLengthAgainst(count($fieldValue));
		}
		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}

	private function checkValueLengthAgainst($valueLength)
	{
		return ($valueLength >= $this->minLength);
	}
}
/* EOF */