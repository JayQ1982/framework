<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

readonly class Language
{
	public function __construct(
		public string $code,
		public string $locale
	) {
	}
}