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

class RegexRule extends FormRule
{
	protected string $pattern;

	public function __construct(string $pattern, string $errorMessage)
	{
		parent::__construct($errorMessage);

		$this->pattern = $pattern;
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue) === true) {
			return $this->checkAgainstPattern($fieldValue);
		} else if (is_array($fieldValue) === true || $fieldValue instanceof ArrayObject) {
			foreach ($fieldValue as $value) {
				if ($this->checkAgainstPattern($value) === false) {
					return false;
				}
			}

			return true;
		} else {
			throw new UnexpectedValueException('The field value is neither scalar nor an array');
		}
	}

	protected function checkAgainstPattern($value)
	{
		return (preg_match($this->pattern, $value) === 1);
	}
}
/* EOF */