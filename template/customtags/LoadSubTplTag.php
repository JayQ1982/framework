<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
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
	public static function getName()
	{
		return 'loadSubTpl';
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
		$dataKey = $node->getAttribute('tplfile')->value;

		$tplFile = null;

		$tplFile = (preg_match('/^{(.+)}$/', $dataKey, $res) === 1) ? '$this->getData(\'' . $res[1] . '\')' : '\'' . $dataKey . '\'';

		/** @var TextNode */
		$newNode = new TextNode($tplEngine->getDomReader());
		$newNode->content = '<?php ' . __NAMESPACE__ . '\\LoadSubTplTag::requireFile(' . $tplFile . ', $this); ?>'; //$newTpl->getResultAsHtml();

		$node->parentNode->replaceNode($node, $newNode);
	}

	public function replaceInline(): void
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
	public static function requireFile(string $file, TemplateEngine $tplEngine)
	{
		echo $tplEngine->getResultAsHtml($file, (array)$tplEngine->getAllData());
	}
}
/* EOF */