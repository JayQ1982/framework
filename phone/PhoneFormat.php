<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
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