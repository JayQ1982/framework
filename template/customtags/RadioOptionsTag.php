<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class RadioOptionsTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'radioOptions';
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
		$tplEngine->checkRequiredAttributes($node, ['options', 'checked']);

		$checkedSelector = $node->getAttribute('checked')->value;
		$optionsSelector = $node->getAttribute('options')->value;
		$fldName = $node->getAttribute('name')->value;

		$textContent = "<?php print " . __CLASS__ . "::render(\$this, '{$fldName}', '{$optionsSelector}', '{$checkedSelector}'); ?>";

		$newNode = new TextNode($tplEngine->getDomReader());
		$newNode->content = $textContent;

		$node->parentNode->insertBefore($newNode, $node);
		$node->parentNode->removeNode($node);
	}

	public static function render(TemplateEngine $tplEngine, $fldName, $optionsSelector, $checkedSelector)
	{
		$options = $tplEngine->getDataFromSelector($optionsSelector);
		$selection = (array)$tplEngine->getDataFromSelector($checkedSelector);

		$html = '<ul>';

		foreach ($options as $key => $val) {
			$attributes = [
				'type="radio"',
				'value="' . $key . '"',
				'name="' . $fldName . '"',
			];
			if (in_array($key, $selection)) {
				$attributes[] = 'checked';
			}
			$html .= '<li><label><input ' . implode(' ', $attributes) . '> ' . $val . '</label ></li > ' . PHP_EOL;
		}

		$html .= '</ul > ';

		return $html;
	}
}
/* EOF */