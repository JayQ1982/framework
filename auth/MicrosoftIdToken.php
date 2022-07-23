<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\Core;
use framework\core\HttpResponse;
use framework\exception\UnauthorizedException;
use framework\security\CspNonce;
use OpenSSLAsymmetricKey;
use stdClass;

class MicrosoftIdToken extends AuthWebToken
{
	private const SSO_STATE_IDENTIFIER = 'ssoState';
	private const AUTHORIZE_PATH = 'https://login.microsoftonline.com/{tenantID}/oauth2/v2.0/' . 'authorize';
	private const PUBLIC_KEYS_PATH = 'https://login.microsoftonline.com/{tenantID}/discovery/keys';

	public static function redirect(string $tenantID, string $clientID, string $redirectUri): void
	{
		$_SESSION[MicrosoftIdToken::SSO_STATE_IDENTIFIER] = uniqid();
		// See https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-protocols-oidc
		HttpResponse::redirectAndExit(
			relativeOrAbsoluteUri: str_replace(search: '{tenantID}', replace: $tenantID, subject: MicrosoftIdToken::AUTHORIZE_PATH) . '?' . implode(
				separator: '&',
				array: [
					'client_id=' . $clientID,
					'response_type=id_token',
					'redirect_uri=' . $redirectUri,
					'response_mode=form_post',
					'scope=openid',
					'state=' . $_SESSION['ssoState'],
					'nonce=' . CspNonce::get(),
				]
			)
		);
	}

	public function __construct(
		private readonly string $tenantID,
		private readonly string $clientID,
		private readonly string $ssoNonce,
		string                  $jwtString
	) {
		parent::__construct(jwtString: $jwtString);
	}

	public function getUserName(): string
	{
		return $this->payload->email;
	}

	protected function verify(): bool
	{
		$header = $this->header;
		if (!property_exists(object_or_class: $header, property: 'alg') || $header->alg !== 'RS256') {
			throw new UnauthorizedException(message: 'Missing or invalid alg');
		}
		if (!property_exists(object_or_class: $header, property: 'kid')) {
			throw new UnauthorizedException(message: 'Missing kid');
		}
		$cachePath = Core::get()->cacheDirectory . 'ssoMicrosoftKeys.json';
		$publicKeysPath = str_replace(search: '{tenantID}', replace: $this->tenantID, subject: MicrosoftIdToken::PUBLIC_KEYS_PATH);
		if (!file_exists(filename: $cachePath)) {
			copy(from: $publicKeysPath, to: $cachePath);
		}
		$publicKeySet = MicrosoftIdToken::parseKeySet(sourceFilePath: $cachePath);
		if (!array_key_exists(key: $header->kid, array: $publicKeySet)) {
			copy(from: $publicKeysPath, to: $cachePath);
		}
		$publicKeySet = MicrosoftIdToken::parseKeySet(sourceFilePath: $cachePath);
		$publicKey = $publicKeySet[$header->kid];
		if (openssl_verify(
				data: "$this->base64Header.$this->base64Payload",
				signature: $this->secret,
				public_key: $publicKey,
				algorithm: OPENSSL_ALGO_SHA256
			) !== 1) {
			throw new UnauthorizedException(message: 'Signature verification failed');
		}
		$timestamp = time();
		$leeway = 60;
		$payload = $this->payload;
		if (!property_exists(object_or_class: $payload, property: 'nbf') || $payload->nbf > ($timestamp + $leeway)) {
			throw new UnauthorizedException(message: 'Missing or outdated nbf');
		}
		if (!property_exists(object_or_class: $payload, property: 'iat') || $payload->iat > ($timestamp + $leeway)) {
			throw new UnauthorizedException(message: 'Missing or outdated iat');
		}
		if (!property_exists(object_or_class: $payload, property: 'exp') || ($timestamp - $leeway) >= $payload->exp) {
			throw new UnauthorizedException(message: 'Missing or expired exp');
		}
		if (!property_exists(object_or_class: $payload, property: 'aud') || $payload->aud !== $this->clientID) {
			throw new UnauthorizedException(message: 'Missing or invalid aud');
		}
		if (!property_exists(object_or_class: $payload, property: 'tid') || $payload->tid !== $this->tenantID) {
			throw new UnauthorizedException(message: 'Missing or invalid tid');
		}
		if (!property_exists(object_or_class: $payload, property: 'nonce') || $payload->nonce !== $this->ssoNonce) {
			throw new UnauthorizedException(message: 'Invalid ssoNonce');
		}

		return true;
	}

	protected static function parseKeySet(string $sourceFilePath): array
	{
		$json = AuthWebToken::jsonDecode(input: file_get_contents(filename: $sourceFilePath));
		$keys = [];
		foreach ($json->keys as $key) {
			$keys[$key->kid] = MicrosoftIdToken::parseKey(source: $key);
		}
		if (count(value: $keys) === 0) {
			throw new UnauthorizedException(message: 'Failed to parse key file:' . $sourceFilePath);
		}

		return $keys;
	}

	private static function parseKey(stdClass $source): OpenSSLAsymmetricKey
	{
		if (property_exists(object_or_class: $source, property: 'd')) {
			throw new UnauthorizedException(message: 'Failed to parse JWK: RSA private key is not supported');
		}
		$certificates = [];
		foreach ($source->x5c as $x5c) {
			$certificates[] = openssl_x509_read(
				certificate: implode(separator: PHP_EOL, array: [
					'-----BEGIN CERTIFICATE-----',
					chunk_split(string: $x5c, length: 64, separator: PHP_EOL) . '-----END CERTIFICATE-----',
					'',
				])
			);
		}
		// Verify validity of certificate chain (each one is signed by the next, except root cert)
		for ($i = 0; $i < count(value: $certificates); $i++) {
			if ($i === count(value: $certificates) - 1) {
				if (openssl_x509_verify(certificate: $certificates[$i], public_key: $certificates[$i]) !== 1) {
					throw new UnauthorizedException(message: 'Invalid Root Certificate');
				}
			} else {
				if (openssl_x509_verify(certificate: $certificates[$i], public_key: $certificates[$i + 1]) !== 1) {
					throw new UnauthorizedException(message: 'Invalid Certificate');
				}
			}
		}

		return openssl_pkey_get_public(public_key: $certificates[0]);
	}
}