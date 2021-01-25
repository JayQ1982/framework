<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\HiddenField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

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
			new HtmlTagAttribute('type', $hiddenField->getType(), true),
			new HtmlTagAttribute('name', $hiddenField->getName(), true),
			new HtmlTagAttribute('value', $hiddenField->renderValue(), true),
		];

		$this->setHtmlTag(new HtmlTag('input', true, $attributes));
	}
}