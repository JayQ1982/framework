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

class MaxLengthRule extends FormRule
{
	protected int $maxLength;

	function __construct(int $maxLength, string $errorMessage)
	{
		$this->maxLength = $maxLength;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue) === true) {
			return $this->checkValueLengthAgainst(mb_strlen($fieldValue));
		}
		if (is_array($fieldValue) === true || $fieldValue instanceof ArrayObject) {
			return $this->checkValueLengthAgainst(count($fieldValue));
		}
		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}

	private function checkValueLengthAgainst($valueLength)
	{
		return ($valueLength <= $this->maxLength);
	}
}
/* EOF */