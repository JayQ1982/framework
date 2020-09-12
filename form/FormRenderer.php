<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

use LogicException;

abstract class FormRenderer
{
	private ?FormTag $formTag = null; // The base Tag-Element for this renderer, which may contain child-elements

	/** The descending classes must use this method to prepare the base Tag-Element */
	abstract public function prepare(): void;

	/**
	 * Method to set the base Tag-Element. It's not allowed to overwrite it, if already set!
	 *
	 * @param FormTag $formTag : The Tag-Element to be set
	 */
	protected function setFormTag(FormTag $formTag): void
	{
		if (!is_null($this->formTag)) {
			throw new LogicException('You cannot overwrite an already defined Tag-Element.');
		}
		$this->formTag = $formTag;
	}

	/**
	 * Get the current base Tag-Element for this renderer
	 *
	 * @return FormTag|null : The current base Tag-Element or null, if not set
	 */
	public function getFormTag(): ?FormTag
	{
		return $this->formTag;
	}

	/**
	 * Get the html-code which can be used for output
	 *
	 * @return string : The html code (rendered by the base Tag-Element and it's children) - Empty string if base Tag-Element is not set
	 */
	public function getHTML(): string
	{
		return is_null($this->formTag) ? '' : $this->formTag->render();
	}

	static function htmlEncode($value)
	{
		if (is_null($value)) {
			return ''; // It's for display, not for value-processing
		}

		if (is_scalar($value)) {
			return htmlspecialchars($value, ENT_QUOTES);
		}

		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = self::htmlEncode($val);
			}
		}

		return $value;
	}
}
/* EOF */