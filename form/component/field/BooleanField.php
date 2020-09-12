<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

class BooleanField extends CheckboxOptionsField
{
	public function __construct(string $name, string $label, bool $labelIsHTML, $value)
	{
		parent::__construct($name, $label, [1 => $label], $labelIsHTML, (int)$value, null, self::LAYOUT_CHECKBOXITEM);
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
/* EOF */