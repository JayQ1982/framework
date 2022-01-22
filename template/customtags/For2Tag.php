<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use Exception;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class For2Tag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'for2';
	}

	public static function isElseCompatible(): bool
	{
		return true;
	}

	public static function isSelfClosing(): bool
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$tplEngine->checkRequiredAttributes($elementNode, ['var', 'as']);

		$dataKeyAttr = $elementNode->getAttribute('var')->getValue();
		$asVarAttr = $elementNode->getAttribute('as')->getValue();

		$keyVarAttr = $elementNode->getAttribute('key')->getValue();
		$counterAttr = $elementNode->getAttribute('counter')->getValue();

		$oddEvenAttr = $elementNode->getAttribute('odd-even')->getValue();
		$firstLastAttr = $elementNode->getAttribute('first-last')->getValue();

		$stepIncrement = $elementNode->getAttribute('step')->getValue();
		$grabCount = $elementNode->getAttribute('grab')->getValue();

		if ($stepIncrement === null && $grabCount !== null) {
			$stepIncrement = $grabCount;
		} else if ($stepIncrement === null) {
			$stepIncrement = 1;
		}

		if ($stepIncrement == 0) {
			throw new Exception('Use a step value other than 0. This will end up in an endless loop');
		}

		$for_i = '$for_i_' . $asVarAttr;
		$for_key = '$for_key_' . $asVarAttr;
		$for_val = '$for_val_' . $asVarAttr;
		$for_data = '$for_data_' . $asVarAttr;
		$for_data_count = '$for_data_count_' . $asVarAttr;

		$phpCode = '<?php
			' . $for_data . ' = $this->getDataFromSelector(\'' . $dataKeyAttr . '\');
			' . $for_data_count . ' = count(' . $for_data . ');
			' . $for_i . ' = 0;
			
			';

		if ($tplEngine->isFollowedBy($elementNode, ['else']) === true) {
			$phpCode .= 'if(' . $for_data_count . ' > 0):
			';
		}

		$phpCode .= 'for(' . $for_val . ' = current(' . $for_data . '), ' . $for_key . ' = key(' . $for_data . '); ' . $for_val . ';  ' . $for_val . ' = next(' . $for_data . '), ' . $for_key . ' = key(' . $for_data . '), ' . $for_i . ' += ' . $stepIncrement . '):
			' . (($counterAttr !== null) ? '$this->addData(\'' . $counterAttr . '\', ' . $for_i . ', true);' : null) . '	
			' . (($keyVarAttr !== null) ? '$this->addData(\'' . $keyVarAttr . '\', ' . $for_key . ', true);' : null);

		if ($grabCount === null || $grabCount == 1) {
			$phpCode .= '$this->addData(\'' . $asVarAttr . '\', ' . $for_val . ', true);';
		} else {
			$phpCode .= '$tmpGrabGroup = array(
				key(' . $for_data . ') => current(' . $for_data . ')
			);
			
			for($i = 2; $i <= ' . $grabCount . '; ++$i) {
				if(($tmpNextEntry = next(' . $for_data . ')) === false)
					break;
					
				$tmpGrabGroup[key(' . $for_data . ')] = $tmpNextEntry;
			}
			
			$this->addData(\'' . $asVarAttr . '\', $tmpGrabGroup, true);';
		}

		$phpCode .= (($oddEvenAttr !== null) ? '$this->addData(\'' . $oddEvenAttr . '\', (((' . $for_i . '/' . $stepIncrement . ')%2 === 0)?\'odd\':\'even\'), true);' : null) . '	
			' . (($firstLastAttr !== null) ? '$this->addData(\'' . $firstLastAttr . '\', ((' . $for_i . ' === 0)?\'first\':(((' . $for_i . '/' . $stepIncrement . ') === ' . $for_data_count . '-1)?\'last\':null)), true);' : null) . '	
		?>
		' . $elementNode->getInnerHtml() . '
		<?php endfor; ?>';

		$newNode = new TextNode();
		$newNode->content = $phpCode;

		$elementNode->parentNode->replaceNode($elementNode, $newNode);
	}
}