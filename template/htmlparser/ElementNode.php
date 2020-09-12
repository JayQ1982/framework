<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\htmlparser;

class ElementNode extends HtmlNode
{
	const TAG_OPEN = 1;
	const TAG_CLOSE = 2;
	const TAG_SELF_CLOSING = 3;
	public ?int $tagType = null;
	public ?string $tagName = null;
	public ?string $namespace = null;
	public array $attributes = [];
	public array $attributesNamed = [];
	public ?string $tagExtension = null;
	public bool $closed = false;

	public function __construct(HtmlDoc $htmlDocument)
	{
		parent::__construct(HtmlNode::ELEMENT_NODE, $htmlDocument);
	}

	public function getAttribute(string $key): HtmlAttribute
	{
		if (isset($this->attributesNamed[$key]) === false) {
			return new HtmlAttribute($key, null);
		}

		return $this->attributesNamed[$key];
	}

	public function addAttribute(HtmlAttribute $attr)
	{
		$this->attributes[] = $attr;
		$this->attributesNamed[$attr->key] = $attr;
	}

	public function doesAttributeExist($key)
	{
		return isset($this->attributesNamed[$key]);
	}

	public function removeAttribute($key)
	{
		if (isset($this->attributesNamed[$key]) === true) {
			unset($this->attributesNamed[$key]);
		}
	}

	/**
	 * @param ElementNode|null $entryNode
	 *
	 * @return string
	 */
	public function getInnerHtml(ElementNode $entryNode = null): string
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

		/** @var ElementNode|HtmlNode $node */
		foreach ($nodeList as $node) {
			if ($node instanceof ElementNode === false) {
				$html .= $node->content;
				continue;
			}

			$tagStr = (($node->namespace !== null) ? $node->namespace . ':' : '') . $node->tagName;

			$attrs = [];
			foreach ($node->attributesNamed as $key => $val) {
				$attrs[] = $key . '="' . $val->value . '"';
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
/* EOF */