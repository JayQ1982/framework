<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use LogicException;
use framework\common\StringUtils;
use framework\form\component\field\PhoneNumberField;
use framework\form\component\FormField;
use framework\form\FormRule;
use framework\vendor\libphonenumber\PhoneNumberUtil;

class PhoneNumberRule extends FormRule
{
	public function validate(FormField $formField): bool
	{
		if (!($formField instanceof PhoneNumberField)) {
			throw new LogicException("The formField must be an instance of PhoneNumberField");
		}

		if ($formField->isValueEmpty()) {
			return true;
		}

		$phone = trim($formField->getRawValue());

		$phoneNumber = StringUtils::parsePhoneNumber($phone, $formField->getCountryCode());
		if (is_null($phoneNumber)) {
			return false;
		}

		$formField->setValue(PhoneNumberUtil::PLUS_SIGN . $phoneNumber->getCountryCode() . '.' . $phoneNumber->getNationalNumber());

		return true;
	}
}
/*EOF*/