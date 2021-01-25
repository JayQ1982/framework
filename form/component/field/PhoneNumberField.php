<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\common\StringUtils;
use framework\form\rule\PhoneNumberRule;
use framework\html\HtmlDocument;
use framework\html\HtmlText;

class PhoneNumberField extends TextField
{
	private string $countryCode = 'CH';
	private string $countryCodeFieldName = 'countryCode';
	private bool $renderInternalFormat = false;

	public function __construct(string $name, HtmlText $label, ?string $value = null, ?HtmlText $requiredError = null, ?HtmlText $individualInvalidError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);

		$invalidError = is_null($individualInvalidError) ? new HtmlText('Die eingegebene Telefonnummer ist ungültig.', true) : $individualInvalidError;
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
			return $this->getRawValue();
		}

		return HtmlDocument::htmlEncode(StringUtils::phoneNumber($this->getRawValue(), $this->countryCode, $this->renderInternalFormat));
	}
}