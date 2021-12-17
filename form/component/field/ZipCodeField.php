<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\ZipCodeRule;
use framework\html\HtmlText;

class ZipCodeField extends TextField
{
	private string $countryCode = 'CH';
	private string $countryCodeFieldName = 'countryCode';

	public function __construct(string $name, HtmlText $label, ?string $value = null, ?HtmlText $requiredError = null, ?HtmlText $individualInvalidError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);

		$invalidError = is_null($individualInvalidError) ? HtmlText::encoded('Die eingegebene PLZ ist ungültig.') : $individualInvalidError;
		$this->addRule(new ZipCodeRule($invalidError));
	}

	public function getCountryCode(): string
	{
		return $this->countryCode;
	}

	public function setCountryCode(string $countryCode): void
	{
		$this->countryCode = $countryCode;
	}

	public function setCountryCodeFieldName(string $fieldName): void
	{
		$this->countryCodeFieldName = $fieldName;
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if (array_key_exists($this->countryCodeFieldName, $inputData)) {
			$this->countryCode = $inputData[$this->countryCodeFieldName];
		}

		if (!parent::validate($inputData, $overwriteValue)) {
			return false;
		}

		return true;
	}
}