<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\session;

class FileSessionHandler extends AbstractSessionHandler
{
	public function __construct(
		private readonly SessionSettingsModel $sessionSettingsModel
	) {
		parent::__construct(sessionSettingsModel: $sessionSettingsModel);
	}

	protected function executePreStartActions(): void
	{
		if ($this->sessionSettingsModel->savePath !== '') {
			session_save_path(path: $this->sessionSettingsModel->savePath);
		}
	}
}