<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\TextAreaField;
use framework\form\FormRenderer;
use framework\html\HtmlDocument;
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
			$attributes[] = new HtmlTagAttribute('class', implode(' ', $cssClassesForRenderer), true);
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
				$rows[] = HtmlDocument::htmlEncode($row);
			}
			$html = implode(PHP_EOL, $rows);
		} else {
			$html = HtmlDocument::htmlEncode($value);
		}

		$textareaTag->addText(new HtmlText($html, true));

		$this->setHtmlTag($textareaTag);
	}
}