<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

class PhoneMetaData
{
	private string $internationalPrefix;
	private int $countryCode;
	private ?PhoneDesc $generalDesc = null;
	private ?string $nationalPrefixForParsing = null;
	private ?string $nationalPrefixTransformRule = null;
	/** @var PhoneFormat[] */
	private array $intlNumberFormat = [];
	/** @var PhoneFormat[] */
	private array $numberFormat = [];
	private ?string $preferredExtnPrefix = null;

	/** @var PhoneMetaData[] */
	private static array $regionToMetaDataMap = [];
	/** @var PhoneMetaData[] */
	private static array $countryCodeToNonGeographicalMetadataMap = [];

	public static function getForRegion(?string $regionCode): ?PhoneMetaData
	{
		if (is_null(value: $regionCode) || !PhoneValidator::isValidRegionCode(regionCode: $regionCode)) {
			return null;
		}

		if (!array_key_exists(key: $regionCode, array: PhoneMetaData::$regionToMetaDataMap)) {
			PhoneMetaData::$regionToMetaDataMap[$regionCode] = new PhoneMetaData(fileName: __DIR__ . '/data/PhoneNumberMetadata_' . $regionCode . '.php');
		}

		return PhoneMetaData::$regionToMetaDataMap[$regionCode];
	}

	public static function getForRegionOrCallingCode(int $countryCallingCode, string $regionCode): ?PhoneMetaData
	{
		if ($regionCode === '001') {
			if (!PhoneRegionCountryCodeMap::countryCodeExists(countryCodeToCheck: $countryCallingCode)) {
				return null;
			}

			if (!array_key_exists(key: $countryCallingCode, array: PhoneMetaData::$countryCodeToNonGeographicalMetadataMap)) {
				PhoneMetaData::$countryCodeToNonGeographicalMetadataMap[$countryCallingCode] = new PhoneMetaData(fileName: __DIR__ . '/data/PhoneNumberMetadata_' . $countryCallingCode . '.php');
			}

			return PhoneMetaData::$countryCodeToNonGeographicalMetadataMap[$countryCallingCode];
		}

		return PhoneMetaData::getForRegion(regionCode: $regionCode);
	}

	private function __construct(string $fileName)
	{
		$data = include $fileName;
		$this->internationalPrefix = (string)$data['internationalPrefix'];
		$this->countryCode = (int)$data['countryCode'];
		if (array_key_exists(key: 'generalDesc', array: $data)) {
			$this->generalDesc = new PhoneDesc(input: $data['generalDesc']);
		}
		if (array_key_exists(key: 'nationalPrefixForParsing', array: $data)) {
			$this->nationalPrefixForParsing = $data['nationalPrefixForParsing'];
		}
		if (array_key_exists(key: 'nationalPrefixTransformRule', array: $data)) {
			$this->nationalPrefixTransformRule = $data['nationalPrefixTransformRule'];
		}
		foreach ($data['intlNumberFormat'] as $intlNumberFormatElt) {
			$this->addIntlNumberFormat(value: new PhoneFormat(input: $intlNumberFormatElt));
		}

		foreach ($data['numberFormat'] as $numberFormatElt) {
			$this->addNumberFormat(value: new PhoneFormat(input: $numberFormatElt));
		}
		if (array_key_exists(key: 'preferredExtnPrefix', array: $data)) {
			$this->preferredExtnPrefix = $data['preferredExtnPrefix'];
		}
	}

	public function getInternationalPrefix(): string
	{
		return $this->internationalPrefix;
	}

	public function getCountryCode(): int
	{
		return $this->countryCode;
	}

	public function getGeneralDesc(): ?PhoneDesc
	{
		return $this->generalDesc;
	}

	public function getNationalPrefixForParsing(): ?string
	{
		return $this->nationalPrefixForParsing;
	}

	public function getNationalPrefixTransformRule(): ?string
	{
		return $this->nationalPrefixTransformRule;
	}

	public function intlNumberFormats(): array
	{
		return $this->intlNumberFormat;
	}

	public function addIntlNumberFormat(PhoneFormat $value): void
	{
		$this->intlNumberFormat[] = $value;
	}

	public function numberFormats(): array
	{
		return $this->numberFormat;
	}

	public function addNumberFormat(PhoneFormat $value): void
	{
		$this->numberFormat[] = $value;
	}

	public function hasPreferredExtnPrefix(): bool
	{
		return !is_null($this->preferredExtnPrefix);
	}

	public function getPreferredExtnPrefix(): string
	{
		return $this->preferredExtnPrefix;
	}
}