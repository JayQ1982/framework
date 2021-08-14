<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\CheckboxOptionsField;
use framework\form\component\field\RadioOptionsField;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

class LegendAndListRenderer extends FormRenderer
{
	private FormField $formField;

	public function __construct(FormField $formField)
	{
		if (
			!($formField instanceof CheckboxOptionsField)
			&& !($formField instanceof RadioOptionsField)
		) {
			throw new LogicException('The $formField must be either an instance of CheckboxOptionsField or RadioOptionsField');
		}
		$this->formField = $formField;
	}

	public function prepare(): void
	{
		$formField = $this->formField;

		$labelInfoText = $this->formField->getLabelInfoText();
		$labelText = $formField->getLabel();
		if (!is_null($labelInfoText)) {
			// Add a space to separate it from following labelInfo-Tag
			$labelText = new HtmlText(' ' . $labelText->render(), true);
		}

		$legendAttributes = [];
		if (!$formField->isRenderLabel()) {
			$legendAttributes[] = new HtmlTagAttribute('class', 'visuallyhidden', true);
		}

		$legendTag = new HtmlTag('legend', false, $legendAttributes);
		$legendTag->addText($labelText);

		if (!is_null($labelInfoText)) {
			$labelInfoTag = new HtmlTag('i', false, [
				new HtmlTagAttribute('class', 'legend-info', true),
			]);
			$labelInfoTag->addText($labelInfoText);
			$legendTag->addTag($labelInfoTag);
		}

		if ($formField->isRequired() && $formField->isRenderRequiredAbbr()) {
			$abbrTag = new HtmlTag('abbr', false, [
				new HtmlTagAttribute('title', 'Erforderliche Eingabe', true),
				new HtmlTagAttribute('class', 'required', true),
			]);
			$abbrTag->addText(new HtmlText('*', true));
			$legendTag->addTag($abbrTag);
		}

		$fieldsetTag = LegendAndListRenderer::createFieldsetTag($formField);
		$fieldsetTag->addTag($legendTag);

		if ($formField instanceof CheckboxOptionsField) {
			$checkboxOptionsRenderer = new CheckboxOptionsRenderer($formField);
			$checkboxOptionsRenderer->prepare();

			$fieldsetTag->addTag($checkboxOptionsRenderer->getHtmlTag());
			FormRenderer::addErrorsToParentHtmlTag($formField, $fieldsetTag);
		} else if ($formField instanceof RadioOptionsField) {
			$radioOptionsRenderer = new RadioOptionsRenderer($formField);
			$radioOptionsRenderer->prepare();

			$fieldsetTag->addTag($radioOptionsRenderer->getHtmlTag());
			FormRenderer::addErrorsToParentHtmlTag($formField, $fieldsetTag);
		}

		if (!is_null($formField->getFieldInfo())) {
			FormRenderer::addFieldInfoToParentHtmlTag($formField, $fieldsetTag);
		}

		$this->setHtmlTag($fieldsetTag);
	}

	public static function createFieldsetTag(FormField $formField): HtmlTag
	{
		$htmlTag = new HtmlTag(
			'fieldset',
			false,
			[new HtmlTagAttribute('class', 'legend-and-list', true)]
		);
		FormRenderer::addAriaAttributesToHtmlTag($formField, $htmlTag);

		return $htmlTag;
	}
}