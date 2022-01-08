<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
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
		$replValue = $this->replace($elementNode->getAttribute('var')->getValue());

		$replNode = new TextNode();
		$replNode->content = $replValue;

		$elementNode->parentNode->replaceNode($elementNode, $replNode);
	}

	public function replaceInline(TemplateEngine $tplEngine, $tagArr): string
	{
		return $this->replace($tagArr['var']);
	}

	public function replace($selector): string
	{
		return '<?php echo ' . __CLASS__ . '::generateOutput($this, \'' . $selector . '\'); ?>';
	}

	public static function generateOutput(TemplateEngine $templateEngine, $selector): float|bool|int|string
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