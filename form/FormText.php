<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

class FormText extends FormElement
{
	private string $text; // Content for this element
	private bool $textIsAlreadyHTML;

	public function __construct(string $text, bool $isHTML = false)
	{
		$this->text = $text;
		$this->textIsAlreadyHTML = $isHTML;
		parent::__construct('text');
	}

	/**
	 * Generate the "html-code" for this Text-Element to be used for output
	 *
	 * @return string : Generated html-code
	 */
	public function render(): string
	{
		return ($this->textIsAlreadyHTML ? $this->text : FormRenderer::htmlEncode($this->text));
	}
}
/* EOF */