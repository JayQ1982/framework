<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\layout\CheckboxOptionsLayout;
use framework\form\FormOptions;
use framework\html\HtmlText;

class BooleanField extends CheckboxOptionsField
{
	public function __construct(
		string                $name,
		HtmlText              $label,
		bool                  $isCheckedByDefault,
		?HtmlText             $requiredError = null,
		CheckboxOptionsLayout $layout = CheckboxOptionsLayout::CHECKBOX_ITEM
	) {
		$formOptions = new FormOptions();
		$formOptions->addItem(key: '1', htmlText: $label);
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValues: $isCheckedByDefault ? ['1'] : [],
			requiredError: $requiredError,
			layout: $layout
		);
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if ($overwriteValue) {
			$this->setValue(value: array_key_exists(key: $this->getName(), array: $inputData) ? $inputData[$this->getName()] : null);
		}

		return parent::validate(inputData: $inputData, overwriteValue: false);
	}

	public function getRawValue(bool $returnNullIfEmpty = false): int
	{
		return (int)parent::getRawValue(returnNullIfEmpty: $returnNullIfEmpty);
	}
}