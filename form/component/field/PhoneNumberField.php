<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\common\StringUtils;
use framework\form\rule\PhoneNumberRule;
use framework\form\rule\RequiredRule;
use framework\html\HtmlDocument;
use framework\html\HtmlText;
use framework\vendor\libphonenumber\PhoneNumberUtil;

class PhoneNumberField extends InputField
{
	protected string $type = 'tel';

	private string $countryCode = 'CH';
	private string $countryCodeFieldName = 'countryCode';
	private bool $renderInternalFormat = false;

	public function __construct(string $name, HtmlText $label, ?string $value = null, ?HtmlText $requiredError = null, ?HtmlText $individualInvalidError = null)
	{
		parent::__construct(name: $name, label: $label, value: $value);

		if (!is_null($requiredError)) {
			$this->addRule(formRule: new RequiredRule($requiredError));
		}

		$invalidError = is_null($individualInvalidError) ? new HtmlText('Die eingegebene Telefonnummer ist ungültig.', true) : $individualInvalidError;
		$this->addRule(formRule: new PhoneNumberRule($invalidError));
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

	public function setRenderInternalFormat(bool $renderInternalFormat): void
	{
		$this->renderInternalFormat = $renderInternalFormat;
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
			return trim($this->getRawValue());
		}

		return HtmlDocument::htmlEncode(StringUtils::phoneNumber($this->getRawValue(), $this->countryCode, $this->renderInternalFormat));
	}

	public function valueHasChanged(): bool
	{
		$originalValue = trim($this->getOriginalValue());
		if ($originalValue !== '') {
			$parsedOriginalValue = StringUtils::parsePhoneNumber($this->getOriginalValue(), $this->getCountryCode());
			if (is_null($parsedOriginalValue)) {
				$originalValue = '';
			} else {
				$originalValue = PhoneNumberUtil::PLUS_SIGN . $parsedOriginalValue->getCountryCode() . '.' . $parsedOriginalValue->getNationalNumber();
			}
		}

		return ($this->getRawValue() !== $originalValue);
	}
}