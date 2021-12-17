<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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