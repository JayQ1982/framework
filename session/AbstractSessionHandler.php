<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\session;

use Exception;
use framework\core\EnvironmentSettingsModel;
use framework\core\HttpRequest;
use SessionHandler;

abstract class AbstractSessionHandler extends SessionHandler
{
	private static null|FileSessionHandler|NoSqlSessionHandler $instance = null;

	private const SESSION_CREATED_INDICATOR = 'sessionCreated';
	private const TRUSTED_REMOTE_ADDRESS_INDICATOR = 'trustedRemoteAddress';
	private const TRUSTED_USER_AGENT_INDICATOR = 'trustedUserAgent';
	private const LAST_ACTIVITY_INDICATOR = 'lastActivity';
	private const PREFERRED_LANGUAGE_INDICATOR = 'preferredLanguage';

	private int $currentTime;
	private ?string $ID = null;
	private ?string $name = null;
	private ?int $maxLifeTime;
	private string $clientRemoteAddress;
	private string $clientUserAgent;
	private ?string $fingerprint = null;
	private array $availableLanguages;

	public static function getSessionHandler(EnvironmentSettingsModel $environmentSettingsModel, HttpRequest $httpRequest): FileSessionHandler|NoSqlSessionHandler
	{
		if (is_null(AbstractSessionHandler::$instance)) {
			if ($environmentSettingsModel->getSessionSettingsModel()->isUseNoSqlSessionHandler()) {
				AbstractSessionHandler::$instance = new NoSqlSessionHandler($environmentSettingsModel, $httpRequest);
			} else {
				AbstractSessionHandler::$instance = new FileSessionHandler($environmentSettingsModel, $httpRequest);
			}
		}

		return AbstractSessionHandler::$instance;
	}

	protected function __construct(EnvironmentSettingsModel $environmentSettingsModel, HttpRequest $httpRequest)
	{
		$this->currentTime = time();
		$this->maxLifeTime = $environmentSettingsModel->getSessionSettingsModel()->getMaxLifeTime();
		$this->clientRemoteAddress = $httpRequest->getRemoteAddress();
		$this->clientUserAgent = $httpRequest->getUserAgent();
		$this->availableLanguages = $environmentSettingsModel->getAvailableLanguages();

		$this->start($environmentSettingsModel->getSessionSettingsModel(), $httpRequest);
	}

	private function start(SessionSettingsModel $sessionSettingsModel, HttpRequest $httpRequest): void
	{
		$this->setDefaultConfigurationOptions($sessionSettingsModel->getMaxLifeTime());
		$this->setDefaultSecuritySettings($sessionSettingsModel->isSameSiteStrict());
		$this->setSessionName($sessionSettingsModel->getIndividualName(), $httpRequest);
		$this->executePreStartActions();
		session_set_save_handler($this, true);
		if (!session_start()) {
			throw new Exception('Could not start session');
		}

		if (!$this->isSessionCreated()) {
			$this->initDefaultSessionData(false);
		} else if ($this->getTrustedRemoteAddress() !== $this->clientRemoteAddress || $this->getTrustedUserAgent() !== $this->clientUserAgent) {
			$this->initDefaultSessionData(true);
		} else if ($this->isSessionExpired()) {
			// Real session lifetime and regeneration after maxLifeTime
			// See: http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes/1270960#1270960
			$this->initDefaultSessionData(true);
		} else if ($this->isSessionOlderThan30Minutes()) {
			$this->regenerateID();
		}

		$this->setLastAction();
	}

	private function setDefaultConfigurationOptions(?int $maxLifeTime): void
	{
		if (!is_null($maxLifeTime)) {
			@ini_set('session.gc_maxlifetime', $maxLifeTime);
		}
	}

	private function setDefaultSecuritySettings(bool $isSameSiteStrict): void
	{
		// Set security related configuration options,
		// See https://php.net/manual/en/session.configuration.php
		// -------------------------------------------
		// Send cookies only over HTTPS
		@ini_set('session.cookie_secure', true);
		// Do not allow JS to access cookie vars (helps to reduce identity theft through XSS attacks)
		@ini_set('session.cookie_httponly', true);
		// Prevent session fixation; very recommended
		@ini_set('session.use_strict_mode', true);

		// Prevent cross domain information leakage
		// See https://www.thinktecture.com/de/identity/samesite/samesite-in-a-nutshell/ for further explanations
		@ini_set('session.cookie_samesite', $isSameSiteStrict ? 'Strict' : 'Lax');
	}

	private function setSessionName(?string $individualName, HttpRequest $httpRequest): void
	{
		if (trim($individualName) !== '') {
			$sn = $individualName;

			// Overwrite session id in cookie when provided by get-Parameter
			$requestedSessionID = $httpRequest->getInputString($sn);
			if (!empty($requestedSessionID) && isset($_COOKIE[$sn]) && $_COOKIE[$sn] !== $requestedSessionID) {
				$_COOKIE[$sn] = $requestedSessionID;
				session_id($requestedSessionID);
			}

			session_name($sn);
		}

		// Just generate new session id, if current from cookie contains illegal characters
		// Inspired from http://stackoverflow.com/questions/32898857/session-start-issues-regarding-illegal-characters-empty-session-id-and-failed
		$sn = session_name();
		if (isset($_COOKIE[$sn]) && $this->checkSessionIdAgainstSidBitsPerChar($_COOKIE[$sn], ini_get('session.sid_bits_per_character')) === false) {
			unset($_COOKIE[$sn]);
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
		if ($sidBitsPerChar == 4 && preg_match('/^[a-f0-9]+$/', $sessionId) === 0) {
			return false;
		}

		if ($sidBitsPerChar == 5 && preg_match('/^[a-v0-9]+$/', $sessionId) === 0) {
			return false;
		}

		if ($sidBitsPerChar == 6 && preg_match('/^[A-Za-z0-9-,]+$/i', $sessionId) === 0) {
			return false;
		}

		return true;
	}

	abstract protected function executePreStartActions(): void;

	public function getID(): string
	{
		if (is_null($this->ID)) {
			$this->ID = session_id();
		}

		return $this->ID;
	}

	public function getName(): string
	{
		if (is_null($this->name)) {
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
		if (is_null($this->fingerprint)) {
			$this->fingerprint = hash('sha256', $this->getID() . $this->clientUserAgent, false);
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
		return array_key_exists(AbstractSessionHandler::SESSION_CREATED_INDICATOR, $_SESSION);
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
			!is_null($this->maxLifeTime)
			&& array_key_exists(AbstractSessionHandler::LAST_ACTIVITY_INDICATOR, $_SESSION)
			&& ($this->currentTime - $_SESSION[AbstractSessionHandler::LAST_ACTIVITY_INDICATOR] > $this->maxLifeTime)
		);
	}

	private function setLastAction(): void
	{
		$_SESSION[AbstractSessionHandler::LAST_ACTIVITY_INDICATOR] = $this->currentTime;
	}

	public function setPreferredLanguage(string $language): void
	{
		if (!array_key_exists($language, $this->availableLanguages)) {
			throw new Exception('The preferred language ' . $language . ' is not available');
		}

		$_SESSION[AbstractSessionHandler::PREFERRED_LANGUAGE_INDICATOR] = $language;
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