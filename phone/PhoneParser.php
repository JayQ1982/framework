<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
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
		$numberToParse = trim($numberToParse);
		if ($numberToParse === '') {
			throw new PhoneParseException(message: 'The string is empty.', code: PhoneParseException::EMPTY_STRING);
		}

		if (str_starts_with(haystack: $numberToParse, needle: PhoneConstants::DOUBLE_ZERO)) {
			$numberToParse = PhoneConstants::PLUS_SIGN . substr($numberToParse, 2);
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
			$phoneNumberRegion = PhoneRegionCountryCodeMap::getRegionCodeForCountryCode($countryCode);
			if ($phoneNumberRegion !== $defaultCountryCode) {
				// Metadata cannot be null because the country calling code is valid.
				$regionMetaData = PhoneMetaData::getForRegionOrCallingCode($countryCode, $phoneNumberRegion);
			}
		} else {
			// If no extracted country calling code, use the region supplied instead.
			// The national number is just the normalized version of the number we were given to parse.
			$normalizedNationalNumber .= $this->normalize($nationalNumber);
			if ($defaultCountryCode !== null) {
				$countryCode = $regionMetaData->getCountryCode();
			}
		}
		if (mb_strlen($normalizedNationalNumber) < PhoneConstants::MIN_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too short to be a phone number.', code: PhoneParseException::TOO_SHORT_NSN);
		}
		if (!is_null($regionMetaData)) {
			$carrierCode = '';
			$potentialNationalNumber = $normalizedNationalNumber;
			$this->maybeStripNationalPrefixAndCarrierCode($potentialNationalNumber, $regionMetaData, $carrierCode);
			// We require that the NSN remaining after stripping the national prefix and carrier code be long enough to be a possible length for the region.
			// Otherwise, we don't do the stripping, since the original number could be a valid short number.
			$validationResult = PhoneValidator::testNumberLength($potentialNationalNumber, $regionMetaData);
			if ($validationResult !== PhoneValidator::TOO_SHORT
				&& $validationResult !== PhoneValidator::IS_POSSIBLE_LOCAL_ONLY
				&& $validationResult !== PhoneValidator::INVALID_LENGTH) {
				$normalizedNationalNumber = $potentialNationalNumber;
			}
		}
		$lengthOfNationalNumber = mb_strlen($normalizedNationalNumber);
		if ($lengthOfNationalNumber < PhoneConstants::MIN_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too short to be a phone number.', code: PhoneParseException::TOO_SHORT_NSN);
		}
		if ($lengthOfNationalNumber > PhoneConstants::MAX_LENGTH_FOR_NSN) {
			throw new PhoneParseException(message: 'The string supplied is too long to be a phone number.', code: PhoneParseException::TOO_LONG);
		}
		$italianLeadingZero = ($countryCode === 39) ? null : false;
		$numberOfLeadingZeros = 1;
		if (strlen($normalizedNationalNumber) > 1 && str_starts_with($normalizedNationalNumber, '0')) {
			$italianLeadingZero = true;
			// Note that if the national number is all "0"s, the last "0" is not counted as a leading zero.
			while ($numberOfLeadingZeros < (strlen($normalizedNationalNumber) - 1) &&
				substr($normalizedNationalNumber, $numberOfLeadingZeros, 1) == '0') {
				$numberOfLeadingZeros++;
			}
		}

		if ((int)$normalizedNationalNumber === 0) {
			$normalizedNationalNumber = '0';
		} else {
			$normalizedNationalNumber = ltrim($normalizedNationalNumber, '0');
		}

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
			) !== 1) {
			return '';
		}

		$number = substr(string: $number, offset: $matches[0][1]);
		// Remove trailing non-alpha non-numerical characters.
		if (preg_match(
				pattern: '/' . PhonePatterns::UNWANTED_END_CHAR_PATTERN . '/ui',
				subject: $number,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) === 1) {
			$positions = [];
			foreach ($groups as $group) {
				$positions[] = [
					$group[0],
					mb_strlen(mb_strcut($number, 0, $group[1])),
				];
			}
			$start = $positions[0][1];
			if ($start > 0) {
				$number = substr(string: $number, offset: 0, length: $start);
			}
		}

		// Check for extra numbers at the end.
		if (preg_match(
				pattern: '%' . PhonePatterns::SECOND_NUMBER_START_PATTERN . '%',
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) === 1) {
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
				subject: $numberToParse,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) === 1);
	}

	private function maybeStripExtension(&$number): string
	{
		if (preg_match(
				pattern: '/' . PhonePatterns::EXTN_PATTERNS_FOR_PARSING . '$/ui',
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) !== 1) {
			return '';
		}

		// If we find a potential extension, and the number preceding this is a viable number, we assume it is an extension.
		if (PhoneValidator::isViablePhoneNumber(substr($number, 0, $matches[0][1]))) {
			// The numbers are captured into groups in the regular expression.
			for ($i = 1, $length = count($matches); $i <= $length; $i++) {
				if ($matches[$i][0] != '') {
					// We go through the capturing groups until we find one that captured some digits.
					// If none did, then we will return the empty string.
					$extension = $matches[$i][0];
					$number = substr($number, 0, $matches[0][1]);

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
		if (mb_strlen($fullNumber) === 0) {
			return 0;
		}
		// Set the default prefix to be something that will never match.
		$possibleCountryIddPrefix = 'NonMatch';
		if ($defaultRegionMetaData !== null) {
			$possibleCountryIddPrefix = $defaultRegionMetaData->getInternationalPrefix();
		}
		$countryCodeSource = $this->maybeStripInternationalPrefixAndNormalize(number: $fullNumber, possibleIddPrefix: $possibleCountryIddPrefix);

		if ($countryCodeSource !== PhoneConstants::FROM_DEFAULT_COUNTRY) {
			if (mb_strlen($fullNumber) <= PhoneConstants::MIN_LENGTH_FOR_NSN) {
				throw new PhoneParseException(
					message: 'Phone number had an IDD, but after this was not long enough to be a viable phone number.',
					code: PhoneParseException::TOO_SHORT_AFTER_IDD
				);
			}
			$potentialCountryCode = $this->extractCountryCode($fullNumber, $normalizedNationalNumber);

			if ($potentialCountryCode === 0) {
				// If this fails, they must be using a strange country calling code that we don't recognize, or that doesn't exist.
				throw new PhoneParseException(message: 'Country calling code supplied was not recognised.', code: PhoneParseException::INVALID_COUNTRY_CODE);
			}

			return $potentialCountryCode;
		}

		if (is_null($defaultRegionMetaData)) {
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
				metaData: $defaultRegionMetaData,
				carrierCode: $carrierCode
			);
			// If the number was not valid before but is valid now, or if it was too long before,
			// we consider the number with the country calling code stripped to be a better result and keep that instead.
			if ((!$this->matchNationalNumber($fullNumber, $generalDesc)
					&& $this->matchNationalNumber($potentialNationalNumber, $generalDesc))
				|| PhoneValidator::testNumberLength($fullNumber, $defaultRegionMetaData) === PhoneValidator::TOO_LONG
			) {
				$normalizedNationalNumber .= $potentialNationalNumber;

				return $defaultCountryCode;
			}
		}

		return 0;
	}

	private function maybeStripInternationalPrefixAndNormalize(string &$number, string $possibleIddPrefix): int
	{
		if (mb_strlen($number) === 0) {
			return PhoneConstants::FROM_DEFAULT_COUNTRY;
		}
		$matches = [];
		// Check to see if the number begins with one or more plus signs.
		if (preg_match(
				pattern: '/^' . PhonePatterns::PLUS_CHARS_PATTERN . '/' . PhoneConstants::REGEX_FLAGS,
				subject: $number,
				matches: $matches,
				flags: PREG_OFFSET_CAPTURE
			) === 1
		) {
			$number = mb_substr($number, $matches[0][1] + mb_strlen($matches[0][0]));
			// Can now normalize the rest of the number since we've consumed the "+" sign at the start.
			$number = $this->normalize($number);

			return PhoneConstants::FROM_NUMBER_WITH_PLUS_SIGN;
		}
		// Attempt to parse the first digits as an international prefix.
		$number = $this->normalize($number);

		return $this->parsePrefixAsIdd($possibleIddPrefix, $number) ? PhoneConstants::FROM_NUMBER_WITH_IDD : PhoneConstants::FROM_DEFAULT_COUNTRY;
	}

	private function normalize(string $number): string
	{
		if (preg_match(
				pattern: '/^' . PhonePatterns::VALID_ALPHA_PHONE_PATTERN . '$/ui',
				subject: $number,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) === 1
		) {
			return $this->normalizeHelper($number);
		}

		return $this->normalizeDigits(number: $number);
	}

	private function normalizeHelper(string $number): string
	{
		$normalizationReplacements = PhoneConstants::ALPHA_PHONE_MAPPINGS;
		$normalizedNumber = '';
		$strLength = mb_strlen($number, 'UTF-8');
		for ($i = 0; $i < $strLength; $i++) {
			$character = mb_substr($number, $i, 1, 'UTF-8');
			if (isset($normalizationReplacements[mb_strtoupper($character, 'UTF-8')])) {
				$normalizedNumber .= $normalizationReplacements[mb_strtoupper($character, 'UTF-8')];
			}
			// If neither of the above are true, we remove this character.
		}

		return $normalizedNumber;
	}

	private function normalizeDigits(string $number): string
	{
		$normalizedDigits = '';
		$numberAsArray = preg_split('/(?<!^)(?!$)/u', $number);
		foreach ($numberAsArray as $character) {
			// Check if we are in the unicode number range
			if (array_key_exists($character, PhoneConstants::NUMERIC_CHARACTERS)) {
				$normalizedDigits .= PhoneConstants::NUMERIC_CHARACTERS[$character];
			} else if (is_numeric($character)) {
				$normalizedDigits .= $character;
			}
		}

		return $normalizedDigits;
	}

	private function parsePrefixAsIdd(string $iddPattern, string &$number): bool
	{
		if (preg_match(
				pattern: '/^' . $iddPattern . '/ui',
				subject: $number,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) === 1
		) {
			$positions = [];
			foreach ($groups as $group) {
				$positions[] = [
					$group[0],
					mb_strlen(mb_strcut($number, 0, $group[1])),
				];
			}
			$matchEnd = $positions[0][1] + mb_strlen($positions[0][0]);

			// Only strip this if the first digit after the match is not a 0, since country calling codes cannot begin with 0.
			if (preg_match(
					pattern: '/' . PhonePatterns::CAPTURING_DIGIT_PATTERN . '/ui',
					subject: substr($number, $matchEnd),
					matches: $groups,
					flags: PREG_OFFSET_CAPTURE
				) === 1
			) {
				$positions = [];
				foreach ($groups as $group) {
					$positions[] = [
						$group[0],
						mb_strlen(mb_strcut($number, 0, $group[1])),
					];
				}
				$normalizedGroup = $this->normalizeDigits(number: $positions[1][0]);
				if ($normalizedGroup === '0') {
					return false;
				}
			}
			$number = substr($number, $matchEnd);

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

	private function maybeStripNationalPrefixAndCarrierCode(string &$number, PhoneMetaData $metadata, ?string &$carrierCode): void
	{
		$numberLength = mb_strlen($number);
		$possibleNationalPrefix = $metadata->getNationalPrefixForParsing();
		if ($numberLength == 0 || $possibleNationalPrefix === null || mb_strlen($possibleNationalPrefix) == 0) {
			// Early return for numbers of zero length.
			return;
		}

		// Attempt to parse the first digits as a national prefix.
		$prefixMatcher = new PhoneMatcher(pattern: $possibleNationalPrefix, subject: $number);
		if ($prefixMatcher->lookingAt()) {
			$generalDesc = $metadata->getGeneralDesc();
			// Check if the original number is viable.
			$isViableOriginalNumber = $this->matchNationalNumber($number, $generalDesc);
			// $prefixMatcher->group($numOfGroups) === null implies nothing was captured by the capturing
			// groups in $possibleNationalPrefix; therefore, no transformation is necessary, and we just
			// remove the national prefix
			$numOfGroups = $prefixMatcher->groupCount();
			$transformRule = $metadata->getNationalPrefixTransformRule();
			if ($transformRule === null
				|| mb_strlen($transformRule) == 0
				|| $prefixMatcher->group($numOfGroups - 1) === null
			) {
				// If the original number was viable, and the resultant number is not, we return.
				if ($isViableOriginalNumber &&
					!$this->matchNationalNumber(
						substr($number, $prefixMatcher->end()), $generalDesc)) {
					return;
				}
				if ($carrierCode !== null && $numOfGroups > 0 && $prefixMatcher->group($numOfGroups) !== null) {
					$carrierCode .= $prefixMatcher->group(1);
				}

				$number = substr($number, $prefixMatcher->end());

				return;
			}

			// Check that the resultant number is still viable. If not, return. Check this by copying
			// the string and making the transformation on the copy first.
			$transformedNumber = $number;
			$transformedNumber = substr_replace(
				$transformedNumber,
				$prefixMatcher->replaceFirst($transformRule),
				0,
				$numberLength
			);
			if ($isViableOriginalNumber
				&& !$this->matchNationalNumber($transformedNumber, $generalDesc)) {
				return;
			}
			if ($carrierCode !== null && $numOfGroups > 1) {
				$carrierCode .= $prefixMatcher->group(1);
			}
			$number = substr_replace($number, $transformedNumber, 0, mb_strlen($number));
		}
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