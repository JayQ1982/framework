<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;

class ValidAmountRule extends FormRule
{
	private bool $valueIsFloat;

	public function __construct(bool $valueIsFloat, HtmlText $errorMessage)
	{
		$this->valueIsFloat = $valueIsFloat;

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

		if (!$this->valueIsFloat && is_float($value)) {
			return false;
		}

		return true;
	}
}