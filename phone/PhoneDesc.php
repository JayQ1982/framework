<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

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