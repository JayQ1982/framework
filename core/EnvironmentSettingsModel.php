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
	private static ?EnvironmentSettingsModel $instance = null;

	public function __construct(
		public readonly array                   $allowedDomains,
		public readonly LanguageCollection      $availableLanguages,
		public readonly bool                    $debug,
		public readonly int                     $copyrightYear,
		public readonly string                  $errorLogRecipientEmail,
		public readonly string                  $robots,
		public readonly ?CspPolicySettingsModel $cspPolicySettingsModel
	) {
		if (!is_null(value: EnvironmentSettingsModel::$instance)) {
			throw new LogicException(message: 'There is already an instance of EnvironmentSettingsModel');
		}
	}

	public static function get(): EnvironmentSettingsModel
	{
		return EnvironmentSettingsModel::$instance;
	}
}