<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Radio;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class RadioTest extends \MailPoetUnitTest {
  /** @var Radio */
  private $radio;

  /** @var MockObject & BlockRendererHelper */
  private $baseMock;

  /** @var MockObject & WPFunctions */
  private $rendererHelperMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'radio',
    'name' => 'Radio',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Input label',
      'required' => '',
      'hide_label' => '',
      'value' => 'Radio 2',
      'values' => [[
        'value' => 'Radio 1',
      ], [
        'value' => 'Radio 2',
      ]],

    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->rendererHelperMock = $this->createMock(WPFunctions::class);
    $this->rendererHelperMock->method('escAttr')->will($this->returnArgument(0));
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $this->baseMock = $this->createMock(BlockRendererHelper::class);
    $this->radio = new Radio($this->baseMock, $this->wrapperMock, $this->rendererHelperMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderRadioInputs() {
    $this->baseMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->once())->method('getInputValidation')->willReturn(' validation="1" ');
    $this->baseMock->expects($this->once())->method('getFieldValue')->willReturn('Radio 2');

    $html = $this->radio->render($this->block, []);

    $radio1 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_radio_label']", 0);
    $radio2 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_radio_label']", 1);
    verify($radio1->textContent)->equals(' Radio 1');
    verify($radio2->textContent)->equals(' Radio 2');

    $radio1Input = $this->htmlParser->getChildElement($radio1, 'input');
    $radio2Input = $this->htmlParser->getChildElement($radio2, 'input');
    verify($this->htmlParser->getAttribute($radio1Input, 'value')->value)->equals('Radio 1');
    verify($this->htmlParser->getAttribute($radio2Input, 'value')->value)->equals('Radio 2');

    verify($this->htmlParser->getAttribute($radio2Input, 'checked')->value)->equals('checked');
  }

  public function testItShouldRenderErrorContainer() {
    $this->baseMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->once())->method('getFieldValue')->willReturn('Radio 2');
    $this->baseMock->expects($this->once())->method('renderErrorsContainer')->willReturn('<span class="mailpoet_error_1_31"></span>');

    $html = $this->radio->render($this->block, []);

    $errorContainer = $this->htmlParser->getElementByXpath($html, "//span[@class='mailpoet_error_1_31']");
    verify($errorContainer)->notEmpty();
    verify($errorContainer->nodeName)->equals('span');
  }
}
