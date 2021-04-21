<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\session;

use framework\core\EnvironmentSettingsModel;
use framework\core\HttpRequest;

class FileSessionHandler extends AbstractSessionHandler
{
	private EnvironmentSettingsModel $environmentSettingsModel;

	public function __construct(EnvironmentSettingsModel $environmentSettingsModel, HttpRequest $httpRequest)
	{
		$this->environmentSettingsModel = $environmentSettingsModel;
		parent::__construct($environmentSettingsModel, $httpRequest);
	}

	protected function executePreStartActions(): void
	{
		$sessionSavePath = $this->environmentSettingsModel->getSessionSettingsModel()->getSavePath();
		if (!is_null($sessionSavePath) && trim($sessionSavePath) !== '') {
			session_save_path($sessionSavePath);
		}
	}
}