<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use framework\core\LocaleHandler;
use framework\template\template\TagNode;
use framework\template\template\TagInline;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class LangTag extends TemplateTag implements TagNode, TagInline
{
	public static function getName(): string
	{
		return 'lang';
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
		$replValue = self::replace($node->getAttribute('key')->value, $node->getAttribute('vars')->value);

		$replNode = new TextNode($tplEngine->getDomReader());
		$replNode->content = $replValue;

		$node->parentNode->replaceNode($node, $replNode);
	}

	public function replaceInline(TemplateEngine $tplEngine, $params): string
	{
		$vars = (array_key_exists('vars', $params)) ? $params['vars'] : null;

		return self::replace($params['key'], $vars);
	}

	public function replace($key, $vars = null)
	{

		$phpVars = ', array()';
		if ($vars !== null) {
			$varsEx = explode(',', $vars);
			$varsFull = [];

			foreach ($varsEx as $v) {
				$varsFull[] = '\'' . $v . '\' => self::getData(\'' . $v . '\')';
			}

			$phpVars = ',array(' . implode(', ', $varsFull) . ')';
		}

		return '<?php echo ' . __CLASS__ . '::getText(\'' . $key . '\'' . $phpVars . ', $this); ?>';
	}

	public static function getText($key, array $phpVars, TemplateEngine $tplEngine)
	{
		/** @var LocaleHandler $localeHandler */
		$localeHandler = $tplEngine->getData('_localeHandler');

		return $localeHandler->getText($key, $phpVars);
	}
}
/* EOF */