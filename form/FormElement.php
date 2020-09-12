<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

abstract class FormElement
{
	private string $name; // Name of this element, which must not be unique

	/**
	 * Protected constructor to make sure, we overwrite it in child classes.
	 *
	 * @param string $name : Name to be set by the constructor
	 */
	protected function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string : Name of this element
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Abstract render-method to make sure, that every child does implement it
	 *
	 * @return string : Content which can be used for output
	 */
	abstract protected function render(): string;
}
/* EOF */