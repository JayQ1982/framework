<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use LogicException;

class RedirectRoute
{
	private Core $core;
	private array $oneToOneRedirects = [];
	private array $regexRedirects = [];
	private string $requestedURI = '';
	private int $maxIterations = 3;

	public function __construct(Core $core)
	{
		$this->core = $core;
		$settingsHandler = $core->getSettingsHandler();
		if (!$settingsHandler->exists('redirects')) {
			return;
		}
		$allRedirects = $settingsHandler->get('redirects');
		if (isset($allRedirects->oneToOne)) {
			foreach (get_object_vars($allRedirects->oneToOne) as $key => $val) {
				$this->oneToOneRedirects[mb_strtolower($key)] = $val;
			}
		}
		if (isset($allRedirects->regex)) {
			$this->regexRedirects = (array)$allRedirects->regex;
		}
		$this->requestedURI = mb_strtolower($core->getHttpRequest()->getPath());
	}

	public function redirectIfRouteExists(): void
	{
		if (empty($this->oneToOneRedirects) && empty($this->regexRedirects)) {
			return;
		}

		$redirectTarget = $this->findTargetForRequestedURI();
		if (is_null($redirectTarget)) {

			return;
		}

		$this->core->redirect($redirectTarget, HttpStatusCodes::HTTP_MOVED_PERMANENTLY); // Does an EXIT
	}

	private function findTargetForRequestedURI(): ?string
	{
		// First check the oneToOne-redirects, as they are more specific than a regex-redirect
		$redirectTarget = $this->checkOneToOneRedirects($this->requestedURI);
		if (!is_null($redirectTarget)) {
			return $redirectTarget;
		}

		// If this point is reached, only regex-redirects are remaining; They might be greedy; first hit, first take!
		return $this->checkRegexRedirects($this->requestedURI);
	}

	private function checkOneToOneRedirects(string $path, int $iteration = 1): ?string
	{
		if (!isset($this->oneToOneRedirects[$path])) {
			return ($iteration === 1) ? null : $path;
		}

		$iteration++;

		if ($iteration > $this->maxIterations) {
			throw new LogicException('redirects.json contains deep nested or looped redirect for "' . $this->requestedURI);
		}

		return $this->checkOneToOneRedirects($this->oneToOneRedirects[$path], $iteration);
	}

	private function checkRegexRedirects(string $path): ?string
	{
		foreach ($this->regexRedirects as $pattern => $newPath) {
			// Check, if that rule is applicable:
			$match = preg_match('!' . $pattern . '!', $path);
			if ($match === 1) {
				// Yes ... then do the replacements:
				return preg_replace('!' . $pattern . '!', $newPath, $path);
			}
		}

		return null;
	}
}