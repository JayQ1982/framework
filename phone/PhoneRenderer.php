<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhoneRenderer
{
	public static function renderInternalFormat(PhoneNumber $phoneNumber): string
	{
		return PhoneConstants::PLUS_SIGN . $phoneNumber->getCountryCode() . '.' . $phoneNumber->getNationalNumber();
	}

	public static function renderInternationalFormat(PhoneNumber $phoneNumber): string
	{
		$countryCallingCode = $phoneNumber->getCountryCode();
		$nationalSignificantNumber = $phoneNumber->getNationalSignificantNumber();

		if (!PhoneRegionCountryCodeMap::countryCodeExists(countryCodeToCheck: $countryCallingCode)) {
			return $nationalSignificantNumber;
		}

		// Note getRegionCodeForCountryCode() is used because formatting information for regions which share a country calling code is contained by only one region for performance reasons.
		// For example, for NANPA regions it will be contained in the metadata for US.
		$regionCode = PhoneRegionCountryCodeMap::getRegionCodeForCountryCode(countryCallingCode: $countryCallingCode);
		// Metadata cannot be null because the country calling code is valid (which means that the region code cannot be ZZ and must be one of our supported region codes).
		$phoneMetaData = PhoneMetaData::getForRegionOrCallingCode(countryCallingCode: $countryCallingCode, regionCode: $regionCode);
		$formattedNumber = PhoneRenderer::formatNsn(nationalSignificantNumber: $nationalSignificantNumber, phoneMetaData: $phoneMetaData);
		PhoneRenderer::maybeAppendFormattedExtension(phoneNumber: $phoneNumber, phoneMetaData: $phoneMetaData, formattedNumber: $formattedNumber);

		return PhoneConstants::PLUS_SIGN . $countryCallingCode . ' ' . $formattedNumber;
	}

	private static function formatNsn(string $nationalSignificantNumber, PhoneMetaData $phoneMetaData): string
	{
		$intlNumberFormats = $phoneMetaData->intlNumberFormats();
		// When the intlNumberFormats exists, we use that to format national number for the INTERNATIONAL format instead of using the numberDesc.numberFormats.
		$availableFormats = (count($intlNumberFormats) === 0) ? $phoneMetaData->numberFormats() : $intlNumberFormats;
		$formattingPattern = PhoneRenderer::chooseFormattingPatternForNumber(
			availableFormats: $availableFormats,
			nationalNumber: $nationalSignificantNumber
		);

		return is_null($formattingPattern) ? $nationalSignificantNumber : PhoneRenderer::formatNsnUsingPattern(nationalSignificantNumber: $nationalSignificantNumber, formattingPattern: $formattingPattern);
	}

	/**
	 * @param PhoneFormat[] $availableFormats
	 * @param string        $nationalNumber
	 *
	 * @return PhoneFormat|null
	 */
	private static function chooseFormattingPatternForNumber(array $availableFormats, string $nationalNumber): ?PhoneFormat
	{
		foreach ($availableFormats as $numFormat) {
			$leadingDigitsPatternMatcher = null;
			$size = $numFormat->leadingDigitsPatternSize();
			// We always use the last leading_digits_pattern, as it is the most detailed.
			if ($size > 0) {
				$leadingDigitsPatternMatcher = new PhoneMatcher(
					pattern: $numFormat->getLeadingDigitsPattern($size - 1),
					subject: $nationalNumber
				);
			}
			if ($size == 0 || $leadingDigitsPatternMatcher->lookingAt()) {
				$m = new PhoneMatcher(
					pattern: $numFormat->getPattern(),
					subject: $nationalNumber
				);
				if ($m->matches() > 0) {
					return $numFormat;
				}
			}
		}

		return null;
	}

	private static function formatNsnUsingPattern(string $nationalSignificantNumber, PhoneFormat $formattingPattern): string
	{
		return (new PhoneMatcher(
			pattern: $formattingPattern->getPattern(),
			subject: $nationalSignificantNumber
		))->replaceAll(replacement: $formattingPattern->getFormat());
	}

	private static function maybeAppendFormattedExtension(PhoneNumber $phoneNumber, PhoneMetaData $phoneMetaData, string &$formattedNumber)
	{
		if (mb_strlen(string: $phoneNumber->getExtension()) > 0) {
			if ($phoneMetaData->hasPreferredExtnPrefix()) {
				$formattedNumber .= $phoneMetaData->getPreferredExtnPrefix() . $phoneNumber->getExtension();
			} else {
				$formattedNumber .= PhoneConstants::DEFAULT_EXTN_PREFIX . $phoneNumber->getExtension();
			}
		}
	}
}