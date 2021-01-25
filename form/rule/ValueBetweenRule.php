<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\rule;

use ArrayObject;
use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;
use UnexpectedValueException;

class ValueBetweenRule extends FormRule
{
	protected int|float $minValue;
	protected int|float $maxValue;

	/**
	 * @param mixed    $minValue     The minimum allowed value
	 * @param mixed    $maxValue     The maximum allowed value
	 * @param HtmlText $errorMessage The error message on failure
	 */
	function __construct(int|float $minValue, int|float $maxValue, HtmlText $errorMessage)
	{
		parent::__construct($errorMessage);

		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_int($fieldValue) || is_float($fieldValue)) {
			return $this->checkValueBetween($fieldValue);
		}
		if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			return $this->checkValueBetweenArray($fieldValue);
		}
		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}

	private function checkValueBetween($value): bool
	{
		return ($value >= $this->minValue && $value <= $this->maxValue);
	}

	private function checkValueBetweenArray(array|ArrayObject $value): bool
	{
		foreach ($value as $val) {
			if ((is_int($val) || is_float($val)) && !$this->checkValueBetween($val)) {
				return false;
			} else if ((is_array($val) || $val instanceof ArrayObject) && !$this->checkValueBetweenArray($val)) {
				return false;
			}
		}

		return true;
	}
}