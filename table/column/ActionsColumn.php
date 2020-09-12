<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class ActionsColumn extends AbstractTableColumn
{
	private array $actionLinks = [];
	private ?string $hideDeleteLinkField = null;
	private ?string $hideDeleteLinkValue = null;

	public function __construct(string $label = '')
	{
		parent::__construct('actions', $label, false);
		$this->addCellCssClass('action');
	}

	public function addIndividualActionLink(string $identifier, string $linkHTML): void
	{
		$this->actionLinks[$identifier] = $linkHTML;
	}

	public function addEditActionLink(string $linkTarget, string $label = 'Bearbeiten'): void
	{
		$this->actionLinks['edit'] = '<a href="' . $linkTarget . '?edit" class="edit">' . $label . '</a>';
	}

	public function addDeleteLink(string $linkTarget, string $label = 'Löschen', ?string $hideField = null, ?string $hideValue = null): void
	{
		$this->actionLinks['delete'] = '<a href="' . $linkTarget . '" class="delete">' . $label . '</a>';
		$this->hideDeleteLinkField = $hideField;
		$this->hideDeleteLinkValue = $hideValue;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$allActionLinks = $this->actionLinks;
		if (
			isset($this->actionLinks['delete'])
			&& !empty($this->hideDeleteLinkField)
			&& $tableItemModel->getRawValue($this->hideDeleteLinkField) === $this->hideDeleteLinkValue
		) {
			unset($allActionLinks['delete']);
		}

		if (count($allActionLinks) === 0) {
			return '';
		}

		$srcArr = [];
		$rplArr = [];
		foreach ($tableItemModel->getAllData() as $key => $val) {
			$srcArr[] = '[' . $key . ']';
			$rplArr[] = htmlspecialchars($val, ENT_QUOTES);
		}

		if (count($allActionLinks) === 1) {
			return implode('', str_replace($srcArr, $rplArr, $allActionLinks));
		}

		$returnHTML = ['<ul>'];
		foreach ($allActionLinks as $actionLink) {
			$returnHTML[] = str_replace($srcArr, $rplArr, $actionLink);
		}
		$returnHTML[] = '</ul>';

		return implode(PHP_EOL, $returnHTML);
	}
}
/* EOF */