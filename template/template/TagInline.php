<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\template;

interface TagInline
{
	public function replaceInline(TemplateEngine $tplEngine, array $tagArr): string;

	public static function getName(): string;
}