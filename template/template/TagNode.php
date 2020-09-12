<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\template;

use framework\template\htmlparser\ElementNode;

interface TagNode
{
	/**
	 * Replaces the custom tag as node
	 *
	 * @param TemplateEngine $tplEngine
	 * @param ElementNode    $tagNode
	 */
	public function replaceNode(TemplateEngine $tplEngine, ElementNode $tagNode);

	/**
	 * @return string
	 */
	public static function getName();

	/**
	 * @return bool
	 */
	public static function isElseCompatible();

	/**
	 * @return bool
	 */
	public static function isSelfClosing();
}
/* EOF */