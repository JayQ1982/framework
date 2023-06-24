<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\datacheck\Sanitizer;
use framework\form\rule\PhoneNumberRule;
use framework\form\rule\RequiredRule;
use framework\form\settings\AutoCompleteValue;
use framework\form\settings\InputTypeValue;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;
use framework\phone\PhoneNumber;
use framework\phone\PhoneParseException;
use framework\phone\PhoneRenderer;

class PhoneNumberField extends InputField
{
	private string $countryCode;

	public function __construct(
		string                 $name,
		HtmlText               $label,
		?string                $value,
		HtmlText               $invalidErrorMessage,
		?HtmlText              $requiredErrorMessage = null,
		string                 $countryCode = 'CH',
		public readonly string $countryCodeFieldName = 'countryCode',
		public readonly bool   $renderInternalFormat = false,
		?string                $placeholder = null,
		?AutoCompleteValue     $autoComplete = null
	) {
		parent::__construct(
			inputType: InputTypeValue::TEL,
			name: $name,
			label: $label,
			value: $value,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		$this->countryCode = $countryCode;
		if (!is_null(value: $requiredErrorMessage)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredErrorMessage));
		}
		$this->addRule(formRule: new PhoneNumberRule(defaultErrorMessage: $invalidErrorMessage));
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
				$parsedOriginalValue = PhoneNumber::createFromString(input: $this->getOriginalValue(), defaultCountryCode: $this->countryCode);
				$originalValue = PhoneRenderer::renderInternalFormat(phoneNumber: $parsedOriginalValue);
			} catch (PhoneParseException) {
				$originalValue = '';
			}
		}

		return ($this->getRawValue() !== $originalValue);
	}
}