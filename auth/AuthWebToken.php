<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\exception\UnauthorizedException;
use stdClass;

abstract class AuthWebToken
{
	public readonly array $jwtArray;
	public readonly string $base64Header;
	public readonly string $base64Payload;
	public readonly string $base64Secret;
	public readonly stdClass $header;
	public readonly stdClass $payload;
	public readonly string $secret;

	public function __construct(public readonly string $jwtString)
	{
		$this->jwtArray = explode(separator: '.', string: $jwtString);
		if (!array_key_exists(key: 2, array: $this->jwtArray)) {
			throw new UnauthorizedException(message: 'The jwtString does not contain three segments.');
		}
		$this->base64Header = $this->jwtArray[0];
		$this->base64Payload = $this->jwtArray[1];
		$this->base64Secret = $this->jwtArray[2];
		$this->header = AuthWebToken::jsonDecode(input: AuthWebToken::urlSafeBase64Decode(base64EncodedString: $this->base64Header));
		$this->payload = AuthWebToken::jsonDecode(input: AuthWebToken::urlSafeBase64Decode(base64EncodedString: $this->base64Payload));
		$this->secret = AuthWebToken::urlSafeBase64Decode(base64EncodedString: $this->base64Secret);
		if (!$this->verify()) {
			throw new UnauthorizedException(message: 'JWT verification failed');
		}
	}

	abstract protected function verify(): bool;

	abstract public function getUserName(): string;

	private static function urlSafeBase64Decode(string $base64EncodedString): string
	{
		$remainder = strlen(string: $base64EncodedString) % 4;
		if ($remainder) {
			$base64EncodedString .= str_repeat(string: '=', times: 4 - $remainder);
		}
		$decodedString = base64_decode(string: strtr(string: $base64EncodedString, from: '-_', to: '+/'));
		if ($decodedString === false) {
			throw new UnauthorizedException(message: 'Failed to decode string: ' . $base64EncodedString);
		}

		return $decodedString;
	}

	protected static function jsonDecode(string $input): stdClass
	{
		return json_decode(json: $input, associative: false, flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
	}
}