<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\formHandler\rule;

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use UnexpectedValueException;

class ValidateAgainstOptions extends FormRule
{
	private array $validOptions;

	function __construct($errorMessage, array $validOptions)
	{
		$this->validOptions = $validOptions;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue)) {
			return array_key_exists($fieldValue, $this->validOptions);
		}

		if (is_array($fieldValue)) {
			foreach ($fieldValue as $elementValue) {
				if (!is_scalar($elementValue)) {
					return false;
				}

				if (!array_key_exists($elementValue, $this->validOptions)) {
					return false;
				}
			}

			return true;
		}

		throw new UnexpectedValueException('The field value is neither a scalar data type nor an array');
	}
}
/* EOF */