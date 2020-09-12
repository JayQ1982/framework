<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\TextAreaField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

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
			new FormTagAttribute('name', $textAreaField->getName()),
			new FormTagAttribute('id', $textAreaField->getId()),
			new FormTagAttribute('rows', $textAreaField->getRows()),
			new FormTagAttribute('cols', $textAreaField->getCols()),
		];

		$cssClassesForRenderer = $textAreaField->getCssClassesForRenderer();
		if (count($cssClassesForRenderer) > 0) {
			$attributes[] = new FormTagAttribute('class', implode(' ', $cssClassesForRenderer));
		}

		if (!is_null($textAreaField->getPlaceholder())) {
			$attributes[] = new FormTagAttribute('placeholder', $textAreaField->getPlaceholder());
		}

		$ariaDescribedBy = [];

		if ($textAreaField->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $textAreaField->getName() . '-error';
		}

		if (!is_null($textAreaField->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $textAreaField->getName() . '-info';
		}
		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		$textareaTag = new FormTag('textarea', false, $attributes);
		$value = $textAreaField->getRawValue();
		if (is_array($value)) {
			$rows = [];
			foreach ($value as $row) {
				$rows[] = FormRenderer::htmlEncode($row);
			}
			$html = implode(PHP_EOL, $rows);
		} else {
			$html = FormRenderer::htmlEncode($value);
		}

		$textareaTag->addText(new FormText($html, true));

		$this->setFormTag($textareaTag);
	}
}
/* EOF */