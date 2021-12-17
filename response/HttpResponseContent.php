<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\response;

abstract class HttpResponseContent
{
	private string $content;

	protected function __construct(string $content)
	{
		$this->content = $content;
	}

	public function getContent(): string
	{
		return $this->content;
	}
}