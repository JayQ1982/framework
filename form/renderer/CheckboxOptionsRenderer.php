<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\CheckboxOptionsField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class CheckboxOptionsRenderer extends FormRenderer
{
	private CheckboxOptionsField $checkboxOptionsField;

	public function __construct(CheckboxOptionsField $checkboxOptionsField)
	{
		$this->checkboxOptionsField = $checkboxOptionsField;
	}

	public function prepare(): void
	{
		$checkboxOptionsField = $this->checkboxOptionsField;

		$options = $checkboxOptionsField->getOptions();
		$optionsAreHTML = $checkboxOptionsField->isOptionsHTML();
		$optionsCount = count($options);
		if ($optionsCount === 0) {
			throw new LogicException('There must be at least one option!');
		}

		$ulTag = new FormTag('ul', false);

		foreach ($options as $key => $val) {

			$attributes = [
				new FormTagAttribute('type', 'checkbox'),
				new FormTagAttribute('name', $checkboxOptionsField->getName() . '[]'),
				new FormTagAttribute('id', $checkboxOptionsField->getId()),
				new FormTagAttribute('value', $key),
			];

			$checkboxValue = $checkboxOptionsField->getRawValue();
			if (is_array($checkboxValue) && in_array($key, $checkboxValue)) {
				$attributes[] = new FormTagAttribute('checked', null);
			}

			$inputTag = new FormTag('input', true, $attributes);

			$labelTag = new FormTag('label', false);
			$labelTag->addTag($inputTag);
			$labelTag->addText(new FormText(' ' . $val, $optionsAreHTML));

			$liTag = new FormTag('li', false);
			$liTag->addTag($labelTag);
			$ulTag->addTag($liTag);
		}

		$this->setFormTag($ulTag);
	}
}
/* EOF */