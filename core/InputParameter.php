<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

class InputParameter
{
	public function __construct(
		public readonly string $name,
		public readonly bool   $isRequired,
		public readonly string $description = ''
	) {

	}
}