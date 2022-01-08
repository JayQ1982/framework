<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\renderer;

use framework\common\Pagination;
use framework\table\table\DbResultTable;

class TablePaginationRenderer
{
	public function render(DbResultTable $dbResultTable, int $entriesPerPage = 25, int $beforeAfter = 2, int $startEnd = 1): string
	{
		return (new Pagination())->render(
			$dbResultTable->getIdentifier(),
			$dbResultTable->getTotalAmount(),
			$dbResultTable->getCurrentPaginationPage(),
			$entriesPerPage,
			$beforeAfter,
			$startEnd,
			$dbResultTable->getAdditionalLinkParameters()
		);
	}
}