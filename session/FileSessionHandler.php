<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\session;

use framework\core\EnvironmentSettingsModel;
use framework\datacheck\Sanitizer;

class FileSessionHandler extends AbstractSessionHandler
{
	private EnvironmentSettingsModel $environmentSettingsModel;

	public function __construct(EnvironmentSettingsModel $environmentSettingsModel)
	{
		$this->environmentSettingsModel = $environmentSettingsModel;
		parent::__construct(environmentSettingsModel: $environmentSettingsModel);
	}

	protected function executePreStartActions(): void
	{
		$sessionSavePath = Sanitizer::trimmedString($this->environmentSettingsModel->getSessionSettingsModel()->getSavePath());
		if ($sessionSavePath !== '') {
			session_save_path(path: $sessionSavePath);
		}
	}
}