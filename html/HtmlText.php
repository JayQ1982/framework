<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

class HtmlText extends HtmlElement
{
	private string $textContent;
	private bool $isEncodedForRendering;

	private function __construct(string $textContent, bool $isEncodedForRendering)
	{
		$this->textContent = $textContent;
		$this->isEncodedForRendering = $isEncodedForRendering;
		parent::__construct('htmlText');
	}

	public static function encoded(string $textContent): HtmlText
	{
		return new HtmlText(textContent: $textContent, isEncodedForRendering: true);
	}

	public static function unencoded(string $textContent): HtmlText
	{
		return new HtmlText(textContent: $textContent, isEncodedForRendering: false);
	}

	/**
	 * Generate the "html-code" for this Text-Element to be used for output
	 *
	 * @return string Generated html-code
	 */
	public function render(): string
	{
		return $this->isEncodedForRendering ? $this->textContent : HtmlDocument::htmlEncode(value: $this->textContent);
	}
}