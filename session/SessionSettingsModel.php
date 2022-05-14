<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\session;

class SessionSettingsModel
{
	public function __construct(
		public readonly string $savePath = '',
		public readonly string $individualName = '',
		public readonly ?int    $maxLifeTime = null,
		public readonly bool   $isSameSiteStrict = true
	) {
	}
}