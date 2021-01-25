<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\InputField;
use framework\form\component\field\OptionsField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class InputFieldRenderer extends FormRenderer
{
	private InputField|OptionsField $formField;

	public function __construct(InputField|OptionsField $formField)
	{
		$this->formField = $formField;
	}

	public function prepare(): void
	{
		$formField = $this->formField;

		$inputTag = new HtmlTag('input', true);
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('type', $formField->getType(), true));
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('name', $formField->getName(), true));
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('id', $formField->getId(), true));

		$htmlValue = $formField->getRawValue();
		if (!is_null($htmlValue)) {
			$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('value', $htmlValue, false));
		}

		if (!is_null($formField->getPlaceholder())) {
			$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('placeholder', $formField->getPlaceholder(), true));
		}

		FormRenderer::addAriaAttributesToHtmlTag($formField, $inputTag);
		$this->setHtmlTag($inputTag);
	}
}