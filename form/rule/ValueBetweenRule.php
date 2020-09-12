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

class ValueBetweenRule extends FormRule
{
	protected $minValue;
	protected $maxValue;

	/**
	 * @param mixed  $minValue     The minimum allowed value
	 * @param mixed  $maxValue     The maximum allowed value
	 * @param string $errorMessage The error message on failure
	 */
	function __construct($minValue, $maxValue, string $errorMessage)
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

		if (is_scalar($fieldValue)) {
			return $this->checkValueBetweenScalar($fieldValue);
		}
		if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			return $this->checkValueBetweenArray($fieldValue);
		}
		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}

	private function checkValueBetweenScalar($value)
	{
		return ($value >= $this->minValue && $value <= $this->maxValue);
	}

	private function checkValueBetweenArray($value)
	{
		foreach ($value as $val) {
			if (is_scalar($val) && $this->checkValueBetweenScalar($val) === false) {
				return false;
			} else if ((is_array($val) || $val instanceof ArrayObject) && $this->checkValueBetweenArray($val) === false) {
				return false;
			}
		}

		return true;
	}
}
/* EOF */