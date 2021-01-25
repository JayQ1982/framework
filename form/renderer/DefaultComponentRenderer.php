<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class DefaultComponentRenderer extends FormRenderer
{
	private FormComponent $formComponent;

	public function __construct(FormComponent $formComponent)
	{
		$this->formComponent = $formComponent;
	}

	public function prepare(): void
	{
		$componentTag = new HtmlTag($this->formComponent->getName(), false);

		if ($this->formComponent->hasErrors()) {
			$componentTag->addHtmlTagAttribute(new HtmlTagAttribute('class', 'has-error', true));
		}
		$this->setHtmlTag($componentTag);
	}
}