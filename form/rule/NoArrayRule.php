<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;

class NoArrayRule extends FormRule
{
	public function validate(FormField $formField) : bool
	{
		return !is_array($formField->getRawValue());
	}
}