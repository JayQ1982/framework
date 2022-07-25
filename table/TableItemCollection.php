<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table;

class TableItemCollection
{
	/** @var TableItemModel[] */
	private array $items = [];
	private int $amount = 0;

	public function add(TableItemModel $tableItemModel): void
	{
		$this->items[] = $tableItemModel;
		$this->amount++;
	}

	/**
	 * @return TableItemModel[]
	 */
	public function list(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return $this->amount;
	}
}