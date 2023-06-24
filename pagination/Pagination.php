<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\pagination;

use framework\Core;
use framework\html\HtmlDataObject;
use framework\html\HtmlDataObjectCollection;
use framework\html\HtmlReplacementCollection;
use framework\html\HtmlSnippet;

/**
 * Provides a Pagination function for usage on the whole project
 * Like: << 1 2 ... 5 6 7 8 ... 23 24 25 >>
 */
class Pagination
{
	public static function render(
		string  $listIdentifier,
		int     $totalAmount,
		int     $currentPage,
		int     $entriesPerPage = 25,
		int     $beforeAfter = 2,
		int     $startEnd = 1,
		array   $additionalLinkParameters = [],
		string  $previousTitle = 'Zurück',
		string  $nextTitle = 'Vor',
		?string $individualHtmlSnippetPath = null
	): string {
		if ($totalAmount <= $entriesPerPage) {
			return '';
		}
		$firstPage = 1;
		$modulo = ($totalAmount % $entriesPerPage);
		$maxPage = (($totalAmount - ($modulo)) / $entriesPerPage) + ($modulo === 0 ? 0 : 1);
		$pages = new HtmlDataObjectCollection();
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
				$pageObject = new HtmlDataObject();
				$pageObject->addBooleanValue(
					propertyName: 'groupPreviousPages',
					booleanValue: ($page === ($maxPage - $startEnd) && $currentPage < $maxPage - ($beforeAfter + 1) - $startEnd)
				);
				$pageObject->addBooleanValue(
					propertyName: 'isCurrentPage',
					booleanValue: ($page === $currentPage)
				);
				$pageObject->addTextElement(propertyName: 'number', content: $page, isEncodedForRendering: true);
				$pageObject->addTextElement(
					propertyName: 'href',
					content: Pagination::getLinkTarget(
						listIdentifier: $listIdentifier,
						pageNumber: $page,
						additionalLinkParameters: $additionalLinkParameters
					),
					isEncodedForRendering: true
				);
				$pageObject->addBooleanValue(
					propertyName: 'groupNextPages',
					booleanValue: ($page === ($firstPage + $startEnd) && $currentPage > $firstPage + ($beforeAfter + 1) + $startEnd)
				);
				$pages->add(htmlDataObject: $pageObject);
			}
		}
		$replacements = new HtmlReplacementCollection();
		$replacements->addEncodedText(identifier: 'previousTitle', content: $previousTitle);
		$replacements->addEncodedText(
			identifier: 'previousPageHref',
			content: ($currentPage === $firstPage) ? '' : Pagination::getLinkTarget(
				listIdentifier: $listIdentifier,
				pageNumber: $currentPage - 1,
				additionalLinkParameters: $additionalLinkParameters
			)
		);
		$replacements->addHtmlDataObjectCollection(identifier: 'pages', htmlDataObjectCollection: $pages);
		$replacements->addEncodedText(identifier: 'nextTitle', content: $nextTitle);
		$replacements->addEncodedText(
			identifier: 'nextPageHref',
			content: ($currentPage === $maxPage) ? '' : Pagination::getLinkTarget(
				listIdentifier: $listIdentifier,
				pageNumber: $currentPage + 1,
				additionalLinkParameters: $additionalLinkParameters
			)
		);

		return (new HtmlSnippet(
			htmlSnippetFilePath: is_null(value: $individualHtmlSnippetPath) ? Core::get()->frameworkDirectory . 'pagination' . DIRECTORY_SEPARATOR . 'pagination.html' : $individualHtmlSnippetPath,
			replacements: $replacements
		))->render();
	}

	private static function getLinkTarget(string $listIdentifier, int $pageNumber, array $additionalLinkParameters): string
	{
		$getAttributes = [];
		foreach (array_merge(['page' => $pageNumber . '|' . $listIdentifier], $additionalLinkParameters) as $key => $val) {
			$getAttributes[] = $key . '=' . $val;
		}

		return '?' . implode(separator: '&', array: $getAttributes);
	}
}