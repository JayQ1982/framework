<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;

class SessionHandler
{
	private int $currentTime;
	private bool $started = false;
	private ?string $ID = null;
	private ?string $savePath;
	private ?string $name;
	private ?int $maxLifeTime;
	private string $remoteAddress;
	private string $userAgent;
	private ?string $fingerprint = null;

	public function __construct(EnvironmentHandler $environmentHandler, HttpRequest $httpRequest)
	{
		$this->currentTime = time();
		$this->savePath = $environmentHandler->getSessionSavePath();
		$this->name = $environmentHandler->getSessionName();
		$this->maxLifeTime = $environmentHandler->getSessionMaxLifeTime();
		$this->remoteAddress = $httpRequest->getRemoteAddress();
		$this->userAgent = $httpRequest->getUserAgent();
	}

	private function setSecuritySettings(): void
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
		@ini_set('session.cookie_samesite', 'Strict');
	}

	public function start(HttpRequest $httpRequest): void
	{
		if ($this->started) {
			return;
		}

		if (!is_null($this->maxLifeTime)) {
			@ini_set('session.gc_maxlifetime', $this->maxLifeTime);
		}

		$this->setSecuritySettings();

		// set session_save_path
		if (!is_null($this->savePath) && trim($this->savePath) !== '') {
			session_save_path($this->savePath);
		}

		if (!is_null($this->name) && $this->name !== '') {
			$sn = $this->name;

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

		if (@session_start() === false) {
			throw new Exception('Could not start session');
		}

		if (!isset($_SESSION['SESSION_CREATED'])) {
			// If default session data has not been initialized yet, we just do it now
			$this->initDefaultSessionData(false);
		} else if ($_SESSION['TRUSTED_REMOTE_ADDR'] != $this->remoteAddress || $_SESSION['PREV_USERAGENT'] != $this->userAgent) {
			// If trusted info does not match, we destroy and reinitialize it
			$this->initDefaultSessionData(true);
		} else if (!is_null($this->maxLifeTime) && isset($_SESSION['LAST_ACTIVITY']) && ($this->currentTime - $_SESSION['LAST_ACTIVITY'] > $this->maxLifeTime)) {
			// Real session lifetime and regeneration after maxLifeTime
			// See: http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes/1270960#1270960
			$this->initDefaultSessionData(true);
		} else if ($this->currentTime - $_SESSION['SESSION_CREATED'] > 1800) {
			// Session started more than 30 minutes ago, so we change session ID for the current session an invalidate old session ID
			$this->regenerateID();
		}

		// Update last access to prevent garbage collection from cleaning up this session
		$_SESSION['LAST_ACTIVITY'] = $this->currentTime;

		$this->started = true;
	}

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
		$_SESSION['SESSION_CREATED'] = $this->currentTime;
	}

	public function close(): void
	{
		if ($this->started) {
			session_write_close();
		}
	}

	private function initDefaultSessionData(bool $destroyOldSession): void
	{
		if ($destroyOldSession) {
			session_destroy(); // Destroy session data in storage
			session_start(); // Cleans current global data ($_SESSION), see http://php.net/manual/de/function.session-destroy.php
		}

		$_SESSION['SESSION_CREATED'] = $this->currentTime;
		$_SESSION['TRUSTED_REMOTE_ADDR'] = $this->remoteAddress;
		$_SESSION['PREV_USERAGENT'] = $this->userAgent;
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

	public function getFingerprint(): string
	{
		if (is_null($this->fingerprint)) {
			$this->fingerprint = hash('sha256', $this->getID() . $this->userAgent, false);
		}

		return $this->fingerprint;
	}

	public function get(string $propertyName)
	{
		return $_SESSION[$propertyName] ?? null;
	}
}
/* EOF */ 