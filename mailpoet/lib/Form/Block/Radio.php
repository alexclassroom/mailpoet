<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Block;

use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class Radio {

  /** @var BlockRendererHelper */
  private $rendererHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var BlockWrapperRenderer */
  private $wrapper;

  public function __construct(
    BlockRendererHelper $rendererHelper,
    BlockWrapperRenderer $wrapper,
    WPFunctions $wp
  ) {
    $this->rendererHelper = $rendererHelper;
    $this->wrapper = $wrapper;
    $this->wp = $wp;
  }

  public function render(array $block, array $formSettings, ?int $formId = null): string {
    $html = '';

    $fieldName = 'data[' . $this->rendererHelper->getFieldName($block) . ']';
    $fieldValidation = $this->rendererHelper->getInputValidation($block, [], $formId);

    $html .= '<fieldset>';
    $html .= $this->rendererHelper->renderLegend($block, $formSettings);

    $options = (!empty($block['params']['values'])
      ? $block['params']['values']
      : []
    );

    $selectedValue = $this->rendererHelper->getFieldValue($block);

    foreach ($options as $option) {
      $id = $this->wp->wpUniqueId('mailpoet_radio_');
      $html .= '<label class="mailpoet_radio_label" for="' . $id . '" '
        . $this->rendererHelper->renderFontStyle($formSettings)
        . '>';

      $html .= '<input type="radio" class="mailpoet_radio" ';
      $html .= 'id="' . $id . '" ';
      $html .= 'name="' . $fieldName . '" ';

      if (is_array($option['value'])) {
        $value = key($option['value']);
        $label = reset($option['value']);
      } else {
        $value = $option['value'];
        $label = $option['value'];
      }

      $html .= 'value="' . $this->wp->escAttr($value) . '" ';

      $html .= (
        (
          $selectedValue === ''
          && isset($option['is_checked'])
          && $option['is_checked']
        ) || ($selectedValue === $value)
      ) ? 'checked="checked"' : '';

      $html .= $fieldValidation;
      $html .= ' /> ' . $this->wp->escAttr($label);
      $html .= '</label>';
    }

    $html .= '</fieldset>';

    $html .= $this->rendererHelper->renderErrorsContainer($block, $formId);

    return $this->wrapper->render($block, $html);
  }
}
