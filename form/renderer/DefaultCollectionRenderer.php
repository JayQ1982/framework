<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\FormCollection;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class DefaultCollectionRenderer extends FormRenderer
{
	private FormCollection $formCollection;

	public function __construct(FormCollection $formCollection)
	{
		$this->formCollection = $formCollection;
	}

	public function prepare(): void
	{
		$componentTag = new HtmlTag($this->formCollection->getName(), false);

		if ($this->formCollection->hasErrors(withChildElements: true)) {
			$componentTag->addHtmlTagAttribute(new HtmlTagAttribute('class', 'has-error', true));
		}

		foreach ($this->formCollection->getChildComponents() as $childComponent) {
			$componentTag->addTag($childComponent->getHtmlTag());
		}

		$this->setHtmlTag($componentTag);
	}
}