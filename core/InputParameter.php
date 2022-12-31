<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

readonly class InputParameter
{
	public function __construct(
		public string $name,
		public bool   $isRequired,
		public string $description = ''
	)
	{
	}
}