<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

class HtmlDataObjectCollection
{
	/** @var HtmlDataObject[] */
	private array $items = [];

	public function add(HtmlDataObject $htmlDataObject): void
	{
		$this->items[] = $htmlDataObject;
	}

	/**
	 * @return HtmlDataObject[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}
}