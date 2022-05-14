<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

class HtmlTextCollection
{
	/** @var HtmlText[] */
	private array $items = [];

	/**
	 * @param HtmlText[] $items
	 */
	public function __construct(array $items = [])
	{
		foreach ($items as $item) {
			$this->add(htmlText: $item);
		}
	}

	public function add(HtmlText $htmlText): void
	{
		$this->items[] = $htmlText;
	}

	/**
	 * @return HtmlText[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}
}