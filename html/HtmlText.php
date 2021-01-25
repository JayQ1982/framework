<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\html;

class HtmlText extends HtmlElement
{
	private string $textContent;
	private bool $isEncodedForRendering;

	public static function encoded(string $textContent): HtmlText
	{
		return new HtmlText($textContent, true);
	}

	public static function unencoded(string $textContent): HtmlText
	{
		return new HtmlText($textContent, false);
	}

	public function __construct(string $textContent, bool $isEncodedForRendering)
	{
		$this->textContent = $textContent;
		$this->isEncodedForRendering = $isEncodedForRendering;
		parent::__construct('htmlText');
	}

	/**
	 * Generate the "html-code" for this Text-Element to be used for output
	 *
	 * @return string : Generated html-code
	 */
	public function render(): string
	{
		return $this->isEncodedForRendering ? $this->textContent : HtmlDocument::htmlEncode($this->textContent);
	}
}