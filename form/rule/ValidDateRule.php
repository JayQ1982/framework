<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\rule;

use DateTime;
use framework\form\component\FormField;
use framework\form\FormRule;
use Throwable;

class ValidDateRule extends FormRule
{
	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$value = $formField->getRawValue();
		if (preg_match('/^[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}$/', $value) !== 1) {
			return false;
		}

		try {
			$dateTime = new DateTime($value);
			$dtErrors = DateTime::getLastErrors();
			if ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0) {
				return false;
			}
		} catch (Throwable) {
			return false;
		}

		$formField->setValue($dateTime->format('Y-m-d'));

		return true;
	}
}