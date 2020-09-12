<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use DateTime;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagInline;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class PrintTag extends TemplateTag implements TagNode, TagInline
{
	public static function getName(): string
	{
		return 'print';
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
		$replValue = $this->replace($node->getAttribute('var')->value);

		$replNode = new TextNode($tplEngine->getDomReader());
		$replNode->content = $replValue;

		$node->parentNode->replaceNode($node, $replNode);
	}

	public function replaceInline(TemplateEngine $tplEngine, $params): string
	{
		return $this->replace($params['var']);
	}

	public function replace($selector)
	{
		return '<?php echo ' . __CLASS__ . '::generateOutput($this, \'' . $selector . '\'); ?>';
	}

	public static function generateOutput(TemplateEngine $templateEngine, $selector)
	{
		$data = $templateEngine->getDataFromSelector($selector);

		if ($data instanceof DateTime) {
			return $data->format('Y-m-d H:i:s');
		} else if (is_scalar($data) === false) {
			return print_r($data, true);
		}

		return $data;
	}
}
/* EOF */