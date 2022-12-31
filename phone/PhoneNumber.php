<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

readonly class PhoneNumber
{
	public function __construct(
		public string $extension,
		public int    $countryCode,
		public ?bool  $italianLeadingZero,
		public int    $numberOfLeadingZeros,
		public string $nationalNumber
	) {
	}

	public static function createFromString(string $input, ?string $defaultCountryCode): PhoneNumber
	{
		$phoneNumber = (PhoneParser::getInstance())->parse(numberToParse: $input, defaultCountryCode: $defaultCountryCode);
		if (!PhoneValidator::isPossibleNumber(phoneNumber: $phoneNumber)) {
			throw new PhoneParseException(message: 'The supplied phone number is not possible.', code: -1);
		}

		return $phoneNumber;
	}

	public function getNationalSignificantNumber(): string
	{
		// If leading zero(s) have been set, we prefix this now. Note this is not a national prefix.
		$nationalNumber = '';
		if ($this->italianLeadingZero && $this->numberOfLeadingZeros > 0) {
			$zeros = str_repeat(string: '0', times: $this->numberOfLeadingZeros);
			$nationalNumber .= $zeros;
		}
		$nationalNumber .= $this->nationalNumber;

		return $nationalNumber;
	}
}