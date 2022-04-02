<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\field\CheckboxOptionsField;

class CheckboxOptionsRenderer extends DefaultOptionsRenderer
{
	public function __construct(CheckboxOptionsField $checkboxOptionsField)
	{
		parent::__construct(optionsField: $checkboxOptionsField, inputFieldType: 'checkbox', acceptMultipleValues: true);
	}
}