<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

class CommentNode extends HtmlNode
{
	public function __construct()
	{
		parent::__construct(nodeType: HtmlNode::COMMENT_NODE);
	}
}