<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

class CDataSectionNode extends HtmlNode
{
	public function __construct()
	{
		parent::__construct(nodeType: HtmlNode::CDATA_SECTION_NODE);
	}
}