<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\html\HtmlDocument;
use framework\table\TableItemModel;

class StripHtmlTagsColumn extends AbstractTableColumn
{
	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$strippedTags = strip_tags($tableItemModel->getRawValue($this->getIdentifier()));

		return HtmlDocument::htmlEncode($strippedTags, true);
	}
}