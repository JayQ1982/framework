<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

class FormTagAttribute extends FormElement
{
	private ?string $htmlEncodedValue; // The value for this attribute. null = no value (e.g. if name is required, selected or checked)

	/**
	 * @param string      $name             : Name of this attribute. Will be used by tag-renderer like <{$tag->getName()} {$this->getName()}>
	 * @param null|string $htmlEncodedValue : null = no value
	 */
	public function __construct(string $name, ?string $htmlEncodedValue)
	{
		$this->htmlEncodedValue = $htmlEncodedValue;
		parent::__construct($name);
	}

	/**
	 * Generate the html-code for this Attribute-Element to be used for output
	 *
	 * @return string : Generated html-code
	 */
	public function render(): string
	{
		return $this->getName() . (!is_null($this->htmlEncodedValue) ? '="' . $this->htmlEncodedValue . '"' : '');
	}
}
/* EOF */