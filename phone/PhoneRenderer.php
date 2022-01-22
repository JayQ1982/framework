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
		$metadata = PhoneMetaData::getForRegionOrCallingCode(countryCallingCode: $countryCallingCode, regionCode: $regionCode);
		$formattedNumber = PhoneRenderer::formatNsn($nationalSignificantNumber, $metadata);
		PhoneRenderer::maybeAppendFormattedExtension($phoneNumber, $metadata, $formattedNumber);

		return PhoneConstants::PLUS_SIGN . $countryCallingCode . ' ' . $formattedNumber;
	}

	private static function formatNsn($number, PhoneMetaData $phoneMetaData)
	{
		$intlNumberFormats = $phoneMetaData->intlNumberFormats();
		// When the intlNumberFormats exists, we use that to format national number for the INTERNATIONAL format instead of using the numberDesc.numberFormats.
		$availableFormats = (count($intlNumberFormats) === 0) ? $phoneMetaData->numberFormats() : $phoneMetaData->intlNumberFormats();
		$formattingPattern = PhoneRenderer::chooseFormattingPatternForNumber($availableFormats, $number);

		return ($formattingPattern === null) ? $number : PhoneRenderer::formatNsnUsingPattern($number, $formattingPattern);
	}

	/**
	 * @param PhoneFormat[] $availableFormats
	 * @param               $nationalNumber
	 *
	 * @return PhoneFormat|null
	 */
	private static function chooseFormattingPatternForNumber(array $availableFormats, $nationalNumber): ?PhoneFormat
	{
		foreach ($availableFormats as $numFormat) {
			$leadingDigitsPatternMatcher = null;
			$size = $numFormat->leadingDigitsPatternSize();
			// We always use the last leading_digits_pattern, as it is the most detailed.
			if ($size > 0) {
				$leadingDigitsPatternMatcher = new PhoneMatcher(
					$numFormat->getLeadingDigitsPattern($size - 1),
					$nationalNumber
				);
			}
			if ($size == 0 || $leadingDigitsPatternMatcher->lookingAt()) {
				$m = new PhoneMatcher($numFormat->getPattern(), $nationalNumber);
				if ($m->matches() > 0) {
					return $numFormat;
				}
			}
		}

		return null;
	}

	private static function formatNsnUsingPattern(string $nationalNumber, PhoneFormat $formattingPattern): string
	{
		$numberFormatRule = $formattingPattern->getFormat();
		$m = new PhoneMatcher($formattingPattern->getPattern(), $nationalNumber);

		return $m->replaceAll($numberFormatRule);
	}

	private static function maybeAppendFormattedExtension(PhoneNumber $number, ?PhoneMetaData $phoneMetaData, &$formattedNumber)
	{
		if (mb_strlen($number->getExtension()) > 0) {
			if (!empty($phoneMetaData) && $phoneMetaData->hasPreferredExtnPrefix()) {
				$formattedNumber .= $phoneMetaData->getPreferredExtnPrefix() . $number->getExtension();
			} else {
				$formattedNumber .= PhoneConstants::DEFAULT_EXTN_PREFIX . $number->getExtension();
			}
		}
	}
}