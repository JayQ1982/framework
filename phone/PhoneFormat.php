<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

class PhoneFormat
{
	private ?string $pattern;
	private ?string $format;
	private array $leadingDigitsPattern = [];

	public function __construct(array $input)
	{
		$this->pattern = $input['pattern'];
		$this->format = $input['format'];
		foreach ($input['leadingDigitsPatterns'] as $leadingDigitsPattern) {
			$this->leadingDigitsPattern[] = $leadingDigitsPattern;
		}
	}

	public function getPattern(): ?string
	{
		return $this->pattern;
	}

	public function getFormat(): ?string
	{
		return $this->format;
	}

	public function leadingDigitsPatternSize(): int
	{
		return count($this->leadingDigitsPattern);
	}

	public function getLeadingDigitsPattern(int $index): string
	{
		return $this->leadingDigitsPattern[$index];
	}
}