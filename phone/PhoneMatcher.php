<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhoneMatcher
{
	private string $pattern;
	private string $subject;
	private array $groups = [];

	public function __construct(string $pattern, string $subject)
	{
		$this->pattern = str_replace('/', '\/', $pattern);
		$this->subject = $subject;
	}

	public function lookingAt(): bool
	{
		if (preg_match(
				pattern: '/^' . $this->pattern . '/ui',
				subject: $this->subject,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) !== 1
		) {
			return false;
		}
		foreach ($groups as $group) {
			$this->groups[] = [
				$group[0],
				mb_strlen(string: mb_strcut(string: $this->subject, start: 0, length: $group[1])),
			];
		}

		return true;
	}

	public function matches(): bool
	{
		if (preg_match(
				pattern: '/^' . $this->pattern . '$/ui',
				subject: $this->subject,
				matches: $groups,
				flags: PREG_OFFSET_CAPTURE
			) !== 1) {
			return false;
		}
		foreach ($groups as $group) {
			$this->groups[] = [
				$group[0],
				mb_strlen(string: mb_strcut(string: $this->subject, start: 0, length: $group[1])),
			];
		}

		return true;
	}

	public function end(): ?int
	{
		return isset($this->groups[0]) ? ($this->groups[0][1] + mb_strlen(string: $this->groups[0][0])) : null;
	}

	public function group(int $group): ?string
	{
		return $this->groups[$group][0] ?? null;
	}

	public function groupCount(): ?int
	{
		return empty($this->groups) ? null : (count($this->groups) - 1);
	}

	public function replaceFirst(string $replacement): string
	{
		return preg_replace(
			pattern: '/' . $this->pattern . '/x',
			replacement: $replacement,
			subject: $this->subject,
			limit: 1
		);
	}

	public function replaceAll(string $replacement): string
	{
		return preg_replace(
			pattern: '/' . $this->pattern . '/x',
			replacement: $replacement,
			subject: $this->subject
		);
	}
}