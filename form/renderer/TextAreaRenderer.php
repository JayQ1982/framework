<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\field\TextAreaField;
use framework\form\FormRenderer;
use framework\html\HtmlEncoder;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

class TextAreaRenderer extends FormRenderer
{
	private TextAreaField $textAreaField;

	public function __construct(TextAreaField $textAreaField)
	{
		$this->textAreaField = $textAreaField;
	}

	public function prepare(): void
	{
		$textAreaField = $this->textAreaField;

		$attributes = [
			new HtmlTagAttribute('name', $textAreaField->getName(), true),
			new HtmlTagAttribute('id', $textAreaField->getId(), true),
			new HtmlTagAttribute('rows', $textAreaField->getRows(), true),
			new HtmlTagAttribute('cols', $textAreaField->getCols(), true),
		];

		$cssClassesForRenderer = $textAreaField->getCssClassesForRenderer();
		if (count($cssClassesForRenderer) > 0) {
			$attributes[] = new HtmlTagAttribute('class', implode(separator: ' ', array: $cssClassesForRenderer), true);
		}

		if (!is_null($textAreaField->getPlaceholder())) {
			$attributes[] = new HtmlTagAttribute('placeholder', $textAreaField->getPlaceholder(), true);
		}

		$textareaTag = new HtmlTag('textarea', false, $attributes);
		FormRenderer::addAriaAttributesToHtmlTag($textAreaField, $textareaTag);

		$value = $textAreaField->getRawValue();
		if (is_array($value)) {
			$rows = [];
			foreach ($value as $row) {
				$rows[] = HtmlEncoder::encode(value: $row);
			}
			$html = implode(separator: PHP_EOL, array: $rows);
		} else {
			$html = HtmlEncoder::encode(value: $value);
		}

		$textareaTag->addText(HtmlText::encoded($html));

		$this->setHtmlTag($textareaTag);
	}
}