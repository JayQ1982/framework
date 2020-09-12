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

class FormAddRemoveTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'formAddRemove';
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
		$tplEngine->checkRequiredAttributes($node, ['chosen', 'name']);

		$chosenEntriesSelector = $node->getAttribute('chosen')->value;
		$poolEntriesSelector = $node->doesAttributeExist('pool') ? $node->getAttribute('pool')->value : null;
		$nameSelector = $node->getAttribute('name')->value;

		$newNode = new TextNode($tplEngine->getDomReader());
		$newNode->content = '<?= ' . self::class . '::render(\'' . $nameSelector . '\', \'' . $chosenEntriesSelector . '\', \'' . $poolEntriesSelector . '\', $this); ?>';

		$node->parentNode->insertBefore($newNode, $node);
		$node->parentNode->removeNode($node);
	}

	public static function render($name, $chosenSelector, $poolSelector, TemplateEngine $tplEngine)
	{
		$chosenEntries = $tplEngine->getDataFromSelector($chosenSelector);
		$poolEntries = [];

		if ($poolSelector !== null) {
			$poolEntries = $tplEngine->getDataFromSelector($poolSelector);
		}

		$html = '<div class="add-remove" name="' . $name . '">';

		$html .= '<ul class="option-list chosen">';

		foreach ($chosenEntries as $id => $title) {
			$html .= '<li id="' . $name . '-' . $id . '">' . $title . '</li>';
		}

		$html .= '</ul>';

		if (count($poolEntries) > 0) {
			// left or right
			$html .= '<div class="between">
				<a href="#" class="entries-add" title="add selected entries">&larr;</a>
				<br>
				<a href="#" class="entries-remove" title="remove selected entries">&rarr;</a>
			</div>';

			// Pool
			$html .= '<ul class="option-list pool">';

			foreach ($poolEntries as $id => $title) {
				$html .= '<li id="' . $name . '-' . $id . '">' . $title . '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '</div>';

		return $html;
	}
}
/* EOF */