<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use Throwable;

class ValidatedEmailAddress
{
	private string $value;
	private string $domain;
	private bool $isValidSyntax;
	private ?bool $isResolvable = null;
	private ?string $lastErrorCode = null;
	private ?string $lastErrorMessage = null;

	public function __construct(string $emailAddress)
	{
		$this->value = $this->silentlyReplaceInvalidWhitespaces(emailAddress: $emailAddress);
		$this->isValidSyntax = $this->validateSyntax();
	}

	/**
	 * An Email address has *never* spaces/tabs/newlines in it (they might get into that string by c&p error done by users)
	 */
	private function silentlyReplaceInvalidWhitespaces(string $emailAddress): string
	{
		return trim(string: str_replace(
			search: [
				' ',
				"\t",
				"\n",
				"\r",
				"&#8203;", "\xE2\x80\x8C", "\xE2\x80\x8B", // https://stackoverflow.com/questions/22600235/remove-unicode-zero-width-space-php
			],
			replace: '',
			subject: $emailAddress
		));
	}

	/**
	 * We purposely do NOT allow commas/semicolons (preventing "multiple" email address entered, where NOT expected)
	 * ':' Will catch "mailto:" copy&paste errors from users, which also result in an invalid email address
	 *
	 * @return bool
	 */
	private function validateSyntax(): bool
	{
		if ($this->value === '') {
			$this->lastErrorCode = 'emptyValue';
			$this->lastErrorMessage = 'Empty email address value';

			return false;
		}

		$forbiddenCharacters = [
			'"',
			'\'',
			',',
			';',
			':',
			' ',
			"\t",
			"\n",
			"\r",
			"&#8203;", "\xE2\x80\x8C", "\xE2\x80\x8B", // https://stackoverflow.com/questions/22600235/remove-unicode-zero-width-space-php
		];
		if ($this->value !== str_replace(search: $forbiddenCharacters, replace: '', subject: $this->value)) {
			$this->lastErrorCode = 'invalidCharacters';
			$this->lastErrorMessage = 'The email address contains invalid characters.';

			return false;
		}

		$emailParts = explode(separator: '@', string: $this->value);
		if (count(value: $emailParts) !== 2) {
			$this->lastErrorCode = 'atCharacterError';
			$this->lastErrorMessage = 'The email address contains not exactly one at-character (@).';

			return false;
		}
		$domain = StringUtils::utf8_to_punycode(string: $emailParts[1]);

		if ($domain === false) {
			$this->lastErrorCode = 'invalidDomainName';
			$this->lastErrorMessage = 'The email address contains an invalid domain part.';

			return false;
		}

		// We do NOT verify the name part, as this is too complex.
		// We use the default php validation because same validation happens when we use the value in PHPMailer.
		if (filter_var(value: $this->value, filter: FILTER_VALIDATE_EMAIL) === false) {
			$this->lastErrorCode = 'invalidSyntax';
			$this->lastErrorMessage = 'The FILTER_VALIDATE_EMAIL filter returned false due to an invalid syntax.';

			return false;
		}

		$this->domain = $domain;

		return true;
	}

	public function isValidSyntax(): bool
	{
		return $this->isValidSyntax;
	}

	public function isResolvable(bool $returnTrueOnDnsGetRecordFailure): bool
	{
		if (!$this->isValidSyntax()) {
			return false;
		}

		if (is_null($this->isResolvable)) {
			$this->isResolvable = $this->resolve();
		}

		if ($this->isResolvable) {
			return true;
		}

		return ($returnTrueOnDnsGetRecordFailure && $this->lastErrorCode === 'dns_get_record');
	}

	private function resolve(): bool
	{
		$mxRecords = [];
		if (getmxrr($this->domain, $mxRecords)) {
			// Currently, we ignore the note from https://www.php.net/manual/en/function.getmxrr:
			// This function should not be used for the purposes of address verification. Only the mailexchangers found in DNS are returned, however, according
			// to » RFC 2821 when no mail exchangers are listed, hostname itself should be used as the only mail exchanger with a priority of 0.
			// TODO: Check a better solution for the future (e.g. dns_get_record with type "MX")? Requires further checking for possible differences.
			return true;
		}

		// Port 25 fallback check if there's no MX record (or an error occu
		try {
			$aRecords = dns_get_record($this->domain, DNS_A);
		} catch (Throwable $throwable) {
			$this->lastErrorCode = 'dns_get_record';
			$this->lastErrorMessage = $throwable->getMessage();

			return false;
		}

		if (count($aRecords) === 0) {
			$this->lastErrorCode = 'noDnsRecords';
			$this->lastErrorMessage = 'No A-Records found for the domain';

			return false;
		}

		try {
			$connection = fsockopen($aRecords[0]['ip'], 25, $errorCode, $errorMessage, 5);
		} catch (Throwable $throwable) {
			$this->lastErrorCode = 'fsockopen';
			$this->lastErrorMessage = $throwable->getMessage();

			return false;
		}

		if (!is_resource($connection)) {
			$this->lastErrorCode = 'notResolvable';
			$this->lastErrorMessage = 'Failed to connect to port 25';

			return false;
		}
		fclose($connection);

		return true;
	}

	public function getLastErrorCode(): string
	{
		return $this->lastErrorCode;
	}

	public function getLastErrorMessage(): string
	{
		return $this->lastErrorMessage;
	}
}