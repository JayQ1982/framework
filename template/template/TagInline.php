<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\template;

interface TagInline
{
	public function replaceInline(TemplateEngine $tplEngine, array $tagArr): string;

	public static function getName(): string;
}