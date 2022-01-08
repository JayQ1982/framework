<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
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

	public static function isElseCompatible(): bool
	{
		return false;
	}

	public static function isSelfClosing(): bool
	{
		return true;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$replValue = LangTag::replace($elementNode->getAttribute('key')->getValue(), $elementNode->getAttribute('vars')->getValue());

		$replNode = new TextNode();
		$replNode->content = $replValue;

		$elementNode->parentNode->replaceNode($elementNode, $replNode);
	}

	public function replaceInline(TemplateEngine $tplEngine, $tagArr): string
	{
		$vars = (array_key_exists('vars', $tagArr)) ? $tagArr['vars'] : null;

		return LangTag::replace($tagArr['key'], $vars);
	}

	public function replace($key, ?string $vars = null): string
	{
		$phpVars = ', array()';
		if (!is_null($vars)) {
			$varsEx = explode(',', $vars);
			$varsFull = [];

			foreach ($varsEx as $v) {
				$varsFull[] = '\'' . $v . '\' => LangTag::getData(\'' . $v . '\')';
			}

			$phpVars = ',array(' . implode(', ', $varsFull) . ')';
		}

		return '<?php echo ' . __CLASS__ . '::getText(\'' . $key . '\'' . $phpVars . ', $this); ?>';
	}

	public static function getText($key, array $phpVars, TemplateEngine $tplEngine): string
	{
		/** @var LocaleHandler $localeHandler */
		$localeHandler = $tplEngine->getData('_localeHandler');

		return $localeHandler->getText($key, $phpVars);
	}
}