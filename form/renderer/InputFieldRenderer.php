<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\InputField;
use framework\form\component\field\OptionsField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;

class InputFieldRenderer extends FormRenderer
{
	/** @var InputField|OptionsField */
	private $inputField;

	/**
	 * @param InputField|OptionsField $inputField
	 */
	public function __construct($inputField)
	{
		if (!($inputField instanceof InputField) && !($inputField instanceof OptionsField)) {
			throw new LogicException("The input field must be either InputField or OptionsField.");
		}

		$this->inputField = $inputField;
	}

	public function prepare(): void
	{
		$inputField = $this->inputField;

		$htmlValue = $inputField->renderValue();

		$attributes = [
			new FormTagAttribute('type', $inputField->getType()),
			new FormTagAttribute('name', $inputField->getName()),
			new FormTagAttribute('id', $inputField->getId()),
		];
		if (!is_null($htmlValue)) {
			$attributes[] = new FormTagAttribute('value', $htmlValue);
		}

		if (!is_null($inputField->getPlaceholder())) {
			$attributes[] = new FormTagAttribute('placeholder', $inputField->getPlaceholder());
		}

		$ariaDescribedBy = [];

		if ($inputField->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $inputField->getName() . '-error';
		}

		if (!is_null($inputField->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $inputField->getName() . '-info';
		}

		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		$this->setFormTag(new FormTag('input', true, $attributes));
	}
}
/* EOF */