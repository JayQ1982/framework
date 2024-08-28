<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;

class NoArrayRule extends FormRule
{
	public function validate(FormField $formField): bool
	{
		return !is_array($formField->getRawValue());
	}
}