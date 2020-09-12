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

class OptionsTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'options';
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
		$tplEngine->checkRequiredAttributes($node, ['options']);

		$selectionSelector = ($node->getAttribute('selected') !== null) ? "'{$node->getAttribute('selected')->value}'" : null;
		$optionsSelector = "'{$node->getAttribute('options')->value}'";

		$textContent = '<?php echo ' . __CLASS__ . '::render($this, ' . $optionsSelector . ', ' . $selectionSelector . '); ?>';

		$newNode = new TextNode($tplEngine->getDomReader());
		$newNode->content = $textContent;

		$node->parentNode->insertBefore($newNode, $node);
		$node->parentNode->removeNode($node);
	}

	public static function render(TemplateEngine $tplEngine, $optionsSelector, $selectedSelector)
	{
		$options = $tplEngine->getDataFromSelector($optionsSelector);
		$selection = [];

		if ($selectedSelector !== null) {
			$selection = (array)$tplEngine->getDataFromSelector($selectedSelector);
		}

		return self::renderOptions($options, $selection);
	}

	public static function renderOptions(array $options, array $selection)
	{
		$html = '';

		foreach ($options as $key => $value) {
			if (is_array($value) === true) {
				$html .= '<optgroup label="' . $key . '">' . PHP_EOL . self::renderOptions($value, $selection) . '</optgroup>' . PHP_EOL;
			} else {
				$attributes = [
					'value="' . $key . '"',
				];
				if (in_array($key, $selection)) {
					$attributes[] = 'selected';
				}
				$html .= '<option ' . implode(' ', $attributes) . '>' . $value . '</option>' . PHP_EOL;
			}
		}

		return $html;
	}
}
/* EOF */