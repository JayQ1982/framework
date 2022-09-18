<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\common\StringUtils;

class Password
{
	public const HASH_ALGORITHM = 'sha256';

	public function __construct(
		public readonly string $salt,
		public readonly string $hash
	) {
	}

	public function isValid(string $rawPassword): bool
	{
		return (Password::createWithSalt(salt: $this->salt, rawPassword: $rawPassword)->hash === $this->hash);
	}

	public static function createWithSalt(string $salt, string $rawPassword): Password
	{
		return new Password(salt: $salt, hash: hash(algo: Password::HASH_ALGORITHM, data: $salt . $rawPassword));
	}

	public static function generateNew(string $rawPassword): Password
	{
		return Password::createWithSalt(salt: StringUtils::generateSalt(), rawPassword: $rawPassword);
	}
}