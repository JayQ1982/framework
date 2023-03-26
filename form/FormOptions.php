<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form;

use framework\html\HtmlText;

class FormOptions
{
	/** @var HtmlText[] */
	private array $data = [];

	public function __construct() { }

	public function addItem(string $key, HtmlText $htmlText): void
	{
		$this->data[$key] = $htmlText;
	}

	public function exists(string $key): bool
	{
		return array_key_exists(key: $key, array: $this->data);
	}

	/**
	 * @return HtmlText[]
	 */
	public function getData(): array
	{
		return $this->data;
	}
}