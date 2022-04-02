<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\datacheck\Sanitizer;
use framework\form\rule\PhoneNumberRule;
use framework\form\rule\RequiredRule;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;
use framework\phone\PhoneNumber;
use framework\phone\PhoneParseException;
use framework\phone\PhoneRenderer;

class PhoneNumberField extends InputField
{
	protected string $type = 'tel';

	private string $countryCode = 'CH';
	private string $countryCodeFieldName = 'countryCode';
	private bool $renderInternalFormat = false;

	public function __construct(
		string    $name,
		HtmlText  $label,
		?string   $value,
		HtmlText  $invalidErrorMessage,
		?HtmlText $requiredErrorMessage = null
	) {
		parent::__construct(name: $name, label: $label, value: $value);

		if (!is_null(value: $requiredErrorMessage)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredErrorMessage));
		}

		$this->addRule(formRule: new PhoneNumberRule(defaultErrorMessage: $invalidErrorMessage));
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
		if (array_key_exists(key: $this->countryCodeFieldName, array: $inputData)) {
			$this->countryCode = $inputData[$this->countryCodeFieldName];
		}

		return parent::validate(inputData: $inputData, overwriteValue: $overwriteValue);
	}

	public function renderValue(): string
	{
		if ($this->isValueEmpty()) {
			return '';
		}

		$currentValue = $this->getRawValue();

		try {
			$phoneNumber = PhoneNumber::createFromString(input: $currentValue, defaultCountryCode: $this->countryCode);
		} catch (PhoneParseException) {
			return HtmlEncoder::encode(value: $currentValue);
		}

		if ($this->renderInternalFormat) {
			return PhoneRenderer::renderInternalFormat(phoneNumber: $phoneNumber);
		}

		return PhoneRenderer::renderInternationalFormat(phoneNumber: $phoneNumber);
	}

	public function valueHasChanged(): bool
	{
		$originalValue = Sanitizer::trimmedString(input: $this->getOriginalValue());
		if ($originalValue !== '') {
			try {
				$parsedOriginalValue = PhoneNumber::createFromString(input: $this->getOriginalValue(), defaultCountryCode: $this->getCountryCode());
				$originalValue = PhoneRenderer::renderInternalFormat(phoneNumber: $parsedOriginalValue);
			} catch (PhoneParseException) {
				$originalValue = '';
			}
		}

		return ($this->getRawValue() !== $originalValue);
	}
}