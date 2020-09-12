<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;

class ValidAmountRule extends FormRule
{
	private bool $float;

	public function __construct(bool $float, string $errorMessage)
	{
		$this->float = $float;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$value = $formField->getRawValue();
		if (!is_numeric($value)) {
			return false;
		}

		if (!$this->float && is_float($value)) {
			return false;
		}

		return true;
	}
}
/* EOF */