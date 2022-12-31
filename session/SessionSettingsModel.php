<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\session;

readonly class SessionSettingsModel
{
	public function __construct(
		public string $savePath = '',
		public string $individualName = '',
		public ?int   $maxLifeTime = null,
		public bool   $isSameSiteStrict = true
	) {
	}
}