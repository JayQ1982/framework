<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhoneDesc
{
	private string $nationalNumberPattern = '';
	private array $possibleLength;
	private array $possibleLengthLocalOnly;

	public function __construct(array $input)
	{
		if (array_key_exists(key: 'NationalNumberPattern', array: $input) && trim(string: $input['NationalNumberPattern']) !== '') {
			$this->nationalNumberPattern = $input['NationalNumberPattern'];
		}
		$this->possibleLength = $input['PossibleLength'];
		$this->possibleLengthLocalOnly = $input['PossibleLengthLocalOnly'];
	}

	public function getPossibleLength(): array
	{
		return $this->possibleLength;
	}

	public function getPossibleLengthLocalOnly(): array
	{
		return $this->possibleLengthLocalOnly;
	}

	public function getNationalNumberPattern(): string
	{
		return $this->nationalNumberPattern;
	}
}