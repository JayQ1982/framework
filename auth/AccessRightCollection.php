<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

class AccessRightCollection
{
	public const ACCESS_DO_PASSWORD_LOGIN = 'doPasswordLogin';

	/** @var string[] */
	private array $accessRights = [];

	protected function __construct() { }

	public static function createEmpty(): AccessRightCollection
	{
		return new AccessRightCollection();
	}

	public static function createFromStringArray(array $input): AccessRightCollection
	{
		$accessRightCollection = new AccessRightCollection();
		foreach ($input as $value) {
			$accessRightCollection->add(accessRight: $value);
		}

		return $accessRightCollection;
	}

	public function add(string $accessRight): void
	{
		$this->accessRights[] = $accessRight;
	}

	public function hasAccessRight(string $accessRight): bool
	{
		return in_array(needle: $accessRight, haystack: $this->accessRights);
	}

	public function hasOneOfAccessRights(AccessRightCollection $accessRightCollection): bool
	{
		foreach ($accessRightCollection->listAccessRights() as $accessRight) {
			if ($this->hasAccessRight(accessRight: $accessRight)) {
				return true;
			}
		}

		return false;
	}

	public function isEmpty(): bool
	{
		return (count(value: $this->accessRights) === 0);
	}

	/**
	 * @return string[]
	 */
	public function listAccessRights(): array
	{
		return $this->accessRights;
	}
}