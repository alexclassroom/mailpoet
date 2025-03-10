<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Html;
use MailPoet\WP\Functions as WPFunctions;

class HtmlTest extends \MailPoetUnitTest {
  private Html $html;

  private $block = [
    'type' => 'html',
    'name' => 'Html',
    'id' => 'html',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'nl2br' => '1',
      'text' => "line1\nline2",
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('wpKsesPost')->willReturnArgument(0);
    $wpMock->method('escAttr')->willReturnArgument(0);
    $this->html = new Html(
      $this->createMock(BlockRendererHelper::class),
      $wpMock
    );
  }

  public function testItShouldRenderCustomHtml() {
    $html = $this->html->render($this->block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph\" >line1<br />\nline2</div>");
  }

  public function testItShouldRenderCustomClass() {
    $block = $this->block;
    $block['params']['class_name'] = 'my_class';
    $html = $this->html->render($block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph my_class\" >line1<br />\nline2</div>");
  }

  public function testItShouldRenderCustomHtmlWithoutAutomaticBrs() {
    $block = $this->block;
    $block['params']['nl2br'] = '';
    $html = $this->html->render($block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph\" >line1\nline2</div>");
  }

  public function testItShouldNotEscapeHtml() {
    $block = $this->block;
    $block['params']['text'] = '<p class="my-p">Hello</p>';
    $html = $this->html->render($block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph\" ><p class=\"my-p\">Hello</p></div>");
  }
}
