<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

use Exception;

abstract class HtmlNode
{
	const ELEMENT_NODE = 1;
	const ATTRIBUTE_NODE = 2;
	const TEXT_NODE = 3;
	const CDATA_SECTION_NODE = 4;
	const ENTITY_REFERENCE_NODE = 5;
	const ENTITY_NODE = 6;
	const PROCESSING_INSTRUCTION_NODE = 7;
	const COMMENT_NODE = 8;
	const DOCUMENT_NODE = 9;
	const DOCUMENT_TYPE_NODE = 10;
	const DOCUMENT_FRAGMENT_NODE = 11;
	const NOTATION_NODE = 12;
	public string $nodeType;
	public ?int $line = null;
	/** @var HtmlNode[] */
	public array $childNodes = [];
	public ?HtmlNode $parentNode = null;
	public string $content = '';
	private HtmlDoc $htmlDoc;

	public function __construct(string $nodeType, HtmlDoc $htmlDoc)
	{
		$this->htmlDoc = $htmlDoc;
		$this->nodeType = $nodeType;
	}

	/**
	 * @return HtmlNode[] All sub nodes
	 */
	public function getAllSubNodes(): array
	{
		$subNodes = [];

		foreach ($this->childNodes as $cn) {
			$subNodes[] = $cn;

			if (count($cn->childNodes) === 0) {
				continue;
			}

			$subNodes = array_merge($subNodes, $cn->getAllSubNodes());
		}

		return $subNodes;
	}

	/**
	 * Replaces a node with another one
	 *
	 * @param HtmlNode $nodeToReplace   The node to replace
	 * @param HtmlNode $replacementNode The replacement node for the original one
	 */
	public function replaceNode(HtmlNode $nodeToReplace, HtmlNode $replacementNode)
	{
		$pos = $this->findNodePosition($nodeToReplace);

		if ($pos === null) {
			throw new Exception('Nix gut... Node for replacement nicht gefunden.');
		}

		$this->childNodes[$pos] = $replacementNode;
	}

	/**
	 * Inserts a node before another one
	 *
	 * @param HtmlNode|HtmlNode[] $nodesToInsert A single HtmlNode object or an array of multiple HtmlNode objects
	 * @param HtmlNode            $beforeNode    HtmlNode object before the new nodes should be inserted
	 */
	public function insertBefore(HtmlNode|array $nodesToInsert, HtmlNode $beforeNode)
	{
		$pos = $this->findNodePosition($beforeNode);

		if (is_array($nodesToInsert) === false) {
			$nodesToInsert = [$nodesToInsert];
		}

		array_splice($this->childNodes, $pos, 0, $nodesToInsert);

		$this->childNodes = array_values($this->childNodes);
	}

	private function findNodePosition(HtmlNode $findNode): ?int
	{
		$countChildren = count($this->childNodes);

		for ($i = 0; $i < $countChildren; $i++) {
			if ($this->childNodes[$i] === $findNode) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * Removes a node from the child nodes
	 *
	 * @param HtmlNode $nodeToRemove
	 */
	public function removeNode(HtmlNode $nodeToRemove)
	{
		$countChildren = count($this->childNodes);

		for ($i = 0; $i < $countChildren; $i++) {
			if ($this->childNodes[$i] !== $nodeToRemove) {
				continue;
			}

			unset($this->childNodes[$i]);
			$this->childNodes = array_values($this->childNodes);

			return;
		}
	}

	/**
	 * Adds a child node to the list
	 *
	 * @param HtmlNode $childNode
	 */
	public function addChildNode(HtmlNode $childNode)
	{
		$this->childNodes[] = $childNode;
	}

	public function getNextSibling(): ?ElementNode
	{
		/** @var ElementNode[] $cNodes */
		$cNodes = $this->parentNode->childNodes;
		$cNodesCount = count($cNodes);

		$nextPos = $this->parentNode->findNodePosition($this) + 1;

		for ($i = $nextPos; $i < $cNodesCount; $i++) {
			if ($cNodes[$i] instanceof $this) {
				return $cNodes[$i];
			}
		}

		return null;
	}

	/**
	 * Returns the previous sibling
	 *
	 * @return HtmlNode|null The previous sibling or NULL of no previous sibling exists
	 */
	public function getPrevSibling(): ?HtmlNode
	{
		$cNodes = $this->parentNode->childNodes;
		$prevPos = $this->parentNode->findNodePosition($this) - 1;

		for ($i = $prevPos; $i > 0; --$i) {
			if ($cNodes[$i] instanceof $this === false) {
				continue;
			}

			return $cNodes[$i];
		}

		return null;
	}

	/**
	 * @param string $filter
	 *
	 * @return HtmlNode[]
	 */
	public function findChildNodes(string $filter): array
	{
		$childNotes = [];

		/** @var ElementNode $cn */
		foreach ($this->childNodes as $cn) {
			if ($cn instanceof ElementNode === false || ($cn->namespace !== null ? $cn->namespace . ':' : null) . $cn->tagName !== $filter) {
				continue;
			}

			$childNotes[] = $cn;
		}

		return $childNotes;
	}

	/**
	 * Sets a child node list for this node
	 *
	 * @param HtmlNode[] $childNodes
	 */
	public function setChildNodes(array $childNodes)
	{
		$this->childNodes = $childNodes;
	}

	/**
	 * @return HtmlNode|null
	 */
	public function getParentNode(): ?HtmlNode
	{
		return $this->parentNode;
	}

	/**
	 * @param HtmlNode|null $parentNode
	 */
	public function setParentNode(?HtmlNode $parentNode)
	{
		$this->parentNode = $parentNode;
	}

	/**
	 * Checks if the node has child nodes or not
	 *
	 * @return boolean
	 */
	public function hasChildren(): bool
	{
		if (count($this->childNodes) > 0) {
			return true;
		}

		return false;
	}
}