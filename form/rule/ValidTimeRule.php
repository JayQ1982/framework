<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use DateTime;
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
		if (preg_match('/^(([0-1][0-9])|(2[0-3])):[0-5][0-9](:[0-5][0-9])?$/', $value) !== 1) {
			return false;
		}

		try {
			$dateTime = new DateTime($value);
			$dtErrors = DateTime::getLastErrors();
			if ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0) {
				return false;
			}
		} catch (Throwable $e) {
			return false;
		}

		$formField->setValue($dateTime->format('H:i:s'));

		return true;
	}
}
/* EOF */
