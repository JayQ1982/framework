<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

use framework\datacheck\Sanitizer;
use framework\html\HtmlTagAttribute;

class HtmlDoc
{
	private ?string $htmlContent;
	private ?int $contentPos = null;
	private DocumentNode $nodeTree;
	private ?HtmlNode $pendingNode;
	private ?string $namespace;
	private array $selfClosingTags;
	private int $currentLine = 1; // Start at line 1 not 0, we are no nerds ;-)
	private string $tagPattern;

	public function __construct(?string $htmlContent = null, ?string $namespace = null)
	{
		$this->htmlContent = $htmlContent;
		$this->nodeTree = new DocumentNode();
		$this->pendingNode = $this->nodeTree;

		$this->selfClosingTags = ['br', 'hr', 'img', 'input', 'link', 'meta'];

		$this->namespace = $namespace;

		if ($namespace !== null) {
			$this->tagPattern = '/(?:<!--.+?-->|<!\[CDATA\[.+?\]\]>|<(\/)?(' . $this->namespace . '\:\w+?)((?:\s+[^=]+="[^"]*")*?)?(\s*\/)?\s*>)/ims';
		} else {
			$this->tagPattern = '/(?:<!--.+?-->|<!\[CDATA\[.+?\]\]>|<(\/)?(\w+?)((?:\s+[^=]+="[^"]*")*?)?(\s*\/)?\s*>)/ims';
		}
	}

	public function parse()
	{
		if ($this->htmlContent === null) {
			return;
		}

		$this->contentPos = 0;

		$this->findNextNode();

		if ($this->contentPos !== strlen($this->htmlContent)) {
			$restNode = new TextNode();
			$restNode->content = substr($this->htmlContent, $this->contentPos);
			$restNode->parentNode = $this->nodeTree;

			$this->nodeTree->addChildNode($restNode);

			$this->currentLine += substr_count($restNode->content, "\n");
		}
	}

	private function findNextNode(): void
	{
		$oldPendingNode = $this->pendingNode;
		$oldContentPos = $this->contentPos;

		if (preg_match($this->tagPattern, $this->htmlContent, $res, PREG_OFFSET_CAPTURE, $this->contentPos) === 0) {
			return;
		}

		$this->currentLine += substr_count($res[0][0], "\n");
		$newPos = $res[0][1];

		if ($oldContentPos !== $newPos) {
			// Control-Node
			$lostText = substr($this->htmlContent, $oldContentPos, ($newPos - $oldContentPos));
			$this->currentLine += substr_count($lostText, "\n");

			$lostTextNode = new TextNode();
			$lostTextNode->content = $lostText;
			$lostTextNode->parentNode = $oldPendingNode;

			if ($oldPendingNode === null) {
				$this->nodeTree->addChildNode($lostTextNode);
			} else {
				$oldPendingNode->addChildNode($lostTextNode);
			}
		}

		$this->contentPos = $newPos + strlen($res[0][0]);

		if (str_starts_with($res[0][0], '<!--')) {
			// Comment-node
			$newNode = new CommentNode();
			$newNode->content = $res[0][0];
		} else if (stripos($res[0][0], '<![CDATA[') === 0) {
			// CDATA-node
			$newNode = new CDataSectionNode();
			$newNode->content = $res[0][0];
		} else if (stripos($res[0][0], '<!DOCTYPE') === 0) {
			$newNode = new DocumentTypeNode();
			$newNode->content = $res[0][0];
		} else {
			$newNode = new ElementNode();

			// </...> (close only)
			if (array_key_exists(1, $res) && $res[1][1] !== -1) {
				if ($this->pendingNode instanceof ElementNode) {
					$this->pendingNode->close();
				}
				$this->pendingNode = ($oldPendingNode !== null) ? $oldPendingNode->parentNode : null;

				if ($this->pendingNode === null) {
					$node = new TextNode();
					$node->content = '</' . $res[2][0] . '>';

					$this->nodeTree->addChildNode($node);
				}

				$this->findNextNode();

				return;
			}

			// Normal HTML-Tag-node
			$tagNParts = explode(':', $res[2][0]);

			if (count($tagNParts) > 1) {
				$newNode->namespace = $tagNParts[0];
				$newNode->tagName = $tagNParts[1];
			} else {
				$newNode->tagName = $tagNParts[0];
			}

			// <img ... /> (open and close)
			if ((array_key_exists(4, $res) && $res[4][0] === '/') || (array_key_exists(3, $res) && $res[3][0] === '/') || in_array($res[2][0], $this->selfClosingTags)) {
				$newNode->tagType = ElementNode::TAG_SELF_CLOSING;
			} else {
				// (open only)
				$this->pendingNode = $newNode;
				$newNode->tagType = ElementNode::TAG_OPEN;
			}

			// Attributes
			if (array_key_exists(3, $res) && $res[3][0] !== '/') {
				preg_match_all('/(.+?)="(.*?)"/', $res[3][0], $resAttrs, PREG_SET_ORDER);

				foreach ($resAttrs as $attr) {
					$newNode->addAttribute(new HtmlTagAttribute(Sanitizer::trimmedString($attr[1]), Sanitizer::trimmedString($attr[2]), true));
				}
			}
		}

		$newNode->line = $this->currentLine;
		$newNode->parentNode = $oldPendingNode;

		if ($oldPendingNode === null) {
			$this->nodeTree->addChildNode($newNode);
		} else {
			$oldPendingNode->addChildNode($newNode);
		}

		$this->findNextNode();
	}

	public function getNodesByNamespace(string $namespace, ?HtmlNode $entryNode = null): array
	{
		$nodes = [];

		$nodeList = ($entryNode === null) ? $this->nodeTree : $entryNode->childNodes;

		/** @var ElementNode $node */
		foreach ($nodeList as $node) {
			if ($node instanceof ElementNode === false) {
				continue;
			}

			if ($node->namespace === $namespace) {
				$nodes[] = $node;
			}

			if (!$node->hasChildren()) {
				continue;
			}

			$nodes = array_merge($nodes, $this->getNodesByNamespace($namespace, $node));
		}

		return $nodes;
	}

	public function getNodesByTagName(string $tagName, ?ElementNode $entryNode = null): array
	{
		$nodes = [];
		$nodeList = ($entryNode === null) ? $this->nodeTree : $entryNode->childNodes;

		/** @var ElementNode $node */
		foreach ($nodeList as $node) {
			if ($node instanceof ElementNode === false) {
				continue;
			}

			if ($node->tagName === $tagName) {
				$nodes[] = $node;
			}

			if (!$node->hasChildren()) {
				continue;
			}

			$nodes = array_merge($nodes, $this->getNodesByTagName($tagName, $node));
		}

		return $nodes;
	}

	public function getHtml(?ElementNode $entryNode = null): string
	{
		$html = '';

		if ($entryNode === null) {
			$nodeList = $this->nodeTree->childNodes;
		} else {
			if ($entryNode->hasChildren() === false) {
				return $html;
			}

			$nodeList = $entryNode->childNodes;
		}

		/** @var ElementNode $node */
		foreach ($nodeList as $node) {
			if (($node instanceof ElementNode) === false) {
				$html .= $node->content;
				continue;
			}

			$tagStr = (($node->namespace !== null) ? $node->namespace . ':' : '') . $node->tagName;

			$attrs = [];
			foreach ($node->getAttributes() as $htmlTagAttribute) {
				$attrs[] = $htmlTagAttribute->render();
			}
			$attrStr = (count($attrs) > 0) ? ' ' . implode(' ', $attrs) : '';

			$html .= '<' . $tagStr . $attrStr . $node->tagExtension . (($node->tagType === ElementNode::TAG_SELF_CLOSING) ? ' /' : '') . '>' . $node->content;

			if (($node->tagType === ElementNode::TAG_OPEN && $node->closed === true) || $node->tagType === ElementNode::TAG_CLOSE) {
				$html .= $this->getHtml($node) . '</' . $tagStr . '>';
			}
		}

		return $html;
	}

	public function replaceNode(HtmlNode $nodeSearch, HtmlNode $nodeReplace)
	{
		$parentSearchNode = $nodeSearch->getParentNode();
		$nodeList = ($parentSearchNode === null) ? $this->nodeTree : $nodeSearch->getParentNode()->childNodes;

		$countChildren = count($nodeList);

		for ($i = 0; $i < $countChildren; $i++) {
			if ($nodeList[$i] !== $nodeSearch) {
				continue;
			}

			$nodeList[$i] = $nodeReplace;
			break;
		}

		if ($parentSearchNode === null) {
			$this->nodeTree = $nodeList;
		} else {
			$parentSearchNode->setChildNodes($nodeList);
		}
	}

	public function getNodeTree(): DocumentNode
	{
		return $this->nodeTree;
	}

	public function addSelfClosingTag(string $tagName): void
	{
		$this->selfClosingTags[] = $tagName;
	}

	public function getCurrentLine(): int
	{
		return $this->currentLine;
	}
}