<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\customtags;

use Exception;
use framework\common\StringUtils;
use framework\datacheck\Sanitizer;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class IfTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'if';
	}

	public static function isElseCompatible(): bool
	{
		return true;
	}

	public static function isSelfClosing(): bool
	{
		return false;
	}

	/**
	 * @param TemplateEngine $tplEngine
	 * @param ElementNode    $elementNode
	 */
	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$condAttr = $elementNode->getAttribute('cond')->getValue();

		$phpCode = '<?php ';

		if ($condAttr === null) {
			$tplEngine->checkRequiredAttributes($elementNode, ['compare', 'operator', 'against']);

			$compareAttr = $elementNode->getAttribute('compare')->getValue();
			$operatorAttr = $elementNode->getAttribute('operator')->getValue();
			$againstAttr = $elementNode->getAttribute('against')->getValue();

			if (strlen($againstAttr) === 0) {
				$againstAttr = "''";
			} else if (is_string($againstAttr)) {
				if (!in_array(strtolower($againstAttr), ['null', 'true', 'false'])) {
					if (StringUtils::startsWith($againstAttr, '{') && StringUtils::endsWith($againstAttr, '}')) {
						$arr = explode(',', substr($againstAttr, 1, -1));
						$againstAttr = [];

						foreach ($arr as $a) {
							$againstAttr[] = Sanitizer::trimmedString($a);
						}
					} else {
						$againstAttr = "'" . $againstAttr . "'";
					}
				}
			}

			$operatorStr = match (strtolower($operatorAttr)) {
				'gt' => '>',
				'ge' => '>=',
				'lt' => '<',
				'le' => '<=',
				'ne' => '!=',
				default => '=='
			};

			$phpCode .= 'if($this->getDataFromSelector(\'' . $compareAttr . '\') ' . $operatorStr . ' ' . $againstAttr . ') { ?>';
		} else {
			$phpCode .= 'if(' . preg_replace_callback(
					pattern: '/\${(.*?)}/i',
					callback: function($m) {
						if (strlen($m[1]) === 0) {
							throw new Exception('Empty template data reference');
						}

						return '$this->getDataFromSelector(\'' . $m[1] . '\')';
					},
					subject: $condAttr
				) . ') { ?>';
		}

		$phpCode .= $elementNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($elementNode, ['else', 'elseif']) === false) {
			$phpCode .= '<?php } ?>';
		}

		$textNode = new TextNode();
		$textNode->content = $phpCode;

		$elementNode->parentNode->replaceNode($elementNode, $textNode);
		$elementNode->parentNode->removeNode($elementNode);
	}
}