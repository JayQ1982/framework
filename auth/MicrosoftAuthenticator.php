<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use DateTimeImmutable;
use framework\Core;
use framework\core\HttpResponse;
use framework\session\AbstractSessionHandler;
use LogicException;
use Throwable;

abstract class MicrosoftAuthenticator extends Authenticator
{
	private const AUTHORIZE_PATH = 'https://login.microsoftonline.com/{tenantID}/oauth2/v2.0/' . 'authorize';

	protected function redirectToMicrosoftLogin(string $tenantID, string $clientID, string $redirectUri, string $ssoNonce): void
	{
		if (AuthSession::isLoggedIn()) {
			throw new LogicException(message: 'User is already logged in');
		}
		AbstractSessionHandler::getSessionHandler()->changeCookieSameSiteToNone();
		// See https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-protocols-oidc
		HttpResponse::redirectAndExit(
			relativeOrAbsoluteUri: str_replace(search: '{tenantID}', replace: $tenantID, subject: MicrosoftAuthenticator::AUTHORIZE_PATH) . '?' . implode(
				separator: '&',
				array: [
					'client_id=' . $clientID,
					'response_type=id_token',
					'redirect_uri=' . $redirectUri,
					'response_mode=form_post',
					'scope=openid',
					'nonce=' . $ssoNonce,
				]
			)
		);
	}

	protected function microsoftIdTokenLogin(string $tenantID, string $clientID, string $ssoNonce, string $microsoftIdToken): bool
	{
		try {
			$authWebToken = new MicrosoftIdToken(
				tenantID: $tenantID,
				clientID: $clientID,
				ssoNonce: $ssoNonce,
				jwtString: $microsoftIdToken
			);
		} catch (Throwable $throwable) {
			$this->logException(throwable: $throwable, ssoNonce: $ssoNonce, inputIdTokenString: $microsoftIdToken);
			$this->setAuthResult(authResult: AuthResult::FAILED_SSO_LOGIN);

			return false;
		}

		return $this->authWebTokenLogin(authWebToken: $authWebToken);
	}

	private function logException(Throwable $throwable, string $ssoNonce, string $inputIdTokenString): void
	{
		$logFile = fopen(filename: Core::get()->logDirectory . 'ssoMicrosoft.log', mode: 'a+');
		fwrite(stream: $logFile,
			data: (implode(
					separator: PHP_EOL,
					array: [
						(new DateTimeImmutable())->format(format: 'Y-m-d H:i:s') . ' ' . $throwable->getMessage(),
						'sso-nonce: ' . $ssoNonce,
						$inputIdTokenString,
						'------------------------------------------------------------------',
					]) . PHP_EOL
			)
		);
		fclose(stream: $logFile);
	}
}