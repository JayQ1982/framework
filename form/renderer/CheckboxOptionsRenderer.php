<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\CheckboxOptionsField;

class CheckboxOptionsRenderer extends DefaultOptionsRenderer
{
	private CheckboxOptionsField $checkboxOptionsField;

	public function __construct(CheckboxOptionsField $checkboxOptionsField)
	{
		$this->checkboxOptionsField = $checkboxOptionsField;
		parent::__construct($checkboxOptionsField, 'checkbox', true);
	}
}