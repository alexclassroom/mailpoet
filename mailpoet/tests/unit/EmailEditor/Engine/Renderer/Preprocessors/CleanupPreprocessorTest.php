<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\CleanupPreprocessor;

class CleanupPreprocessorTest extends \MailPoetUnitTest {

  private const PARAGRAPH_BLOCK = [
    'blockName' => 'core/paragraph',
    'attrs' => [],
    'innerHTML' => 'Paragraph content',
  ];

  private const COLUMNS_BLOCK = [
    'blockName' => 'core/columns',
    'attrs' => [],
    'innerBlocks' => [[
      'blockName' => 'core/column',
      'attrs' => [],
      'innerBlocks' => [],
    ]],
  ];

  /** @var CleanupPreprocessor */
  private $preprocessor;

  /** @var array{contentSize: string} */
  private array $layout;

  /** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
  private array $styles;

  public function _before() {
    parent::_before();
    $this->preprocessor = new CleanupPreprocessor();
    $this->layout = ['contentSize' => '660px'];
    $this->styles = ['spacing' => ['padding' => ['left' => '10px', 'right' => '10px', 'top' => '10px', 'bottom' => '10px'], 'blockGap' => '10px']];
  }

  public function testItRemovesUnwantedBlocks(): void {
    $blocks = [
      self::COLUMNS_BLOCK,
      ['blockName' => null, 'attrs' => [], 'innerHTML' => "\r\n"],
      self::PARAGRAPH_BLOCK,
    ];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    verify($result)->arrayCount(2);
    verify($result[0])->equals(self::COLUMNS_BLOCK);
    verify($result[1])->equals(self::PARAGRAPH_BLOCK);
  }

  public function testItPreservesAllRelevantBlocks(): void {
    $blocks = [
      self::COLUMNS_BLOCK,
      self::PARAGRAPH_BLOCK,
      self::COLUMNS_BLOCK,
    ];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    verify($result)->arrayCount(3);
    verify($result[0])->equals(self::COLUMNS_BLOCK);
    verify($result[1])->equals(self::PARAGRAPH_BLOCK);
    verify($result[2])->equals(self::COLUMNS_BLOCK);
  }
}
