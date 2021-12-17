<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\customtags;

use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class FormAddRemoveTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'formAddRemove';
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
		$tplEngine->checkRequiredAttributes($elementNode, ['chosen', 'name']);

		$chosenEntriesSelector = $elementNode->getAttribute('chosen')->getValue();
		$poolEntriesSelector = $elementNode->doesAttributeExist('pool') ? $elementNode->getAttribute('pool')->getValue() : null;
		$nameSelector = $elementNode->getAttribute('name')->getValue();

		$newNode = new TextNode();
		$newNode->content = '<?= ' . FormAddRemoveTag::class . '::render(\'' . $nameSelector . '\', \'' . $chosenEntriesSelector . '\', \'' . $poolEntriesSelector . '\', $this); ?>';

		$elementNode->parentNode->insertBefore($newNode, $elementNode);
		$elementNode->parentNode->removeNode($elementNode);
	}

	public static function render($name, $chosenSelector, $poolSelector, TemplateEngine $tplEngine): string
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