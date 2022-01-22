<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhoneNumber
{
	public function __construct(
		private string $extension,
		private int    $countryCode,
		private ?bool  $italianLeadingZero,
		private int    $numberOfLeadingZeros,
		private string $nationalNumber
	) {
	}

	public static function createFromString(string $input, ?string $defaultCountryCode): PhoneNumber
	{
		$phoneNumber = (PhoneParser::getInstance())->parse(numberToParse: $input, defaultCountryCode: $defaultCountryCode);
		if (!PhoneValidator::isPossibleNumber(number: $phoneNumber)) {
			throw new PhoneParseException(message: 'The supplied phone number is not possible.', code: -1);
		}

		return $phoneNumber;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function getCountryCode(): int
	{
		return $this->countryCode;
	}

	public function isItalianLeadingZero(): ?bool
	{
		return $this->italianLeadingZero;
	}

	public function getNumberOfLeadingZeros(): int
	{
		return $this->numberOfLeadingZeros;
	}

	public function getNationalNumber(): string
	{
		return $this->nationalNumber;
	}

	public function getNationalSignificantNumber(): string
	{
		// If leading zero(s) have been set, we prefix this now. Note this is not a national prefix.
		$nationalNumber = '';
		if ($this->isItalianLeadingZero() && $this->getNumberOfLeadingZeros() > 0) {
			$zeros = str_repeat('0', $this->getNumberOfLeadingZeros());
			$nationalNumber .= $zeros;
		}
		$nationalNumber .= $this->getNationalNumber();

		return $nationalNumber;
	}
}