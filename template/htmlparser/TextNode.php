<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\htmlparser;

class TextNode extends HtmlNode
{
	public function __construct()
	{
		parent::__construct(nodeType: HtmlNode::TEXT_NODE);
	}
}