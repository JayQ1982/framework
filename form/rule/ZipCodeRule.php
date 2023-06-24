<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * =============================================================
 * this class uses original source (10.2016) from:
 * http://www.pixelenvision.com/1708/zip-postal-code-validation-regex-php-code-for-12-countries/
 * License: "This code is free to use, distribute, modify and study. When referencing
 *           please link back to this website / post in any way e.g. direct link, credits etc."
 * -------------------------------------------------------------
 * RegEx for CH from https://jonaswitmer.ch/projekte/18-regex-fuer-schweizer-postleitzahlen-und-telefonnummern ,
 * licensed under https://creativecommons.org/licenses/by/3.0/ch/deed.de_CH , adapted to code
 * =============================================================
 */

namespace framework\form\rule;

use framework\form\component\field\ZipCodeField;
use framework\form\component\FormField;
use framework\form\FormRule;
use LogicException;

class ZipCodeRule extends FormRule
{
	private const ZIP_CODE_REGULAR_EXPRESSION = [
		//		'US'=>'^\d{5}([\-]?\d{4})?$',
		//		'UK'=>'^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$',
		'DE' => '\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b',
		//		'CA'=>'^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$',
		//		'FR'=>'^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$',
		//		'IT'=>'^(V-|I-)?[0-9]{5}$',
		//		'AU'=>'^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$',
		//		'NL'=>'^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$',
		//		'ES'=>'^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$',
		//		'DK'=>'^([D-d][K-k])?( |-)?[1-9]{1}[0-9]{3}$',
		//		'SE'=>'^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$',
		//		'BE'=>'^[1-9]{1}[0-9]{3}$',
		'CH' => '^([1-468][0-9]|[57][0-7]|9[0-6])[0-9]{2}$',
		'AT' => '^[1-9][0-9]{3}$',
	];

	public function validate(FormField $formField): bool
	{
		if (!($formField instanceof ZipCodeField)) {
			throw new LogicException(message: 'The formField must be an instance of ZipCodeField');
		}
		if ($formField->isValueEmpty()) {
			return true;
		}
		$zip = trim(string: (string)$formField->getRawValue());
		if (strlen(string: $zip) > 16) {
			return false;
		}
		$countryCode = $formField->getCountryCode();
		if (array_key_exists(key: $countryCode, array: ZipCodeRule::ZIP_CODE_REGULAR_EXPRESSION)) {
			return (preg_match(
					pattern: '/' . ZipCodeRule::ZIP_CODE_REGULAR_EXPRESSION[$countryCode] . '/i',
					subject: $zip
				) === 1
			);
		}

		return true;
	}
}