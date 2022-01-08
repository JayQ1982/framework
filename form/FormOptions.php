<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form;

use framework\html\HtmlText;

class FormOptions
{
	/** @var HtmlText[] */
	private array $data = [];

	/**
	 * @param HtmlText[] $data
	 */
	public function __construct(array $data = [])
	{
		foreach ($data as $key => $val) {
			$this->addItem($key, $val);
		}
	}

	public function addItem(string $key, HtmlText $htmlText): void
	{
		$this->data[$key] = $htmlText;
	}

	public function exists(string $key): bool
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * @return HtmlText[]
	 */
	public function getData(): array
	{
		return $this->data;
	}
}