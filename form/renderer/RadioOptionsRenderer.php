<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\RadioOptionsField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class RadioOptionsRenderer extends FormRenderer
{
	private RadioOptionsField $radioOptionsField;

	public function __construct(RadioOptionsField $radioOptionsField)
	{
		$this->radioOptionsField = $radioOptionsField;
	}

	public function prepare(): void
	{
		$radioOptionsField = $this->radioOptionsField;
		$options = $radioOptionsField->getOptions();
		$optionsAreHTML = $radioOptionsField->isOptionsHTML();

		$optionsCount = count($options);
		if ($optionsCount === 0) {
			throw new LogicException('There must be at least one option!');
		}

		$ulTag = new FormTag('ul', false);

		foreach ($options as $key => $val) {

			$attributes = [
				new FormTagAttribute('type', 'radio'),
				new FormTagAttribute('name', $radioOptionsField->getName()),
				new FormTagAttribute('id', $radioOptionsField->getId() . '_' . $key),
				new FormTagAttribute('value', $key),
			];

			$radioOptionValue = $radioOptionsField->getRawValue();
			if ($radioOptionValue == $key) {
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