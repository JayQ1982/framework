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

class ValidValueRule extends FormRule
{
	protected array $validValues;

	public function __construct(array $validValues, string $errorMessage)
	{
		parent::__construct($errorMessage);

		$this->validValues = $validValues;
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue)) {
			return in_array($fieldValue, $this->validValues);
		}

		if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			return (count(array_diff($fieldValue, $this->validValues)) === 0);
		}

		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}
}
/* EOF */