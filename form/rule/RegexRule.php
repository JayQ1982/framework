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

class RegexRule extends FormRule
{
	protected string $pattern;

	public function __construct(string $pattern, HtmlText $errorMessage)
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

		if (is_scalar($fieldValue)) {
			return $this->checkAgainstPattern($fieldValue);
		} else if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			foreach ($fieldValue as $value) {
				if (!$this->checkAgainstPattern($value)) {
					return false;
				}
			}

			return true;
		} else {
			throw new UnexpectedValueException('The field value is neither scalar nor an array');
		}
	}

	protected function checkAgainstPattern($value): bool
	{
		return (preg_match($this->pattern, $value) === 1);
	}
}