<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\HiddenField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;

class HiddenFieldRenderer extends FormRenderer
{
	private HiddenField $hiddenField;

	public function __construct(HiddenField $hiddenField)
	{
		$this->hiddenField = $hiddenField;
	}

	public function prepare(): void
	{
		$hiddenField = $this->hiddenField;

		$attributes = [
			new FormTagAttribute('type', $hiddenField->getType()),
			new FormTagAttribute('name', $hiddenField->getName()),
			new FormTagAttribute('value', $hiddenField->renderValue()),
		];

		$this->setFormTag(new FormTag('input', true, $attributes));
	}
}
/* EOF */