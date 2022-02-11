<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use stdClass;

class HtmlDataObject
{
	private stdClass $data;

	public function __construct()
	{
		$this->data = new stdClass();
	}

	public function addTextElement(string $propertyName, ?string $content, bool $isEncodedForRendering)
	{
		if (is_null($content)) {
			$this->data->{$propertyName} = null;

			return;
		}

		$this->data->{$propertyName} = $isEncodedForRendering ? $content : HtmlEncoder::encode(value: $content);
	}

	public function addDataObject(string $propertyName, ?HtmlDataObject $htmlDataObject)
	{
		$this->data->{$propertyName} = is_null($htmlDataObject) ? null : $htmlDataObject->getData();
	}

	/**
	 * @param string                $propertyName
	 * @param HtmlDataObject[]|null $htmlDataObjectsArray
	 */
	public function addHtmlDataObjectsArray(string $propertyName, ?array $htmlDataObjectsArray): void
	{
		if (is_null($htmlDataObjectsArray)) {
			$this->data->{$propertyName} = null;

			return;
		}

		$array = [];
		foreach ($htmlDataObjectsArray as $htmlDataObject) {
			$array[] = $htmlDataObject->getData();
		}

		$this->data->{$propertyName} = $array;
	}

	public function addBooleanValue(string $propertyName, bool $booleanValue): void
	{
		$this->data->{$propertyName} = $booleanValue;
	}

	public function addNullValue(string $propertyName): void
	{
		$this->data->{$propertyName} = null;
	}

	public function getData(): stdClass
	{
		return $this->data;
	}
}