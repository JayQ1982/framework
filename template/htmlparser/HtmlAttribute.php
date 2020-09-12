<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\htmlparser;

class HtmlAttribute
{
	public string $key;
	public ?string $value;

	public function __construct(string $key, ?string $value)
	{
		$this->key = $key;
		$this->value = $value;
	}
}
/* EOF */