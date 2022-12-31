<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

class PhoneValidator
{
	/**
	 * The number length matches that of valid numbers for this region
	 */
	public const IS_POSSIBLE = 0;

	/**
	 * The number is shorter than all valid numbers for this region.
	 */
	public const TOO_SHORT = 2;

	/**
	 * The number is longer than all valid numbers for this region.
	 */
	public const TOO_LONG = 3;

	/**
	 * The number length matches that of local numbers for this region only (i.e. numbers that may
	 * be able to be dialled within an area, but do not have all the information to be dialled from
	 * anywhere inside or outside the country).
	 */
	public const IS_POSSIBLE_LOCAL_ONLY = 4;

	/**
	 * The number is longer than the shortest valid numbers for this region, shorter than the
	 * longest valid numbers for this region, and does not itself have a number length that matches
	 * valid numbers for this region. This can also be returned in the case where
	 * isPossibleNumberForTypeWithReason was called, and there are no numbers of this type at all
	 * for this region.
	 */
	public const INVALID_LENGTH = 5;

	/**
	 * Checks to see if the string of characters could possibly be a phone number at all.
	 * At the moment, checks to see that the string begins with at least 2 digits, ignoring any punctuation commonly found in phone numbers.
	 * This method does not require the number to be normalized in advance - but does assume that leading non-number symbols have been removed.
	 *
	 * @param string $number Number to be checked for viability as a phone number
	 *
	 * @return boolean true if the number could be a phone number of some sort, otherwise false
	 */
	public static function isViablePhoneNumber(string $number): bool
	{
		if (mb_strlen(string: $number) < PhoneConstants::MIN_LENGTH_FOR_NSN) {
			return false;
		}

		return (preg_match(
				pattern: PhonePatterns::VALID_PHONE_NUMBER_PATTERN,
				subject: $number
			) === 1
		);
	}

	public static function isValidRegionCode(?string $regionCode): bool
	{
		return !is_null($regionCode) && in_array($regionCode, PhoneRegionCountryCodeMap::getSupportedRegions());
	}

	public static function testNumberLength(string $number, PhoneMetaData $phoneMetaData): int
	{
		$descForType = $phoneMetaData->getGeneralDesc();
		$possibleLengths = (count($descForType->getPossibleLength()) === 0) ? $phoneMetaData->getGeneralDesc()->getPossibleLength() : $descForType->getPossibleLength();
		$localLengths = $descForType->getPossibleLengthLocalOnly();
		if ($possibleLengths[0] === -1) {
			return PhoneValidator::INVALID_LENGTH;
		}
		$actualLength = mb_strlen(string: $number);
		if (in_array(needle: $actualLength, haystack: $localLengths)) {
			return PhoneValidator::IS_POSSIBLE_LOCAL_ONLY;
		}
		$minimumLength = (int)reset(array: $possibleLengths);
		if ($minimumLength === $actualLength) {
			return PhoneValidator::IS_POSSIBLE;
		}
		if ($minimumLength > $actualLength) {
			return PhoneValidator::TOO_SHORT;
		}
		if (
			array_key_exists(key: (count(value: $possibleLengths) - 1), array: $possibleLengths)
			&& $possibleLengths[count(value: $possibleLengths) - 1] < $actualLength
		) {
			return PhoneValidator::TOO_LONG;
		}

		array_shift(array: $possibleLengths);

		return in_array(needle: $actualLength, haystack: $possibleLengths) ? PhoneValidator::IS_POSSIBLE : PhoneValidator::INVALID_LENGTH;
	}

	public static function isPossibleNumber(PhoneNumber $phoneNumber): bool
	{
		$result = PhoneValidator::isPossibleNumberWithReason(phoneNumber: $phoneNumber);

		return ($result === PhoneValidator::IS_POSSIBLE || $result === PhoneValidator::IS_POSSIBLE_LOCAL_ONLY);
	}

	private static function isPossibleNumberWithReason(PhoneNumber $phoneNumber): int
	{
		$nationalNumber = $phoneNumber->getNationalSignificantNumber();
		$countryCode = $phoneNumber->countryCode;
		$regionCode = PhoneRegionCountryCodeMap::getRegionCodeForCountryCode(countryCallingCode: $countryCode);
		// Metadata cannot be null because the country calling code is valid.
		$phoneMetaData = PhoneMetaData::getForRegionOrCallingCode(countryCallingCode: $countryCode, regionCode: $regionCode);

		return PhoneValidator::testNumberLength(
			number: $nationalNumber,
			phoneMetaData: $phoneMetaData
		);
	}
}