<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\html\HtmlTagAttribute;
use framework\template\htmlparser\ElementNode;
use framework\template\template\TagNode;
use framework\template\template\templateEngine;
use framework\template\template\TemplateTag;

class CheckboxTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'checkbox';
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
		$sels = $elementNode->getAttribute('selection')->getValue();
		$selsStr = '$this->getDataFromSelector(\'' . $sels . '\')';
		$value = $elementNode->getAttribute('value')->getValue();
		$elementNode->removeAttribute('selection');

		$elementNode->namespace = null;
		$elementNode->tagName = 'input';
		if ($sels !== null) {
			$elementNode->tagExtension = ' <?php echo ((is_array('.$selsStr.') && in_array('.$value.', '.$selsStr.')) || ('.$selsStr.' == \''.$value.'\'))?\' checked\':null; ?>';
		}

		$elementNode->addAttribute(new HtmlTagAttribute('type', 'checkbox', true));
	}
}