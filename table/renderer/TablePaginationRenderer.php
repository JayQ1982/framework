<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\renderer;

use framework\common\Pagination;
use framework\table\table\DbResultTable;

readonly class TablePaginationRenderer
{
	public function __construct(public Pagination $pagination) { }

	public function render(DbResultTable $dbResultTable, int $entriesPerPage = 25, int $beforeAfter = 2, int $startEnd = 1): string
	{
		return $this->pagination->render(
			listIdentifier: $dbResultTable->identifier,
			totalAmount: $dbResultTable->getTotalAmount(),
			currentPage: $dbResultTable->getCurrentPaginationPage(),
			entriesPerPage: $entriesPerPage,
			beforeAfter: $beforeAfter,
			startEnd: $startEnd,
			additionalLinkParameters: $dbResultTable->getAdditionalLinkParameters()
		);
	}
}