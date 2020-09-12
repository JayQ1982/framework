<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use Exception;
use framework\common\StringUtils;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class IfTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'if';
	}

	public static function isElseCompatible()
	{
		return true;
	}

	public static function isSelfClosing()
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $tagNode)
	{
		$condAttr = $tagNode->getAttribute('cond')->value;

		$phpCode = '<?php ';

		if ($condAttr === null) {
			$tplEngine->checkRequiredAttributes($tagNode, ['compare', 'operator', 'against']);

			$compareAttr = $tagNode->getAttribute('compare')->value;
			$operatorAttr = $tagNode->getAttribute('operator')->value;
			$againstAttr = $tagNode->getAttribute('against')->value;

			if (strlen($againstAttr) === 0) {
				$againstAttr = "''";
			} else if (is_int($againstAttr) === true) {
				$againstAttr = intval($againstAttr);
			} else if (is_float($againstAttr) === true) {
				$againstAttr = floatval($againstAttr);
			} else if (is_string($againstAttr) === true) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if (strtolower($againstAttr) === 'null') {
				} else /** @noinspection PhpStatementHasEmptyBodyInspection */ if (strtolower($againstAttr) === 'true' || strtolower($againstAttr) === 'false') {
				} else if (StringUtils::startsWith($againstAttr, '{') && StringUtils::endsWith($againstAttr, '}')) {
					$arr = explode(',', substr($againstAttr, 1, -1));
					$againstAttr = [];

					foreach ($arr as $a) {
						$againstAttr[] = trim($a);
					}
				} else {
					$againstAttr = "'" . $againstAttr . "'";
				}
			}

			$operatorStr = '==';

			switch (strtolower($operatorAttr)) {
				case 'gt':
					$operatorStr = '>';
					break;
				case 'ge':
					$operatorStr = '>=';
					break;
				case 'lt':
					$operatorStr = '<';
					break;
				case 'le':
					$operatorStr = '<=';
					break;
				case 'eq':
					$operatorStr = '==';
					break;
				case 'ne':
					$operatorStr = '!=';
					break;
			}

			$phpCode .= 'if($this->getDataFromSelector(\'' . $compareAttr . '\') ' . $operatorStr . ' ' . $againstAttr . ') { ?>';
		} else {
			$phpCode .= 'if(' . preg_replace_callback('/\${(.*?)}/i', function($m) {
					if (strlen($m[1]) === 0) {
						throw new Exception('Empty template data reference');
					}

					return '$this->getDataFromSelector(\'' . $m[1] . '\')';
				}, $condAttr) . ') { ?>';
		}

		$phpCode .= $tagNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($tagNode, ['else', 'elseif']) === false) {
			$phpCode .= '<?php } ?>';
		}

		$textNode = new TextNode($tplEngine->getDomReader());
		$textNode->content = $phpCode;

		$tagNode->parentNode->replaceNode($tagNode, $textNode);
		$tagNode->parentNode->removeNode($tagNode);
	}
}
/* EOF */