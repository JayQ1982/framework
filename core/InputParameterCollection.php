<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use LogicException;

class InputParameterCollection
{
	/** @var InputParameter[] */
	private array $allParameters = [];
	/** @var InputParameter[] */
	private array $requiredParameters = [];
	/** @var InputParameter[] */
	private array $optionalParameters = [];

	public function add(InputParameter $inputParameter): void
	{
		$name = $inputParameter->name;
		if (array_key_exists(key: $name, array: $this->allParameters)) {
			throw new LogicException(message: 'There is already an input parameter with this name: ' . $name);
		}
		$this->allParameters[$name] = $inputParameter;
		if ($inputParameter->isRequired) {
			$this->requiredParameters[$name] = $inputParameter;
		} else {
			$this->optionalParameters[$name] = $inputParameter;
		}
	}

	/**
	 * @return InputParameter[]
	 */
	public function listAllParameters(): array
	{
		return $this->allParameters;
	}

	/**
	 * @return InputParameter[]
	 */
	public function listRequiredParameters(): array
	{
		return $this->requiredParameters;
	}

	/**
	 * @return InputParameter[]
	 */
	public function listOptionalParameters(): array
	{
		return $this->optionalParameters;
	}

	public function hasParameter(string $name): bool
	{
		return array_key_exists(key: $name, array: $this->allParameters);
	}
}