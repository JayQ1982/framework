<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use LogicException;

class HtmlTag extends HtmlElement
{
	/** @var HtmlTagAttribute[] : Array with all HtmlTag-Attributes */
	private array $htmlTagAttributes = [];
	private bool $selfClosing; // Defines, if it is a self-closing tag (which can't have any child elements)
	/** @var HtmlText[]|HtmlTag[] : Array with all child-elements which can be either Tag- or Text-Elements */
	private array $childElements = [];

	/**
	 * @param string             $name : Name of this element, will by used by renderer <{$name}{$attributes}></{$name}>
	 * @param bool               $selfClosing
	 * @param HtmlTagAttribute[] $htmlTagAttributes
	 */
	public function __construct(string $name, bool $selfClosing, array $htmlTagAttributes = [])
	{
		$this->selfClosing = $selfClosing;
		parent::__construct($name);

		foreach ($htmlTagAttributes as $htmlTagAttribute) {
			/** We use this way instead of direct assignment to make sure the attributes are all instances of HtmlTagAttribute */
			$this->addHtmlTagAttribute($htmlTagAttribute);
		}
	}

	/**
	 * Add Tag-Element as child
	 *
	 * @param HtmlTag $htmlTag : The Tag-Element to be added as child
	 */
	public function addTag(HtmlTag $htmlTag): void
	{
		$this->addChildElement($htmlTag);
	}

	/**
	 * Add Text-Tag as child
	 *
	 * @param HtmlText $htmlText : The Text-Tag to be added as child
	 */
	public function addText(HtmlText $htmlText)
	{
		$this->addChildElement($htmlText);
	}

	/**
	 * Add child element. This method throws an Exception, if we try to add a child to a self-closing tag or the child element has an invalid base class.
	 *
	 * @param $childElement : The child-element to be added
	 */
	private function addChildElement($childElement)
	{
		if ($this->selfClosing) {
			throw new LogicException('A self-closing tag cannot have child elements');
		}

		if (!($childElement instanceof HtmlTag) && !($childElement instanceof HtmlText)) {
			throw new LogicException('The child-element must be either instance of HtmlTag or HtmlText');
		}

		$this->childElements[] = $childElement;
	}

	public function addHtmlTagAttribute(HtmlTagAttribute $htmlTagAttribute)
	{
		$this->htmlTagAttributes[] = $htmlTagAttribute;
	}

	/**
	 * Generate the html-code for this Tag-Element (including all children) to be used for output
	 *
	 * @return string : Generated html-code
	 */
	public function render(): string
	{
		$tagName = $this->getName(); // MUST be HTML-safe!
		$html = '<' . $tagName;
		foreach ($this->htmlTagAttributes as $htmlTagAttribute) {
			$html .= ' ' . $htmlTagAttribute->render();
		}
		$html .= '>';
		if (!$this->selfClosing) {

			foreach ($this->childElements as $childElement) {
				$html .= $childElement->render();
			}
			$html .= '</' . $tagName . '>';
		}

		return $html;
	}
}