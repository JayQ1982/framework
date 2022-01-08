<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

/**
 * Provides a Pagination function for usage on the whole project
 * Like: << 1 2 ... 5 6 7 8 ... 23 24 25 >>
 */
class Pagination
{
	protected const linkTarget = '[linkTarget]';
	protected const pageNumber = '[pageNumber]';

	private string $rootDivClass = 'pagination clearfix';
	private string $ulClass = '';
	private string $backDisabledHtml = '<li class="backdisabled">&laquo;</li>';
	private string $backHtml = '<li class="back"><a href="' . Pagination::linkTarget . '">&laquo;</a></li>';
	private string $multiplePagesHtml = '<li><span>…</span></li>';
	private string $currentPageHtml = '<li class="currentpage"><strong>' . Pagination::pageNumber . '</strong></li>';
	private string $availablePageHtml = '<li><a href="' . Pagination::linkTarget . '">' . Pagination::pageNumber . '</a></li>';
	private string $nextDisabledHtml = '<li class="nextdisabled">&raquo;</li>';
	private string $nextHtml = '<li class="next"><a href="' . Pagination::linkTarget . '">&raquo;</a></li>';

	public function setRootDivClass(string $rootDivClass): void
	{
		$this->rootDivClass = $rootDivClass;
	}

	public function getRootDivClass(): string
	{
		return $this->rootDivClass;
	}

	public function setUlClass(string $ulClass): void
	{
		$this->ulClass = $ulClass;
	}

	public function setBackDisabledHtml(string $backDisabledHtml): void
	{
		$this->backDisabledHtml = $backDisabledHtml;
	}

	public function setBackHtml(string $backHtml): void
	{
		$this->backHtml = $backHtml;
	}

	public function setMultiplePagesHtml(string $multiplePagesHtml): void
	{
		$this->multiplePagesHtml = $multiplePagesHtml;
	}

	public function setCurrentPageHtml(string $currentPageHtml): void
	{
		$this->currentPageHtml = $currentPageHtml;
	}

	public function setAvailablePageHtml(string $availablePageHtml): void
	{
		$this->availablePageHtml = $availablePageHtml;
	}

	public function setNextDisabledHtml(string $nextDisabledHtml): void
	{
		$this->nextDisabledHtml = $nextDisabledHtml;
	}

	public function setNextHtml(string $nextHtml): void
	{
		$this->nextHtml = $nextHtml;
	}

	public function render(string $listIdentifier, int $totalAmount, int $currentPage, int $entriesPerPage = 25, int $beforeAfter = 2, int $startEnd = 1, array $additionalLinkParameters = []): string
	{
		if ($totalAmount <= $entriesPerPage) {
			return '';
		}

		$firstPage = 1;
		$modulo = ($totalAmount % $entriesPerPage);
		$maxPage = (($totalAmount - ($modulo)) / $entriesPerPage) + ($modulo === 0 ? 0 : 1);

		$html = ['<div class="' . $this->rootDivClass . '">'];
		$html[] = ($this->ulClass === '') ? '<ul>' : '<ul class="' . $this->ulClass . '">';

		if ($currentPage === $firstPage) {
			$html[] = $this->backDisabledHtml;
		} else {
			$beforePage = $currentPage - $firstPage;
			$html[] = str_replace(Pagination::linkTarget, $this->getLinkTarget($listIdentifier, $beforePage, $additionalLinkParameters), $this->backHtml);
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
					$html[] = $this->multiplePagesHtml;
				}
				if ($page === $currentPage) {
					$html[] = str_replace([
						Pagination::linkTarget,
						Pagination::pageNumber,
					], [
						$this->getLinkTarget($listIdentifier, $page, $additionalLinkParameters),
						$page,
					], $this->currentPageHtml);
				} else {
					$html[] = str_replace([
						Pagination::linkTarget,
						Pagination::pageNumber,
					], [
						$this->getLinkTarget($listIdentifier, $page, $additionalLinkParameters),
						$page,
					], $this->availablePageHtml);
				}
				if ($page === ($firstPage + $startEnd) && $currentPage > $firstPage + ($beforeAfter + 1) + $startEnd) {
					$html[] = $this->multiplePagesHtml;
				}
			}
		}

		if ($currentPage === $maxPage) {
			$html[] = $this->nextDisabledHtml;
		} else {
			$nextPage = $currentPage + 1;
			$html[] = str_replace(Pagination::linkTarget, $this->getLinkTarget($listIdentifier, $nextPage, $additionalLinkParameters), $this->nextHtml);
		}
		$html[] = '</ul>';
		$html[] = '</div>';

		return implode(PHP_EOL, $html);
	}

	private function getLinkTarget(string $listIdentifier, int $pageNumber, array $additionalLinkParameters): string
	{
		$getAttributes = [];
		foreach (array_merge(['page' => $pageNumber . '|' . $listIdentifier], $additionalLinkParameters) as $key => $val) {
			$getAttributes[] = $key . '=' . $val;
		}

		return '?' . implode('&', $getAttributes);
	}
}