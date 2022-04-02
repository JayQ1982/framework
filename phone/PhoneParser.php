<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

class PhoneParser
{
	private static ?PhoneParser $instance = null;

	public static function getInstance(): PhoneParser
	{
		if (is_null(PhoneParser::$instance)) {
			PhoneParser::$instance = new PhoneParser();
		}

		return PhoneParser::$instance;
	}

	public function parse(string $numberToParse, ?string $defaultCountryCode): PhoneNumber
	{
		$numberToParse = trim(string: $numberToParse);
		if ($numberToParse === '') {
			throw new PhoneParseException(message: 'The string is empty.', code: PhoneParseException::EMPTY_STRING);
		}

		if (str_starts_with(haystack: $numberToParse, needle: PhoneConstants::DOUBLE_ZERO)) {
			$numberToParse = PhoneConstants::PLUS_SIGN . substr(string: $numberToParse, offset: 2);
		}

		$defaultCountryCode = str_starts_with(haystack: $numberToParse, needle: PhoneConstants::PLUS_SIGN) ? null : $defaultCountryCode;

		if (mb_strlen(string: $numberToParse) > PhoneConstants::MAX_INPUT_STRING_LENGTH) {
			throw new PhoneParseException(message: 'The string supplied was too long to parse.', code: PhoneParseException::TOO_LONG);
		}

		$nationalNumber = $this->buildNationalNumberForParsing(numberToParse: $numberToParse);

		if (!PhoneValidator::isViablePhoneNumber(number: $nationalNumber)) {
			throw new PhoneParseException(message: 'The string supplied did not seem to be a phone number.', code: PhoneParseException::NOT_A_NUMBER);
		}

		// Check, if the region supplied is valid, or that the extracted number starts with some sort of +-sign, so the number's region can be determined.
		if (!$this->checkRegionForParsing(numberToParse: $nationalNumber, defaultRegion: $defaultCountryCode)) {
			throw new PhoneParseException(message: 'Missing or invalid default region.', code: PhoneParseException::INVALID_COUNTRY_CODE);
		}

		// Attempt to parse extension first, since it doesn't require region-specific data, and we want to have the non-normalised number here.
		$extension = $this->maybeStripExtension(number: $nationalNumber);

		$regionMetaData = PhoneMetaData::getForRegion(regionCode: $defaultCountryCode);
		// Check to see if the number is given in international format, so we know whether this number is from the default region or not.
		$normalizedNationalNumber = '';
		try {
			$countryCode = $this->maybeExtractCountryCode(
				fullNumber: $nationalNumber,
				defaultRegionMetaData: $regionMetaData,
				normalizedNationalNumber: $normalizedNationalNumber
			);
		} catch (PhoneParseException $phoneParseException) {
			if ($phoneParseException->getCode() !== PhoneParseException::INVALID_COUNTRY_CODE) {
				throw $phoneParseException;
			}
			$matcher = new PhoneMatcher(pattern: PhonePatterns::PLUS_CHARS_PATTERN, subject: $nationalNumber);
			if ($matcher->lookingAt()) {
				// Strip the plus-char, and try again.
				$countryCode = $this->maybeExtractCountryCode(
					fullNumber: substr($nationalNumber, $matcher->end()),
					defaultRegionMetaData: $regionMetaData,
					normalizedNationalNumber: $normalizedNationalNumber
				);
				if ($countryCode === 0) {
					throw new PhoneParseException(message: 'Could not interpret numbers after plus-sign.', code: PhoneParseException::INVALID_COUNTRY_CODE);
				}
			}
			throw $phoneParseException;
		}
		if ($countryCode !== 0) {
			$phoneNumberRegion = PhoneRegionCountryCodeMap::getRegionCodeForCountryCode(countryCallingCode: $countryCode);
			if ($phoneNumberRegion !== $defaultCountryCode) {
				// Metadata cannot be null because the country calling code is valid.
				$regionMetaData = PhoneMetaData::getForRegionOrCallingCode(countryCallingCode: $countryCode, regionCode: $phoneNumberRegion);
			}
		} else {
			// If no extracted country calling code, use the region supplied instead.
			// The national number is just the normalized version of the number we were given to parse.
			$normalizedNationalNumber .= $this->normalize(number: $nationalNumber);
			if (!is_null(value: $defaultCountryCode)) {
				$countryCode = $regionMetaData->getCountryCode();
			}
		}
		if (mb_strlen(string: $normalizedNationalNumber) < PhoneConstants::MIN_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too short to be a phone number.', code: PhoneParseException::TOO_SHORT_NSN);
		}
		if (!is_null(value: $regionMetaData)) {
			$carrierCode = '';
			$potentialNationalNumber = $normalizedNationalNumber;
			$this->maybeStripNationalPrefixAndCarrierCode(number: $potentialNationalNumber, phoneMetaData: $regionMetaData, carrierCode: $carrierCode);
			// We require that the NSN remaining after stripping the national prefix and carrier code be long enough to be a possible length for the region.
			// Otherwise, we don't do the stripping, since the original number could be a valid short number.
			$validationResult = PhoneValidator::testNumberLength(number: $potentialNationalNumber, phoneMetaData: $regionMetaData);
			if (!in_array(needle: $validationResult, haystack: [
				PhoneValidator::TOO_SHORT,
				PhoneValidator::IS_POSSIBLE_LOCAL_ONLY,
				PhoneValidator::INVALID_LENGTH,
			])) {
				$normalizedNationalNumber = $potentialNationalNumber;
			}
		}
		$lengthOfNationalNumber = mb_strlen(string: $normalizedNationalNumber);
		if ($lengthOfNationalNumber < PhoneConstants::MIN_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too short to be a phone number.', code: PhoneParseException::TOO_SHORT_NSN);
		}
		if ($lengthOfNationalNumber > PhoneConstants::MAX_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too long to be a phone number.', code: PhoneParseException::TOO_LONG);
		}
		$italianLeadingZero = ($countryCode === 39) ? null : false;
		$numberOfLeadingZeros = 1;
		if (strlen(string: $normalizedNationalNumber) > 1 && str_starts_with(haystack: $normalizedNationalNumber, needle: '0')) {
			$italianLeadingZero = true;
			// Note that if the national number is all "0"s, the last "0" is not counted as a leading zero.
			while (
				$numberOfLeadingZeros < (strlen(string: $normalizedNationalNumber) - 1)
				&& substr(string: $normalizedNationalNumber, offset: $numberOfLeadingZeros, length: 1) === '0'
			) {
				$numberOfLeadingZeros++;
			}
		}

		$normalizedNationalNumber = ((int)$normalizedNationalNumber === 0) ? '0' : ltrim(string: $normalizedNationalNumber, characters: '0');

		return new PhoneNumber(
			extension: $extension,
			countryCode: $countryCode,
			italianLeadingZero: $italianLeadingZero,
			numberOfLeadingZeros: $numberOfLeadingZeros,
			nationalNumber: $normalizedNationalNumber
		);
	}

	private function buildNationalNumberForParsing(string $numberToParse): string
	{
		$nationalNumber = '';
		$indexOfPhoneContext = strpos($numberToParse, PhoneConstants::RFC3966_PHONE_CONTEXT);
		if ($indexOfPhoneContext !== false) {
			$phoneContextStart = $indexOfPhoneContext + mb_strlen(PhoneConstants::RFC3966_PHONE_CONTEXT);
			// If the phone context contains a phone number prefix, we need to capture it, whereas domains will be ignored.
			if (
				$phoneContextStart < (strlen($numberToParse) - 1)
				&& substr($numberToParse, $phoneContextStart, 1) == PhoneConstants::PLUS_SIGN
			) {
				// Additional parameters might follow the phone context.
				// If so, we will remove them here because the parameters after phone context are not important for parsing the phone number.
				$phoneContextEnd = strpos($numberToParse, ';', $phoneContextStart);
				if ($phoneContextEnd > 0) {
					$nationalNumber .= substr($numberToParse, $phoneContextStart, $phoneContextEnd - $phoneContextStart);
				} else {
					$nationalNumber .= substr($numberToParse, $phoneContextStart);
				}
			}

			// Now append everything between the "tel:" prefix and the phone-context.
			// This should include the national number, an optional extension or isdn-subaddress component.
			// Note we also handle the case when "tel:" is missing, as we have seen in some of the phone number inputs.
			// In that case, we append everything from the beginning.
			$indexOfRfc3966Prefix = strpos($numberToParse, PhoneConstants::RFC3966_PREFIX);
			$indexOfNationalNumber = ($indexOfRfc3966Prefix !== false) ? $indexOfRfc3966Prefix + strlen(PhoneConstants::RFC3966_PREFIX) : 0;
			$nationalNumber .= substr($numberToParse, $indexOfNationalNumber,
				$indexOfPhoneContext - $indexOfNationalNumber);
		} else {
			// Extract a possible number from the string passed in (this strips leading characters that could not be the start of a phone number.)
			$nationalNumber .= $this->extractPossibleNumber($numberToParse);
		}

		// Delete the isdn-sub address and everything after it if it is present. Note extension won't
		// appear at the same time with isdn-sub address according to paragraph 5.3 of the RFC3966 spec,
		$indexOfIsdn = strpos($nationalNumber, PhoneConstants::RFC3966_ISDN_SUBADDRESS);
		if ($indexOfIsdn > 0) {
			$nationalNumber = substr($nationalNumber, 0, $indexOfIsdn);
		}
		// If both phone context and isdn-subaddress are absent but other parameters are present, the
		// parameters are left in nationalNumber. This is because we are concerned about deleting
		// content from a potential number string when there is no strong evidence that the number is
		// actually written in RFC3966.
		return $nationalNumber;
	}

	private function extractPossibleNumber(string $number): string
	{
		$matches = [];
		if (preg_match(
				pattern: '/' . PhonePatterns::VALID_START_CHAR_PATTERN . '/ui',
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) !== 1
		) {
			return '';
		}

		$number = substr(string: $number, offset: $matches[0][1]);
		// Remove trailing non-alpha non-numerical characters.
		$trailingCharsMatcher = new PhoneMatcher(pattern: PhonePatterns::UNWANTED_END_CHAR_PATTERN, subject: $number);
		if ($trailingCharsMatcher->find() && $trailingCharsMatcher->start() > 0) {
			$number = substr(string: $number, offset: 0, length: $trailingCharsMatcher->start());
		}

		// Check for extra numbers at the end.
		if (preg_match(
				pattern: '%' . PhonePatterns::SECOND_NUMBER_START_PATTERN . '%',
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) === 1
		) {
			$number = substr(string: $number, offset: 0, length: $matches[0][1]);
		}

		return $number;
	}

	private function checkRegionForParsing(string $numberToParse, ?string $defaultRegion): bool
	{
		if (PhoneValidator::isValidRegionCode(regionCode: $defaultRegion)) {
			return true;
		}

		if (mb_strlen(string: $numberToParse) === 0) {
			return false;
		}

		return (preg_match(
				pattern: '/^' . PhonePatterns::PLUS_CHARS_PATTERN . '/ui',
				subject: $numberToParse
			) === 1);
	}

	private function maybeStripExtension(&$number): string
	{
		if (preg_match(
				pattern: PhonePatterns::EXTN_PATTERN,
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) !== 1) {
			return '';
		}

		// If we find a potential extension, and the number preceding this is a viable number, we assume it is an extension.
		if (PhoneValidator::isViablePhoneNumber(number: substr(string: $number, offset: 0, length: $matches[0][1]))) {
			// The numbers are captured into groups in the regular expression.
			for ($i = 1, $length = count(value: $matches); $i <= $length; $i++) {
				$extension = $matches[$i][0];
				if ($extension !== '') {
					// We go through the capturing groups until we find one that captured some digits.
					// If none did, then we will return the empty string.
					$number = substr(string: $number, offset: 0, length: $matches[0][1]);

					return $extension;
				}
			}
		}

		return '';
	}

	private function maybeExtractCountryCode(
		string         $fullNumber,
		?PhoneMetaData $defaultRegionMetaData,
		string         &$normalizedNationalNumber
	): int {
		if (mb_strlen(string: $fullNumber) === 0) {
			return 0;
		}
		// Set the default prefix to be something that will never match.
		$possibleCountryIddPrefix = 'NonMatch';
		if (!is_null(value: $defaultRegionMetaData)) {
			$possibleCountryIddPrefix = $defaultRegionMetaData->getInternationalPrefix();
		}
		$countryCodeSource = $this->maybeStripInternationalPrefixAndNormalize(
			number: $fullNumber,
			possibleIddPrefix: $possibleCountryIddPrefix
		);

		if ($countryCodeSource !== PhoneConstants::FROM_DEFAULT_COUNTRY) {
			if (mb_strlen(string: $fullNumber) <= PhoneConstants::MIN_LENGTH_FOR_NSN) {
				throw new PhoneParseException(
					message: 'Phone number had an IDD, but after this was not long enough to be a viable phone number.',
					code: PhoneParseException::TOO_SHORT_AFTER_IDD
				);
			}
			$potentialCountryCode = $this->extractCountryCode(fullNumber: $fullNumber, nationalNumber: $normalizedNationalNumber);

			if ($potentialCountryCode === 0) {
				throw new PhoneParseException(message: 'Country calling code supplied was not recognised.', code: PhoneParseException::INVALID_COUNTRY_CODE);
			}

			return $potentialCountryCode;
		}

		if (is_null(value: $defaultRegionMetaData)) {
			return 0;
		}
		// Check to see if the number starts with the country calling code for the default region.
		// If so, we remove the country calling code, and do some checks on the validity of the number before and after.
		$defaultCountryCode = $defaultRegionMetaData->getCountryCode();
		$defaultCountryCodeString = (string)$defaultCountryCode;
		$normalizedNumber = $fullNumber;
		if (str_starts_with(haystack: $normalizedNumber, needle: $defaultCountryCodeString)) {
			$potentialNationalNumber = substr(string: $normalizedNumber, offset: mb_strlen(string: $defaultCountryCodeString));
			$generalDesc = $defaultRegionMetaData->getGeneralDesc();
			$carrierCode = null;
			$this->maybeStripNationalPrefixAndCarrierCode(
				number: $potentialNationalNumber,
				phoneMetaData: $defaultRegionMetaData,
				carrierCode: $carrierCode
			);
			// If the number was not valid before but is valid now, or if it was too long before,
			// we consider the number with the country calling code stripped to be a better result and keep that instead.
			if (
				(
					!$this->matchNationalNumber(number: $fullNumber, numberDesc: $generalDesc)
					&& $this->matchNationalNumber(number: $potentialNationalNumber, numberDesc: $generalDesc)
				)
				|| PhoneValidator::testNumberLength(number: $fullNumber, phoneMetaData: $defaultRegionMetaData) === PhoneValidator::TOO_LONG
			) {
				$normalizedNationalNumber .= $potentialNationalNumber;

				return $defaultCountryCode;
			}
		}

		return 0;
	}

	private function maybeStripInternationalPrefixAndNormalize(string &$number, string $possibleIddPrefix): int
	{
		if (mb_strlen(string: $number) === 0) {
			return PhoneConstants::FROM_DEFAULT_COUNTRY;
		}
		$matches = [];
		if (preg_match(
				pattern: '/^' . PhonePatterns::PLUS_CHARS_PATTERN . '/' . PhoneConstants::REGEX_FLAGS,
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) === 1
		) {
			$number = mb_substr(string: $number, start: $matches[0][1] + mb_strlen(string: $matches[0][0]));
			$number = $this->normalize(number: $number);

			return PhoneConstants::FROM_NUMBER_WITH_PLUS_SIGN;
		}
		// Attempt to parse the first digits as an international prefix.
		$number = $this->normalize(number: $number);

		return $this->parsePrefixAsIdd(iddPattern: $possibleIddPrefix, number: $number) ? PhoneConstants::FROM_NUMBER_WITH_IDD : PhoneConstants::FROM_DEFAULT_COUNTRY;
	}

	private function normalize(string $number): string
	{
		if (preg_match(
				pattern: '/^' . PhonePatterns::VALID_ALPHA_PHONE_PATTERN . '$/ui',
				subject: $number
			) === 1
		) {
			return $this->normalizeHelper(number: $number);
		}

		return $this->normalizeDigits(number: $number);
	}

	private function normalizeHelper(string $number): string
	{
		$normalizationReplacements = PhoneConstants::ALPHA_PHONE_MAPPINGS;
		$normalizedNumber = '';
		$strLength = mb_strlen(string: $number, encoding: 'UTF-8');
		for ($i = 0; $i < $strLength; $i++) {
			$character = mb_substr(string: $number, start: $i, length: 1, encoding: 'UTF-8');
			if (array_key_exists(key: mb_strtoupper(string: $character, encoding: 'UTF-8'), array: $normalizationReplacements)) {
				$normalizedNumber .= $normalizationReplacements[mb_strtoupper(string: $character, encoding: 'UTF-8')];
			}
		}

		return $normalizedNumber;
	}

	private function normalizeDigits(string $number): string
	{
		$normalizedDigits = '';
		$numberAsArray = preg_split(pattern: '/(?<!^)(?!$)/u', subject: $number);
		$numericCharacters = PhoneConstants::NUMERIC_CHARACTERS;
		foreach ($numberAsArray as $character) {
			if (array_key_exists(key: $character, array: $numericCharacters)) {
				$normalizedDigits .= $numericCharacters[$character];
			} else if (is_numeric(value: $character)) {
				$normalizedDigits .= $character;
			}
		}

		return $normalizedDigits;
	}

	private function parsePrefixAsIdd(string $iddPattern, string &$number): bool
	{
		$matcher = new PhoneMatcher(pattern: $iddPattern, subject: $number);
		if ($matcher->lookingAt()) {
			$matchEnd = $matcher->end();
			$digitMatcher = new PhoneMatcher(
				pattern: PhonePatterns::CAPTURING_DIGIT_PATTERN,
				subject: substr(string: $number, offset: $matchEnd)
			);
			if ($digitMatcher->find()) {
				$normalizedGroup = $this->normalizeDigits(number: $digitMatcher->group(group: 1));
				if ($normalizedGroup === '0') {
					return false;
				}
			}
			$number = substr(string: $number, offset: $matchEnd);

			return true;
		}

		return false;
	}

	private function extractCountryCode(string $fullNumber, string &$nationalNumber): int
	{
		$numberLength = mb_strlen(string: $fullNumber);
		if (($numberLength === 0) || ($fullNumber[0] === '0')) {
			// Country codes do not begin with a '0'.
			return 0;
		}
		for ($i = 1; $i <= PhoneConstants::MAX_LENGTH_COUNTRY_CODE && $i <= $numberLength; $i++) {
			$potentialCountryCode = (int)substr(string: $fullNumber, offset: 0, length: $i);
			if (PhoneRegionCountryCodeMap::countryCodeExists(countryCodeToCheck: $potentialCountryCode)) {
				$nationalNumber .= substr($fullNumber, $i);

				return $potentialCountryCode;
			}
		}

		return 0;
	}

	private function maybeStripNationalPrefixAndCarrierCode(string &$number, PhoneMetaData $phoneMetaData, ?string &$carrierCode): void
	{
		$numberLength = mb_strlen(string: $number);
		$possibleNationalPrefix = $phoneMetaData->getNationalPrefixForParsing();
		if ($numberLength === 0 || is_null(value: $possibleNationalPrefix) || mb_strlen(string: $possibleNationalPrefix) === 0) {
			// Early return for numbers of zero length.
			return;
		}

		// Attempt to parse the first digits as a national prefix.
		$prefixMatcher = new PhoneMatcher(pattern: $possibleNationalPrefix, subject: $number);
		if (!$prefixMatcher->lookingAt()) {
			return;
		}

		$generalDesc = $phoneMetaData->getGeneralDesc();
		// Check if the original number is viable.
		$isViableOriginalNumber = $this->matchNationalNumber(number: $number, numberDesc: $generalDesc);
		// $prefixMatcher->group($numOfGroups) === null implies nothing was captured by the capturing
		// groups in $possibleNationalPrefix; therefore, no transformation is necessary, and we just
		// remove the national prefix
		$numOfGroups = $prefixMatcher->groupCount();
		$transformRule = $phoneMetaData->getNationalPrefixTransformRule();
		if (is_null(value: $transformRule)
			|| mb_strlen(string: $transformRule) === 0
			|| is_null($prefixMatcher->group(group: $numOfGroups - 1))
		) {
			// If the original number was viable, and the resultant number is not, we return.
			if (
				$isViableOriginalNumber
				&& !$this->matchNationalNumber(
					number: substr(string: $number, offset: $prefixMatcher->end()),
					numberDesc: $generalDesc
				)
			) {
				return;
			}
			if (!is_null($carrierCode) && $numOfGroups > 0 && !is_null($prefixMatcher->group(group: $numOfGroups))) {
				$carrierCode .= $prefixMatcher->group(group: 1);
			}

			$number = substr(string: $number, offset: $prefixMatcher->end());

			return;
		}

		// Check that the resultant number is still viable. If not, return. Check this by copying
		// the string and making the transformation on the copy first.
		$transformedNumber = $number;
		$transformedNumber = substr_replace(
			string: $transformedNumber,
			replace: $prefixMatcher->replaceFirst($transformRule),
			offset: 0,
			length: $numberLength
		);
		if (
			$isViableOriginalNumber
			&& !$this->matchNationalNumber(number: $transformedNumber, numberDesc: $generalDesc)
		) {
			return;
		}
		if (!is_null($carrierCode) && $numOfGroups > 1) {
			$carrierCode .= $prefixMatcher->group(group: 1);
		}
		$number = substr_replace(
			string: $number,
			replace: $transformedNumber,
			offset: 0,
			length: mb_strlen(string: $number)
		);
	}

	private function matchNationalNumber(string $number, PhoneDesc $numberDesc): bool
	{
		$nationalNumberPattern = $numberDesc->getNationalNumberPattern();
		if (strlen(string: $nationalNumberPattern) === 0) {
			return false;
		}

		return (new PhoneMatcher(pattern: $nationalNumberPattern, subject: $number))->matches();
	}
}