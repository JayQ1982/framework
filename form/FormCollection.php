<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

use LogicException;
use framework\form\renderer\DefaultCollectionRenderer;

abstract class FormCollection extends FormComponent
{
	/** @var FormComponent[] : Array with all child components, which can also be collections */
	private array $childComponents = [];

	final public function addChildComponent(FormComponent $formComponent): void
	{
		$childComponentName = $formComponent->getName();
		if (isset($this->childComponents[$childComponentName])) {
			throw new LogicException('There is already an existing child component with the same name: ' . $childComponentName);
		}
		$formComponent->setParentFormComponent($this);

		$this->childComponents[$childComponentName] = $formComponent;
	}

	/**
	 * Returns all child components as array
	 *
	 * @return FormComponent[]
	 */
	public function getChildComponents(): array
	{
		return $this->childComponents;
	}

	public function hasChildComponent(string $childComponentName): bool
	{
		return array_key_exists($childComponentName, $this->childComponents);
	}

	public function getChildComponent(string $childComponentName): FormComponent
	{
		if (!$this->hasChildComponent($childComponentName)) {
			throw new LogicException('FormCollection ' . $this->getName() . ' does not contain requested ChildComponent ' . $childComponentName);
		}

		return $this->childComponents[$childComponentName];
	}

	public function removeChildComponent(string $childComponentName): void
	{
		if (!$this->hasChildComponent($childComponentName)) {
			throw new LogicException('FormCollection ' . $this->getName() . ' does not contain requested ChildComponent ' . $childComponentName);
		}
		unset($this->childComponents[$childComponentName]);
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new DefaultCollectionRenderer($this);
	}
}
/* EOF */