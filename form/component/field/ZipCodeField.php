<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ZipCodeRule;
use framework\form\settings\AutoCompleteValue;
use framework\html\HtmlText;

class ZipCodeField extends TextField
{
	public function __construct(
		string                  $name,
		HtmlText                $label,
		?string                 $value = null,
		?HtmlText               $requiredError = null,
		?HtmlText               $individualInvalidError = null,
		private string          $countryCode = 'CH',
		private readonly string $countryCodeFieldName = 'countryCode',
		?string                 $placeholder = null,
		?AutoCompleteValue      $autoComplete = null
	) {
		parent::__construct(
			name: $name,
			label: $label,
			value: $value,
			requiredError: $requiredError,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		$invalidError = is_null(value: $individualInvalidError) ? HtmlText::encoded(textContent: 'Die eingegebene PLZ ist ungültig.') : $individualInvalidError;
		$this->addRule(formRule: new ZipCodeRule(defaultErrorMessage: $invalidError));
	}

	public function getCountryCode(): string
	{
		return $this->countryCode;
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if (array_key_exists(key: $this->countryCodeFieldName, array: $inputData)) {
			$this->countryCode = $inputData[$this->countryCodeFieldName];
		}
		if (!parent::validate(inputData: $inputData, overwriteValue: $overwriteValue)) {
			return false;
		}

		return true;
	}
}