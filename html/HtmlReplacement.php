<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use stdClass;

readonly class HtmlReplacement
{
	private function __construct(
		public null|HtmlText|bool|stdClass|HtmlTextCollection|HtmlDataObjectCollection|int $content
	) {

	}

	public static function htmlText(?HtmlText $htmlText): HtmlReplacement
	{
		return new HtmlReplacement(content: $htmlText);
	}

	public static function encodedText(?string $content): HtmlReplacement
	{
		return new HtmlReplacement(content: HtmlText::encoded(textContent: $content));
	}

	public static function unencodedText(?string $content): HtmlReplacement
	{
		return new HtmlReplacement(content: HtmlText::unencoded(textContent: $content));
	}

	public static function bool(?bool $bool): HtmlReplacement
	{
		return new HtmlReplacement(content: $bool);
	}

	public static function int(?int $int): HtmlReplacement
	{
		return new HtmlReplacement(content: $int);
	}

	public static function object(?stdClass $object): HtmlReplacement
	{
		return new HtmlReplacement(content: $object);
	}

	public static function textCollection(?HtmlTextCollection $collection): HtmlReplacement
	{
		return new HtmlReplacement(content: $collection);
	}

	public static function htmlDataObjectCollection(?HtmlDataObjectCollection $collection): HtmlReplacement
	{
		return new HtmlReplacement(content: $collection);
	}

	public function getDataForRenderer(): null|string|bool|stdClass|array
	{
		if ($this->content instanceof HtmlText) {
			return $this->content->render();
		}
		if ($this->content instanceof HtmlTextCollection) {
			$array = [];
			foreach ($this->content->getItems() as $htmlText) {
				$array[] = $htmlText->render();
			}

			return $array;
		}
		if ($this->content instanceof HtmlDataObjectCollection) {
			$array = [];
			foreach ($this->content->getItems() as $htmlDataObject) {
				$array[] = $htmlDataObject->getData();
			}

			return $array;
		}

		return $this->content;
	}
}