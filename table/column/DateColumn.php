<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use DateTimeImmutable;
use framework\html\HtmlText;
use framework\table\TableItemModel;

class DateColumn extends AbstractTableColumn
{
	private string $format = 'd.m.Y H:i:s';
	private ?HtmlText $emptyValueText = null;

	public function setFormat(string $format): void
	{
		$this->format = $format;
	}

	public function setEmptyValueText(HtmlText $htmlText): void
	{
		$this->emptyValueText = $htmlText;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$value = trim(string: (string)$tableItemModel->getRawValue(name: $this->identifier));

		if ($value === '') {
			return is_null(value: $this->emptyValueText) ? '' : $this->emptyValueText->render();
		}

		return (new DateTimeImmutable(datetime: $value))->format(format: $this->format);
	}
}