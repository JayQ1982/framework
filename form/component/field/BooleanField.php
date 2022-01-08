<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\html\HtmlText;

class BooleanField extends CheckboxOptionsField
{
	public function __construct(string $name, HtmlText $label, bool $isCheckedByDefault)
	{
		$formOptions = new FormOptions();
		$formOptions->addItem('1', $label);

		parent::__construct($name, $label, $formOptions, $isCheckedByDefault ? ['1'] : [], null, CheckboxOptionsField::LAYOUT_CHECKBOXITEM);
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if ($overwriteValue) {
			$this->setValue(array_key_exists($this->getName(), $inputData) ? $inputData[$this->getName()] : null);
		}

		return parent::validate($inputData, false);
	}

	public function getRawValue(bool $returnNullIfEmpty = false): int
	{
		return (int)parent::getRawValue($returnNullIfEmpty);
	}
}