<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class FormComponentTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'formComponent';
	}

	public static function isElseCompatible()
	{
		return false;
	}

	public static function isSelfClosing()
	{
		return true;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $node)
	{
		$tplEngine->checkRequiredAttributes($node, ['form', 'name']);

		// DATA
		$newNode = new TextNode($tplEngine->getDomReader());
		$newNode->content = '<?= ' . self::class . '::render(\'' . $node->getAttribute('form')->value . '\', \'' . $node->getAttribute('name')->value . '\', $this); ?>';

		$node->parentNode->insertBefore($newNode, $node);
		$node->parentNode->removeNode($node);
	}

	public static function render($formSelector, $componentName, TemplateEngine $tplEngine)
	{
		$callback = [$tplEngine->getDataFromSelector($formSelector), 'getChildComponent'];
		$component = call_user_func($callback, $componentName);

		return call_user_func([$component, 'render']);
	}
}
/* EOF */