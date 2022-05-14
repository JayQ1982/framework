<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\session;

use Exception;
use framework\core\EnvironmentSettingsModel;
use framework\core\HttpRequest;
use LogicException;
use SessionHandler;

abstract class AbstractSessionHandler extends SessionHandler
{
	private static AbstractSessionHandler $registeredInstance;

	private const SESSION_CREATED_INDICATOR = 'sessionCreated';
	private const TRUSTED_REMOTE_ADDRESS_INDICATOR = 'trustedRemoteAddress';
	private const TRUSTED_USER_AGENT_INDICATOR = 'trustedUserAgent';
	private const LAST_ACTIVITY_INDICATOR = 'lastActivity';
	private const PREFERRED_LANGUAGE_INDICATOR = 'preferredLanguage';

	private int $currentTime;
	private ?string $ID = null;
	private ?string $name = null;
	private string $clientRemoteAddress;
	private string $clientUserAgent;
	private ?string $fingerprint = null;

	public static function register(?AbstractSessionHandler $individualSessionHandler): void
	{
		if (isset(AbstractSessionHandler::$registeredInstance)) {
			throw new LogicException(message: 'SessionHandler handler is already registered.');
		}
		AbstractSessionHandler::$registeredInstance = is_null(value: $individualSessionHandler) ? new FileSessionHandler(sessionSettingsModel: new SessionSettingsModel()) : $individualSessionHandler;
	}

	public static function getSessionHandler(): AbstractSessionHandler
	{
		return AbstractSessionHandler::$registeredInstance;
	}

	protected function __construct(private readonly SessionSettingsModel $sessionSettingsModel)
	{
		$this->currentTime = time();
		$this->clientRemoteAddress = HttpRequest::getRemoteAddress();
		$this->clientUserAgent = HttpRequest::getUserAgent();

		$this->start();
	}

	private function start(): void
	{
		$sessionSettingsModel = $this->sessionSettingsModel;
		$this->setDefaultConfigurationOptions(maxLifeTime: $sessionSettingsModel->maxLifeTime);
		$this->setDefaultSecuritySettings(isSameSiteStrict: $sessionSettingsModel->isSameSiteStrict);
		$this->setSessionName(individualName: $sessionSettingsModel->individualName);
		$this->executePreStartActions();
		session_set_save_handler($this, true); // TODO: Named parameters not working in PHP 8.1
		if (!session_start()) {
			throw new Exception(message: 'Could not start session');
		}

		if (!$this->isSessionCreated()) {
			$this->initDefaultSessionData(destroyOldSession: false);
		} else if ($this->getTrustedRemoteAddress() !== $this->clientRemoteAddress || $this->getTrustedUserAgent() !== $this->clientUserAgent) {
			$this->initDefaultSessionData(destroyOldSession: true);
		} else if ($this->isSessionExpired()) {
			// Real session lifetime and regeneration after maxLifeTime
			// See: http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes/1270960#1270960
			$this->initDefaultSessionData(destroyOldSession: true);
		} else if ($this->isSessionOlderThan30Minutes()) {
			$this->regenerateID();
		}

		$this->setLastAction();
	}

	private function setDefaultConfigurationOptions(?int $maxLifeTime): void
	{
		if (!is_null(value: $maxLifeTime)) {
			@ini_set(option: 'session.gc_maxlifetime', value: $maxLifeTime);
		}
	}

	private function setDefaultSecuritySettings(bool $isSameSiteStrict): void
	{
		// Set security related configuration options,
		// See https://php.net/manual/en/session.configuration.php
		// -------------------------------------------
		// Send cookies only over HTTPS
		ini_set(option: 'session.cookie_secure', value: true);
		// Do not allow JS to access cookie vars (helps to reduce identity theft through XSS attacks)
		ini_set(option: 'session.cookie_httponly', value: true);
		// Prevent session fixation; very recommended
		ini_set(option: 'session.use_strict_mode', value: true);

		// Prevent cross domain information leakage
		// See https://www.thinktecture.com/de/identity/samesite/samesite-in-a-nutshell/ for further explanations
		ini_set(option: 'session.cookie_samesite', value: $isSameSiteStrict ? 'Strict' : 'Lax');
	}

	private function setSessionName(string $individualName): void
	{
		if ($individualName !== '') {
			$sessionName = $individualName;

			// Overwrite session id in cookie when provided by get-Parameter
			$requestedSessionID = HttpRequest::getInputString(keyName: $sessionName);
			if (!empty($requestedSessionID) && isset($_COOKIE[$sessionName]) && $_COOKIE[$sessionName] !== $requestedSessionID) {
				$_COOKIE[$sessionName] = $requestedSessionID;
				session_id(id: $requestedSessionID);
			}

			session_name(name: $sessionName);
		}

		// Just generate new session id, if current from cookie contains illegal characters
		// Inspired from http://stackoverflow.com/questions/32898857/session-start-issues-regarding-illegal-characters-empty-session-id-and-failed
		$sessionName = session_name();
		if (isset($_COOKIE[$sessionName]) && $this->checkSessionIdAgainstSidBitsPerChar($_COOKIE[$sessionName], ini_get(option: 'session.sid_bits_per_character')) === false) {
			unset($_COOKIE[$sessionName]);
		}
	}

	/**
	 * Checks session id against valid characters based on the session.sid_bits_per_character ini setting
	 * (http://php.net/manual/en/session.configuration.php#ini.session.sid-bits-per-character)
	 *
	 * @param string $sessionId      The session id to check (for example cookie or get value)
	 * @param int    $sidBitsPerChar The session.sid_bits_per_character value (4, 5 or 6)
	 *
	 * @return bool Returns true if session_id is valid or false if not
	 */
	protected function checkSessionIdAgainstSidBitsPerChar(string $sessionId, int $sidBitsPerChar): bool
	{
		if ($sidBitsPerChar == 4 && preg_match(pattern: '/^[a-f\d]+$/', subject: $sessionId) === 0) {
			return false;
		}

		if ($sidBitsPerChar == 5 && preg_match(pattern: '/^[a-v\d]+$/', subject: $sessionId) === 0) {
			return false;
		}

		if ($sidBitsPerChar == 6 && preg_match(pattern: '/^[A-Za-z\d-,]+$/i', subject: $sessionId) === 0) {
			return false;
		}

		return true;
	}

	abstract protected function executePreStartActions(): void;

	public function getID(): string
	{
		if (is_null(value: $this->ID)) {
			$this->ID = session_id();
		}

		return $this->ID;
	}

	public function getName(): string
	{
		if (is_null(value: $this->name)) {
			$this->name = session_name();
		}

		return $this->name;
	}

	public function regenerateID(): void
	{
		@session_regenerate_id(true);
		$this->ID = session_id();
		$this->setSessionCreated();
	}

	private function initDefaultSessionData(bool $destroyOldSession): void
	{
		if ($destroyOldSession) {
			session_destroy(); // Destroy session data in storage
			session_start(); // Cleans current global data ($_SESSION), see http://php.net/manual/de/function.session-destroy.php
		}
		$this->setSessionCreated();
		$this->setTrustedRemoteAddress();
		$this->setTrustedUserAgent();
	}

	public function getFingerprint(): string
	{
		if (is_null(value: $this->fingerprint)) {
			$this->fingerprint = hash(
				algo: 'sha256',
				data: $this->getID() . $this->clientUserAgent,
				binary: false
			);
		}

		return $this->fingerprint;
	}

	public function get(string $propertyName)
	{
		return $_SESSION[$propertyName] ?? null;
	}

	private function setSessionCreated(): void
	{
		$_SESSION[AbstractSessionHandler::SESSION_CREATED_INDICATOR] = $this->currentTime;
	}

	public function getSessionCreated(): int
	{
		return $_SESSION[AbstractSessionHandler::SESSION_CREATED_INDICATOR];
	}

	private function isSessionCreated(): bool
	{
		return array_key_exists(key: AbstractSessionHandler::SESSION_CREATED_INDICATOR, array: $_SESSION);
	}

	private function isSessionOlderThan30Minutes(): bool
	{
		return ($this->currentTime - $this->getSessionCreated() > 1800);
	}

	private function setTrustedRemoteAddress(): void
	{
		$_SESSION[AbstractSessionHandler::TRUSTED_REMOTE_ADDRESS_INDICATOR] = $this->clientRemoteAddress;
	}

	public function getTrustedRemoteAddress(): string
	{
		return $_SESSION[AbstractSessionHandler::TRUSTED_REMOTE_ADDRESS_INDICATOR];
	}

	public function setTrustedUserAgent(): void
	{
		$_SESSION[AbstractSessionHandler::TRUSTED_USER_AGENT_INDICATOR] = $this->clientUserAgent;
	}

	public function getTrustedUserAgent(): string
	{
		return $_SESSION[AbstractSessionHandler::TRUSTED_USER_AGENT_INDICATOR];
	}

	private function isSessionExpired(): bool
	{
		return (
			!is_null(value: $this->sessionSettingsModel->maxLifeTime)
			&& array_key_exists(key: AbstractSessionHandler::LAST_ACTIVITY_INDICATOR, array: $_SESSION)
			&& ($this->currentTime - $_SESSION[AbstractSessionHandler::LAST_ACTIVITY_INDICATOR] > $this->sessionSettingsModel->maxLifeTime)
		);
	}

	private function setLastAction(): void
	{
		$_SESSION[AbstractSessionHandler::LAST_ACTIVITY_INDICATOR] = $this->currentTime;
	}

	public function setPreferredLanguage(string $languageCode): void
	{
		if (!EnvironmentSettingsModel::get()->availableLanguages->hasLanguage(languageCode: $languageCode)) {
			throw new Exception(message: 'The preferred language ' . $languageCode . ' is not available');
		}

		$_SESSION[AbstractSessionHandler::PREFERRED_LANGUAGE_INDICATOR] = $languageCode;
	}

	public function getPreferredLanguage(): ?string
	{
		return $_SESSION[AbstractSessionHandler::PREFERRED_LANGUAGE_INDICATOR] ?? null;
	}

	public function changeCookieSameSiteToLax(): void
	{
		if ((session_status() === PHP_SESSION_ACTIVE)) {
			// Prevent from "Session cookie parameters cannot be changed when a session is active" exception
			session_write_close();
		}
		session_set_cookie_params(['samesite' => 'Lax']);
		session_start();
	}
}