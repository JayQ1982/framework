<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

class Language
{
	public function __construct(
		public readonly string $code,
		public readonly string $locale
	) {
	}
}