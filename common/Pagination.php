<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

class Pagination
{
	public static function render(int $totalAmount, int $currentPage, int $entriesPerPage = 25, int $beforeAfter = 2, int $startEnd = 1, string $linkParams = ''): string
	{
		if ($totalAmount <= $entriesPerPage) {
			return '';
		}

		$firstPage = 1;
		$modulo = ($totalAmount % $entriesPerPage);
		$maxPage = (($totalAmount - ($modulo)) / $entriesPerPage) + ($modulo === 0 ? 0 : 1);

		$pagenavi = '<div class="pagination clearfix">' . PHP_EOL . '<ul>' . PHP_EOL;
		if ($currentPage === $firstPage) {
			$pagenavi .= '<li class="backdisabled">&laquo;</li>' . PHP_EOL;
		} else {
			$beforePage = $currentPage - $firstPage;
			$pagenavi .= '<li class="back"><a href="?page=' . $beforePage . $linkParams . '">&laquo;</a></li>' . PHP_EOL;
		}

		for ($page = $firstPage; $page <= $maxPage; $page++) {
			if (
				$page === $firstPage
				|| $page === $currentPage
				|| $page === $maxPage
				|| ($page <= $firstPage + $startEnd)
				|| ($page >= $maxPage - $startEnd)
				|| ($page < $currentPage && $page >= ($currentPage - $beforeAfter))
				|| ($page > $currentPage && $page <= ($currentPage + $beforeAfter))
			) {
				if ($page === ($maxPage - $startEnd) && $currentPage < $maxPage - ($beforeAfter + 1) - $startEnd) {
					$pagenavi .= '<li><span>...</span></li>' . PHP_EOL;
				}
				if ($page === $currentPage) {
					$pagenavi .= '<li class="currentpage"><strong>' . $page . '</strong></li>' . PHP_EOL;
				} else {
					$pagenavi .= '<li><a href="?page=' . $page . $linkParams . '">' . $page . '</a></li>' . PHP_EOL;
				}
				if ($page === ($firstPage + $startEnd) && $currentPage > $firstPage + ($beforeAfter + 1) + $startEnd) {
					$pagenavi .= '<li><span>...</span></li>' . PHP_EOL;
				}
			}
		}

		if ($currentPage === $maxPage) {
			$pagenavi .= '<li class="nextdisabled">&raquo;</li>' . PHP_EOL;
		} else {
			$nextPage = $currentPage + 1;
			$pagenavi .= '<li class="next"><a href="?page=' . $nextPage . $linkParams . '">&raquo;</a></li>' . PHP_EOL;
		}
		$pagenavi .= '</ul>' . PHP_EOL . '</div>' . PHP_EOL;

		return $pagenavi;
	}
}
/* EOF */