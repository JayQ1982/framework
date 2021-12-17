<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\customtags;

use Exception;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class LoadSubTplTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'loadSubTpl';
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
		$dataKey = $elementNode->getAttribute('tplfile')->getValue();
		$tplFile = (preg_match('/^{(.+)}$/', $dataKey, $res) === 1) ? '$this->getData(\'' . $res[1] . '\')' : '\'' . $dataKey . '\'';

		/** @var TextNode */
		$newNode = new TextNode();
		$newNode->content = '<?php ' . __NAMESPACE__ . '\\LoadSubTplTag::requireFile(' . $tplFile . ', $this); ?>';

		$elementNode->parentNode->replaceNode($elementNode, $newNode);
	}

	public function replaceInline()
	{
		throw new Exception('Don\'t use this tag (LoadSubTpl) inline!');
	}

	/**
	 * A special method that belongs to the LoadSubTplTag class but needs none
	 * static properties from this class and is called from the cached template
	 * files.
	 *
	 * @param string         $file The full filepath to include (OR magic {this})
	 * @param TemplateEngine $tplEngine
	 */
	public static function requireFile(string $file, TemplateEngine $tplEngine): void
	{
		if ($file === '') {
			echo '';

			return;
		}
		echo $tplEngine->getResultAsHtml($file, (array)$tplEngine->getAllData());
	}
}