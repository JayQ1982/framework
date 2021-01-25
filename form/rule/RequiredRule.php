<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;

class RequiredRule extends FormRule
{
	public function validate(FormField $formField): bool
	{
		return !$formField->isValueEmpty();
	}
}