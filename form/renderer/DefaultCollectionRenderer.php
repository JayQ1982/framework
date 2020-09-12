<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\FormCollection;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;

class DefaultCollectionRenderer extends FormRenderer
{
	private FormCollection $formCollection;

	public function __construct(FormCollection $formCollection)
	{
		$this->formCollection = $formCollection;
	}

	public function prepare(): void
	{
		$componentTag = new FormTag($this->formCollection->getName(), false);

		if ($this->formCollection->hasErrors()) {
			$componentTag->addFormTagAttribute(new FormTagAttribute('class', 'has-error'));
		}

		foreach ($this->formCollection->getChildComponents() as $childComponent) {
			$componentTag->addTag($childComponent->getFormTag());
		}

		$this->setFormTag($componentTag);
	}
}
/* EOF */