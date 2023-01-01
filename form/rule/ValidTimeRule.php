<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use DateTimeImmutable;
use framework\form\component\FormField;
use framework\form\FormRule;
use Throwable;

class ValidTimeRule extends FormRule
{
	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}
		$value = $formField->getRawValue();
		if (preg_match(pattern: '/^(([0-1]\d)|(2[0-3])):[0-5]\d(:[0-5]\d)?$/', subject: $value) !== 1) {
			return false;
		}
		try {
			$dateTimeImmutable = new DateTimeImmutable(datetime: $value);
			if (DateTimeImmutable::getLastErrors() !== false) {
				return false;
			}
			$formField->setValue(value: $dateTimeImmutable->format(format: 'H:i:s'));
		} catch (Throwable) {
			return false;
		}

		return true;
	}
}