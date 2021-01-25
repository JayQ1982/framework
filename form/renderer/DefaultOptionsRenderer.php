<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\OptionsField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

abstract class DefaultOptionsRenderer extends FormRenderer
{
	private OptionsField $optionsField;
	private string $inputFieldType;
	private bool $acceptMultipleValues;

	protected function __construct(OptionsField $optionsField, string $inputFieldType, bool $acceptMultipleValues)
	{
		$this->optionsField = $optionsField;
		$this->inputFieldType = $inputFieldType;
		$this->acceptMultipleValues = $acceptMultipleValues;
	}

	public function prepare(): void
	{
		$optionsField = $this->optionsField;
		$options = $optionsField->getFormOptions()->getData();
		if (count($options) === 0) {
			throw new LogicException('There must be at least one option!');
		}

		$ulTag = new HtmlTag('ul', false);

		foreach ($options as $key => $htmlText) {
			$name = ($this->acceptMultipleValues) ? $optionsField->getName() . '[]' : $optionsField->getName();
			$attributes = [
				new HtmlTagAttribute('type', $this->inputFieldType, true),
				new HtmlTagAttribute('name', $name, true),
				new HtmlTagAttribute('id', $optionsField->getId() . '_' . $key, true),
				new HtmlTagAttribute('value', $key, true),
			];

			$rawValue = $optionsField->getRawValue();

			if ($this->acceptMultipleValues) {
				if (is_array($rawValue) && in_array($key, $rawValue)) {
					$attributes[] = new HtmlTagAttribute('checked', null, true);
				}
			} else {
				if ($rawValue == $key) {
					$attributes[] = new HtmlTagAttribute('checked', null, true);
				}
			}

			$inputTag = new HtmlTag('input', true, $attributes);

			$labelTag = new HtmlTag('label', false);
			$labelTag->addTag($inputTag);
			$labelTag->addText(new HtmlText(' ' . $htmlText->render(), true));

			$liTag = new HtmlTag('li', false);
			$liTag->addTag($labelTag);
			$ulTag->addTag($liTag);
		}

		$this->setHtmlTag($ulTag);
	}
}