<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;

class DefaultComponentRenderer extends FormRenderer
{
	private FormComponent $formComponent;

	public function __construct(FormComponent $formComponent)
	{
		$this->formComponent = $formComponent;
	}

	public function prepare(): void
	{
		$componentTag = new FormTag($this->formComponent->getName(), false);

		if ($this->formComponent->hasErrors()) {
			$componentTag->addFormTagAttribute(new FormTagAttribute('class', 'has-error'));
		}
		$this->setFormTag($componentTag);
	}
}
/* EOF */