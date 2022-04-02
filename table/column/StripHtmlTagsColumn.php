<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\html\HtmlEncoder;
use framework\table\TableItemModel;

class StripHtmlTagsColumn extends AbstractTableColumn
{
	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$strippedTags = strip_tags($tableItemModel->getRawValue($this->getIdentifier()));

		return HtmlEncoder::encodeKeepQuotes(value: $strippedTags);
	}
}