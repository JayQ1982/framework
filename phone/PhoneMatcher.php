<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

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

	public function find(): bool
	{
		if (preg_match(
				pattern: '/' . $this->pattern . '/ui',
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

	public function start(): ?int
	{
		return isset($this->groups[0]) ? $this->groups[0][1] : null;
	}

	public function end(): ?int
	{
		return isset($this->groups[0]) ? ($this->groups[0][1] + mb_strlen(string: $this->groups[0][0])) : null;
	}

	public function group(int $group): ?string
	{
		return (
			array_key_exists(key: $group, array: $this->groups)
			&& array_key_exists(key: 0, array: $this->groups[$group])
		) ? $this->groups[$group][0] : null;
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