<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\common\StringUtils;
use framework\form\FormRenderer;
use framework\form\rule\PhoneNumberRule;

class PhoneNumberField extends TextField
{
	private string $countryCode = 'CH';
	private string $countryCodeFieldName = 'countryCode';

	public function __construct(string $name, string $label, ?string $value = null, ?string $requiredError = null, ?string $individualInvalidError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);

		$invalidError = $individualInvalidError ?? 'Die eingegebene Telefonnummer ist ungültig.';
		$this->addRule(new PhoneNumberRule($invalidError));
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

	public function renderValue(): string
	{
		if ($this->isValueEmpty()) {
			return FormRenderer::htmlEncode($this->getRawValue());
		}

		return FormRenderer::htmlEncode(StringUtils::phoneNumber($this->getRawValue(), $this->countryCode, true));
	}
}
/* EOF */