<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

use framework\html\HtmlTagAttribute;

class ElementNode extends HtmlNode
{
	const TAG_OPEN = 1;
	const TAG_CLOSE = 2;
	const TAG_SELF_CLOSING = 3;

	public ?int $tagType = null;
	public ?string $tagName = null;
	public ?string $namespace = null;
	/** @var HtmlTagAttribute[] */
	private array $attributes = [];
	public ?string $tagExtension = null;
	public bool $closed = false;

	public function __construct(HtmlDoc $htmlDocument)
	{
		parent::__construct(HtmlNode::ELEMENT_NODE, $htmlDocument);
	}

	/**
	 * @return HtmlTagAttribute[]
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function getAttribute(string $name): HtmlTagAttribute
	{
		if (!array_key_exists($name, $this->attributes)) {
			return new HtmlTagAttribute($name, null, true);
		}

		return $this->attributes[$name];
	}

	public function updateAttribute(string $name, HtmlTagAttribute $htmlTagAttribute): void
	{
		$this->attributes[$name] = $htmlTagAttribute;
	}

	public function addAttribute(HtmlTagAttribute $htmlTagAttribute)
	{
		$this->attributes[$htmlTagAttribute->getName()] = $htmlTagAttribute;
	}

	public function doesAttributeExist(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
	}

	public function removeAttribute(string $name): void
	{
		if (array_key_exists($name, $this->attributes)) {
			unset($this->attributes[$name]);
		}
	}

	public function getInnerHtml(?ElementNode $entryNode = null): string
	{
		$html = '';
		$nodeList = null;

		if ($entryNode === null) {
			$nodeList = $this->childNodes;
		} else {
			$nodeList = $entryNode->childNodes;
		}

		if ($nodeList === null) {
			return $html;
		}

		/** @var ElementNode $node */
		foreach ($nodeList as $node) {
			if ($node instanceof ElementNode === false) {
				$html .= $node->content;
				continue;
			}

			$tagStr = (($node->namespace !== null) ? $node->namespace . ':' : '') . $node->tagName;

			$attrs = [];
			foreach ($node->attributes as $htmlTagAttribute) {
				$attrs[] = $htmlTagAttribute->render();
			}
			$attrStr = (count($attrs) > 0) ? ' ' . implode(' ', $attrs) : '';

			$html .= '<' . $tagStr . $attrStr . $node->tagExtension . (($node->tagType === ElementNode::TAG_SELF_CLOSING) ? ' /' : '') . '>' . $node->content;

			if ($node->tagType === ElementNode::TAG_OPEN) {
				$html .= $this->getInnerHtml($node) . '</' . $tagStr . '>';
			}
		}

		return $html;
	}
}