<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\htmlparser;

class DocumentNode extends HtmlNode
{
	public function __construct(HtmlDoc $htmlDocument)
	{
		parent::__construct(HtmlNode::DOCUMENT_NODE, $htmlDocument);
	}
}