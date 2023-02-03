<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\security\CspPolicySettingsModel;
use LogicException;

class EnvironmentSettingsModel
{
	private static ?EnvironmentSettingsModel $registeredInstance = null;

	public static function register(EnvironmentSettingsModel $environmentSettingsModel): void
	{
		if (!is_null(value: EnvironmentSettingsModel::$registeredInstance)) {
			throw new LogicException(message: 'EnvironmentSettingsModel is already registered.');
		}
		EnvironmentSettingsModel::$registeredInstance = $environmentSettingsModel;
	}

	public static function get(): EnvironmentSettingsModel
	{
		return EnvironmentSettingsModel::$registeredInstance;
	}

	public function __construct(
		public readonly array                   $allowedDomains,
		public readonly LanguageCollection      $availableLanguages,
		public readonly bool                    $debug,
		public readonly int                     $copyrightYear,
		public readonly string                  $robots,
		public readonly ?CspPolicySettingsModel $cspPolicySettingsModel
	) {
	}
}