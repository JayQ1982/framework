<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

use LogicException;

class FormTag extends FormElement
{
	/** @var FormTagAttribute[] : Array with all FormTag-Attributes */
	private array $formTagAttributes = [];
	private bool $selfClosing; // Defines, if it is a self-closing tag (which can't have any child elements)
	/** @var FormText[]|FormTag[] : Array with all child-elements which can be either Tag- or Text-Elements */
	private array $childElements = [];

	/**
	 * @param string             $name : Name of this element, will by used by renderer <{$name}{$attributes}></{$name}>
	 * @param bool               $selfClosing
	 * @param FormTagAttribute[] $formTagAttributes
	 */
	public function __construct(string $name, bool $selfClosing, array $formTagAttributes = [])
	{
		$this->selfClosing = $selfClosing;
		parent::__construct($name);

		foreach ($formTagAttributes as $formTagAttribute) {
			/** We use this way instead of direct assignment to make sure the attributes are all instances of FormTagAttribute */
			$this->addFormTagAttribute($formTagAttribute);
		}
	}

	/**
	 * Add Tag-Element as child
	 *
	 * @param FormTag $formTag : The Tag-Element to be added as child
	 */
	public function addTag(FormTag $formTag)
	{
		$this->addChildElement($formTag);
	}

	/**
	 * Add Text-Tag as child
	 *
	 * @param FormText $formText : The Text-Tag to be added as child
	 */
	public function addText(FormText $formText)
	{
		$this->addChildElement($formText);
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

		if (!($childElement instanceof FormTag) && !($childElement instanceof FormText)) {
			throw new LogicException('The child-element must be either instance of FormTag or FormText');
		}

		$this->childElements[] = $childElement;
	}

	/**
	 * Add Tag-Attribute to this Tag-Element
	 *
	 * @param FormTagAttribute $formTagAttribute
	 */
	public function addFormTagAttribute(FormTagAttribute $formTagAttribute)
	{
		$this->formTagAttributes[] = $formTagAttribute;
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
		foreach ($this->formTagAttributes as $formTagAttribute) {
			$html .= ' ' . $formTagAttribute->render();
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
/* EOF */