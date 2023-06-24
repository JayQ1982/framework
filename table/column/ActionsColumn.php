<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\html\HtmlEncoder;
use framework\table\TableItemModel;

class ActionsColumn extends AbstractTableColumn
{
	private array $actionLinks = [];
	private ?string $hideDeleteLinkField = null;
	private ?string $hideDeleteLinkValue = null;

	public function __construct(string $label = '', string $cellCssClass = 'action')
	{
		parent::__construct(identifier: 'actions', label: $label);
		$this->addCellCssClass(className: $cellCssClass);
	}

	public function addIndividualActionLink(string $identifier, string $linkHTML): void
	{
		$this->actionLinks[$identifier] = $linkHTML;
	}

	public function addEditActionLink(string $linkTarget, string $label = 'Bearbeiten'): void
	{
		$this->actionLinks['edit'] = '<a href="' . $linkTarget . '" class="edit">' . $label . '</a>';
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
			&& $tableItemModel->getRawValue(name: $this->hideDeleteLinkField) === $this->hideDeleteLinkValue
		) {
			unset($allActionLinks['delete']);
		}

		if (count($allActionLinks) === 0) {
			return '';
		}

		$srcArr = [];
		$rplArr = [];
		foreach ($tableItemModel->data as $key => $val) {
			$srcArr[] = '[' . $key . ']';
			$rplArr[] = HtmlEncoder::encode(value: $val);
		}

		if (count($allActionLinks) === 1) {
			return implode(
				separator: PHP_EOL,
				array: [
					implode(
						separator: PHP_EOL,
						array: str_replace(
							search: $srcArr,
							replace: $rplArr,
							subject: $allActionLinks
						)
					),
				]
			);
		}

		foreach ($allActionLinks as $key => $val) {
			$allActionLinks[$key] = '<li>' . $val . '</li>';
		}

		return implode(
			separator: PHP_EOL,
			array: [
				'<ul>',
				implode(
					separator: PHP_EOL,
					array: str_replace(
						search: $srcArr,
						replace: $rplArr,
						subject: $allActionLinks
					)
				),
				'</ul>',
			]
		);
	}
}