<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
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

		if ($this->formComponent->hasErrors(withChildElements: true)) {
			$componentTag->addHtmlTagAttribute(new HtmlTagAttribute('class', 'has-error', true));
		}
		$this->setHtmlTag($componentTag);
	}
}