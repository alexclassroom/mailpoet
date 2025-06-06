<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewsletterHtmlSanitizer;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Button;
use MailPoet\Newsletter\Renderer\Blocks\Divider;
use MailPoet\Newsletter\Renderer\Blocks\Footer;
use MailPoet\Newsletter\Renderer\Blocks\Header;
use MailPoet\Newsletter\Renderer\Blocks\Image;
use MailPoet\Newsletter\Renderer\Blocks\Social;
use MailPoet\Newsletter\Renderer\Blocks\Spacer;
use MailPoet\Newsletter\Renderer\Blocks\Text;
use MailPoet\Newsletter\Renderer\BodyRenderer;
use MailPoet\Newsletter\Renderer\Columns\Renderer as ColumnRenderer;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group woo
 */
class RendererTest extends \MailPoetTest {
  public $dOMParser;
  public $columnRenderer;

  /** @var Renderer */
  public $renderer;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var CapabilitiesManager & MockObject */
  private $capabilitiesManager;

  const COLUMN_BASE_WIDTH = 660;

  public function _before() {
    parent::_before();
    $this->newsletter = new NewsletterEntity();
    $body = json_decode(
      (string)file_get_contents(dirname(__FILE__) . '/RendererTestData.json'),
      true
    );
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $this->newsletter->setSubject('Some subject');
    $this->newsletter->setPreheader('Some preheader');
    $this->newsletter->setType('standard');
    $this->newsletter->setStatus('active');
    $this->capabilitiesManager = $this->createMock(CapabilitiesManager::class);
    $this->capabilitiesManager->method('getCapability')->willReturn(new Capability('mailpoetLogoInEmails', 'boolean', false));
    $this->renderer = new Renderer(
      $this->diContainer->get(BodyRenderer::class),
      $this->diContainer->get(\MailPoet\EmailEditor\Engine\Renderer\Renderer::class),
      $this->diContainer->get(Preprocessor::class),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(LoggerFactory::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(SendingQueuesRepository::class),
      $this->capabilitiesManager
    );
    $this->columnRenderer = new ColumnRenderer();
    $this->dOMParser = new pQuery();
  }

  public function testItRendersCompleteNewsletter() {
    $template = $this->renderer->renderAsPreview($this->newsletter);// do not render logo
    verify(isset($template['html']))->true();
    verify(isset($template['text']))->true();
    $DOM = $this->dOMParser->parseStr($template['html']);
    // we expect to have 7 columns:
    //  1x column including header
    //  2x column
    //  3x column
    //  1x footer
    verify(count($DOM('.mailpoet_cols-one')))->equals(2);
    verify(count($DOM('.mailpoet_cols-two')))->equals(2);
    verify(count($DOM('.mailpoet_cols-three')))->equals(3);

    // nested vertical container should be rendered
    verify(count($DOM('.nested-vertical-container')))->equals(1);

    // Verify it doesn't replace <!--[if !mso]><!-- --> with <!--[if !mso]><![endif]-->
    // This comment is needed for outlook and should not be replaced. There was an issue in pQuery that was replacing it.
    // The email content contain button and the button contains this comment.
    verify($template['html'])->stringContainsString('mailpoet_table_button');
    verify($template['html'])->stringContainsString('<!--[if !mso]><!-- -->');
  }

  public function testItRendersOneColumn() {
    $columnContent = [
      'one',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-one > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    verify($renderedColumnContent)->equals($columnContent);
    verify((string)$DOM)->stringContainsString(' bgcolor="#999999"');
  }

  public function testItRendersTwoColumns() {
    $columnContent = [
      'one',
      'two',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[], []],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-two > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    verify($renderedColumnContent)->equals($columnContent);
    verify((string)$DOM)->stringContainsString(' bgcolor="#999999"');
  }

  public function testItRendersThreeColumns() {
    $columnContent = [
      'one',
      'two',
      'three',
    ];
    $columnStyles = [
      'block' => [
        'backgroundColor' => "#999999",
      ],
    ];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[], [], []],
        ],
        $columnContent
      )
    );
    $renderedColumnContent = [];
    foreach ($DOM('table.mailpoet_cols-three > tbody') as $column) {
      $renderedColumnContent[] = trim($column->text());
    };
    verify($renderedColumnContent)->equals($columnContent);
    verify((string)$DOM)->stringContainsString(' bgcolor="#999999"');
  }

  public function testItRendersScaledColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'scale', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    verify($columnCss)->stringContainsString('background: #999999 url(https://example.com/image.jpg) no-repeat center/cover;');
    verify($columnCss)->stringContainsString('background-color: #999999;');
    verify($columnCss)->stringContainsString('background-image: url(https://example.com/image.jpg);');
    verify($columnCss)->stringContainsString('background-repeat: no-repeat;');
    verify($columnCss)->stringContainsString('background-position: center;');
    verify($columnCss)->stringContainsString('background-size: cover;');
  }

  public function testItRendersFitColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'fit', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    verify($columnCss)->stringContainsString('background: #999999 url(https://example.com/image.jpg) no-repeat center/contain;');
    verify($columnCss)->stringContainsString('background-color: #999999;');
    verify($columnCss)->stringContainsString('background-image: url(https://example.com/image.jpg);');
    verify($columnCss)->stringContainsString('background-repeat: no-repeat;');
    verify($columnCss)->stringContainsString('background-position: center;');
    verify($columnCss)->stringContainsString('background-size: contain;');
  }

  public function testItRendersTiledColumnBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => "#999999"]];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'tile', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    verify($columnCss)->stringContainsString('background: #999999 url(https://example.com/image.jpg) repeat center/contain;');
    verify($columnCss)->stringContainsString('background-color: #999999;');
    verify($columnCss)->stringContainsString('background-image: url(https://example.com/image.jpg);');
    verify($columnCss)->stringContainsString('background-repeat: repeat;');
    verify($columnCss)->stringContainsString('background-position: center;');
    verify($columnCss)->stringContainsString('background-size: contain;');
  }

  public function testItRendersFallbackColumnBackgroundColorForBackgroundImage() {
    $columnContent = ['one'];
    $columnStyles = ['block' => ['backgroundColor' => 'transparent']];
    $columnImage = ['src' => 'https://example.com/image.jpg', 'display' => 'tile', 'width' => '1000px', 'height' => '500px'];
    $DOM = $this->dOMParser->parseStr(
      $this->columnRenderer->render(
        [
          'styles' => $columnStyles,
          'blocks' => [[]],
          'image' => $columnImage,
        ],
        $columnContent
      )
    );
    $columnCss = $DOM('td.mailpoet_content')[0]->attr('style');
    verify($columnCss)->stringContainsString('background: #ffffff url(https://example.com/image.jpg) repeat center/contain;');
    verify($columnCss)->stringContainsString('background-color: #ffffff;');
  }

  public function testItRendersHeader() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][0];
    $headerRenderer = new Header(
      $this->diContainer->get(NewsletterHtmlSanitizer::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $DOM = $this->dOMParser->parseStr($headerRenderer->render($template));
    // element should be properly nested, and styles should be applied
    verify($DOM('tr > td.mailpoet_header', 0)->html())->notEmpty();
    verify($DOM('tr > td > a', 0)->html())->notEmpty();
    verify($DOM('a', 0)->attr('style'))->notEmpty();
    verify($DOM('td', 0)->attr('style'))->notEmpty();
  }

  public function testItRendersImage() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, self::COLUMN_BASE_WIDTH));
    // element should be properly nested, it's width set and style applied
    verify($DOM('tr > td > img', 0)->attr('width'))->equals(620);
  }

  public function testItRendersAlignedImage() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    // default alignment (center)
    unset($template['styles']['block']['textAlign']);
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    verify($DOM('tr > td', 0)->attr('align'))->equals('center');
    $template['styles']['block']['textAlign'] = 'center';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    verify($DOM('tr > td', 0)->attr('align'))->equals('center');
    $template['styles']['block']['textAlign'] = 'something odd';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    verify($DOM('tr > td', 0)->attr('align'))->equals('center');
    // left alignment
    $template['styles']['block']['textAlign'] = 'left';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    verify($DOM('tr > td', 0)->attr('align'))->equals('left');
    // right alignment
    $template['styles']['block']['textAlign'] = 'right';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, $columnCount = 1));
    verify($DOM('tr > td', 0)->attr('align'))->equals('right');
  }

  public function testItDoesNotRenderImageWithoutSrc() {
    $image = [
      'src' => '',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    verify($renderedImage)->equals('');
  }

  public function testItForcesAbsoluteSrcForImages() {
    $image = [
      'src' => '/relative-path',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    $siteUrl = get_option('siteurl');
    verify($renderedImage)->stringContainsString('src="' . $siteUrl . '/relative-path"');

    $image = [
      'src' => '//path-without-protocol',
      'width' => '100',
      'height' => '200',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    verify($renderedImage)->stringContainsString('src="//path-without-protocol"');
  }

  public function testItRendersImageWithLink() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][1];
    $template['link'] = 'http://example.com';
    $DOM = $this->dOMParser->parseStr((new Image)->render($template, self::COLUMN_BASE_WIDTH));
    // element should be wrapped in <a> tag
    verify($DOM('tr > td > a', 0)->html())->stringContainsString('<img');
    verify($DOM('tr > td > a', 0)->attr('href'))->equals($template['link']);
  }

  public function testItAdjustsImageDimensions() {
    // image gets scaled down when image width > column width
    $image = [
      'width' => 800,
      'height' => 600,
      'fullWidth' => true,
    ];
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    verify($newImageDimensions['width'])->equals(660);
    verify($newImageDimensions['height'])->equals(495);
    // nothing happens when image width = column width
    $image['width'] = 661;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    verify($newImageDimensions['width'])->equals(660);
    // nothing happens when image width < column width
    $image['width'] = 659;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    verify($newImageDimensions['width'])->equals(659);
    // image is reduced by 40px when it's width > padded column width
    $image['width'] = 621;
    $image['fullWidth'] = false;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    verify($newImageDimensions['width'])->equals(620);
    // nothing happens when image with < padded column width
    $image['width'] = 619;
    $newImageDimensions = (new Image)->adjustImageDimensions($image, self::COLUMN_BASE_WIDTH);
    verify($newImageDimensions['width'])->equals(619);
  }

  public function testItRendersImageWithAutoDimensions() {
    $image = [
      'width' => 'auto',
      'height' => 'auto',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    verify($renderedImage)->stringContainsString('width="auto"');
  }

  public function testItAdjustImageDimensionsWithPx() {
    $image = [
      'width' => '1000px',
      'height' => '1000px',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    verify($renderedImage)->stringContainsString('width="620"');
  }

  public function testItAdjustImageDimensionsWithoutPx() {
    $image = [
      'width' => '1000',
      'height' => '1000',
      'src' => 'https://example.com/image.jpg',
      'link' => '',
      'fullWidth' => false,
      'alt' => 'some test alt text',
    ];
    $renderedImage = (new Image)->render($image, self::COLUMN_BASE_WIDTH);
    verify($renderedImage)->stringContainsString('width="620"');
  }

  public function testItRendersText() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][2];
    $DOM = $this->dOMParser->parseStr((new Text)->render($template));
    // blockquotes and paragraphs should be converted to spans and placed inside a table
    verify(
      $DOM('tr > td > table > tr > td.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    verify(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->notEmpty();
    // blockquote should contain heading elements but not paragraphs
    verify(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->stringContainsString('<h2');
    verify(
      $DOM('tr > td > table > tr > td.mailpoet_blockquote', 0)->html()
    )->stringNotContainsString('<p');
    // ul/ol/li should have mailpoet_paragraph class added & styles applied
    verify(
      $DOM('tr > td > ul.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    verify(
      $DOM('tr > td > ol.mailpoet_paragraph > li.mailpoet_paragraph', 0)->html()
    )->notEmpty();
    verify($DOM('tr > td.mailpoet_text > ul.mailpoet_paragraph', 0)->attr('style'))
      ->stringContainsString('padding-top:0;padding-bottom:0;margin-top:10px;text-align:left;margin-bottom:10px;');
    // headings should be styled
    verify($DOM('tr > td.mailpoet_text > h1', 0)->attr('style'))
      ->stringContainsString('padding:0;font-style:normal;font-weight:normal;');

    // trailing line breaks should be cut off, but not inside an element
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][8];
    $DOM = $this->dOMParser->parseStr((new Text)->render($template));
    verify(count($DOM('tr > td > br', 0)))
      ->equals(0);
    verify($DOM('tr > td > h3', 0)->html())
      ->stringContainsString('<a');
  }

  public function testItRendersDivider() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][3];
    $DOM = $this->dOMParser->parseStr((new Divider)->render($template));
    // element should be properly nested and its border-top-width set
    verify(
      preg_match(
        '/border-top-width: 3px/',
        $DOM('tr > td.mailpoet_divider > table > tr > td.mailpoet_divider-cell', 0)->attr('style')
      )
    )->equals(1);
  }

  public function testItRendersSpacer() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    // element should be properly nested and its height set
    verify($DOM('tr > td.mailpoet_spacer', 0)->attr('height'))->equals(50);
  }

  public function testItSetsSpacerBackground() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][4];
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    verify($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))->null();
    $template['styles']['block']['backgroundColor'] = '#ffff';
    $DOM = $this->dOMParser->parseStr((new Spacer)->render($template));
    verify($DOM('tr > td.mailpoet_spacer', 0)->attr('bgcolor'))
      ->equals('#ffff');
  }

  public function testItCalculatesButtonWidth() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $template['styles']['block']['width'] = '700px';
    $buttonWidth = (new Button)->calculateWidth($template, self::COLUMN_BASE_WIDTH);
    verify($buttonWidth)->equals('618px'); //(width - (2 * border width)
  }

  public function testItRendersButton() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $DOM = $this->dOMParser->parseStr((new Button)->render($template, self::COLUMN_BASE_WIDTH));
    // element should be properly nested with arcsize/styles/fillcolor set
    verify(
      $DOM('tr > td > div > table > tr > td > table > tr > td > a.mailpoet_button', 0)->html()
    )->notEmpty();
    verify(
      preg_match(
        '/line-height: 30px/',
        $DOM('a.mailpoet_button', 0)->attr('style')
      )
    )->equals(1);
    verify(
      preg_match(
        '/arcsize="' . round(20 / 30 * 100) . '%"/',
        $DOM('tr > td > div > table > tr > td', 0)->text()
      )
    )->equals(1);
    verify(
      preg_match(
        '/style="height:30px.*?width:98px/',
        $DOM('tr > td > div > table > tr > td', 0)->text()
      )
    )->equals(1);
    verify(
      preg_match(
        '/style="color:#ffffff.*?font-family:Arial.*?font-size:14px/',
        $DOM('tr > td > div > table > tr > td', 0)->text()
      )
    )->equals(1);
    verify(
      preg_match(
        '/fillcolor="#666666/',
        $DOM('tr > td > div > table > tr > td', 0)->text()
      )
    )->equals(1);
  }

  public function testItUsesFullFontFamilyNameInElementStyles() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][5];
    $template['styles']['block']['fontFamily'] = 'Lucida';
    $DOM = $this->dOMParser->parseStr((new Button)->render($template, self::COLUMN_BASE_WIDTH));
    $style = $DOM('td.mailpoet_table_button', 0)->attr('style');
    verify($style)->stringContainsString('font-family: \'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif');
  }

  public function testItRendersSocialIcons() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][0]['blocks'][0]['blocks'][6];
    $DOM = $this->dOMParser->parseStr((new Social)->render($template));
    // element should be properly nested, contain social icons and
    // image source/link href/alt should be  properly set
    verify($DOM('tr > td', 0)->html())->notEmpty();
    verify($DOM('a', 0)->attr('href'))->equals('http://example.com');
    verify($DOM('td > a:nth-of-type(9) > img')->attr('src'))->stringContainsString('custom.png');
    verify($DOM('td > a:nth-of-type(9) > img')->attr('alt'))->equals('custom');
    // there should be 9 icons
    verify(count($DOM('a > img')))->equals(9);
  }

  public function testItDoesNotRenderSocialIconsWithoutImageSrc() {
    $block = [
      'icons' => [
        'image' => '',
        'width' => '100',
        'height' => '100',
        'link' => '',
        'iconType' => 'custom',
      ],
    ];
    $renderedBlock = (new Social)->render($block);
    verify($renderedBlock)->equals('');
  }

  public function testItRendersFooter() {
    $newsletter = (array)$this->newsletter->getBody();
    $template = $newsletter['content']['blocks'][3]['blocks'][0]['blocks'][0];
    $footerRenderer = new Footer(
      $this->diContainer->get(NewsletterHtmlSanitizer::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $DOM = $this->dOMParser->parseStr($footerRenderer->render($template));
    // element should be properly nested, and styles should be applied
    verify($DOM('tr > td.mailpoet_footer', 0)->html())->notEmpty();
    verify($DOM('tr > td > a', 0)->html())->notEmpty();
    verify($DOM('a', 0)->attr('style'))->notEmpty();
    verify($DOM('td', 0)->attr('style'))->notEmpty();
  }

  public function testItSetsSubject() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    $DOM = $this->dOMParser->parseStr($template['html']);
    $subject = trim($DOM('title')->text());
    verify($subject)->equals($this->newsletter->getSubject());
  }

  public function testItSetsPreheader() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    $DOM = $this->dOMParser->parseStr($template['html']);
    $preheader = trim($DOM('td.mailpoet_preheader')->text());
    verify($preheader)->equals($this->newsletter->getPreheader());
  }

  public function testItDoesNotAddMailpoetLogoIfItIsNotRequired() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    verify($template['html'])->stringNotContainsString('mailpoet_logo_newsletter.png');
  }

  public function testItDoesNotAddMailpoetLogoWhenPreviewIsEnabled() {
    $capabilitiesManager = $this->createMock(CapabilitiesManager::class);
    $capabilitiesManager->method('getCapability')->willReturn(new Capability('mailpoetLogoInEmails', 'boolean', true));
    $renderer = new Renderer(
      $this->diContainer->get(BodyRenderer::class),
      $this->diContainer->get(\MailPoet\EmailEditor\Engine\Renderer\Renderer::class),
      $this->diContainer->get(Preprocessor::class),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(LoggerFactory::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(SendingQueuesRepository::class),
      $capabilitiesManager
    );
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $renderer->renderAsPreview($this->newsletter);
    verify($template['html'])->stringNotContainsString('mailpoet_logo_newsletter.png');
  }

  public function testItAddsMailpoetLogoIfItIsRequired() {
    $capabilitiesManager = $this->createMock(CapabilitiesManager::class);
    $capabilitiesManager->method('getCapability')->willReturn(new Capability('mailpoetLogoInEmails', 'boolean', true));
    $renderer = new Renderer(
      $this->diContainer->get(BodyRenderer::class),
      $this->diContainer->get(\MailPoet\EmailEditor\Engine\Renderer\Renderer::class),
      $this->diContainer->get(Preprocessor::class),
      $this->diContainer->get(\MailPoetVendor\CSS::class),
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(LoggerFactory::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(SendingQueuesRepository::class),
      $capabilitiesManager
    );

    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);

    $template = $renderer->render($this->newsletter);
    verify($template['html'])->stringContainsString('mailpoet_logo_newsletter.png');
  }

  public function testItPostProcessesTemplate() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    // !important should be stripped from everywhere except from with the <style> tag
    verify(preg_match('/<style.*?important/s', $template['html']))->equals(1);
    verify(preg_match('/mailpoet_template.*?important/s', $template['html']))->equals(0);

    // spaces are only replaces in image tag URLs
    verify(preg_match('/image%20with%20space.jpg/s', $template['html']))->equals(1);
    verify(preg_match('/link%20with%20space.jpg/s', $template['html']))->equals(0);

    // non mso condition for button is rendered correctly
    verify(preg_match('/<\!--\[if \!mso\]><\!-- -->\s*<table.*<td class=\"mailpoet\_table\_button\".+<\/td>.*<\/table>\s*<\!--<\!\[endif\]-->/s', $template['html']))->equals(1);
  }

  public function testItFixesAmpersandsInLinks() {
    $body = json_decode(
      (string)file_get_contents(dirname(__FILE__) . '/RendererTestData.json'),
      true
    );
    $this->assertIsArray($body);
    $links = '<a href="https://example.com?a=1&b=2">Link1</a>'; // Ok link
    $links .= '<a href="https://example.com?c=1&amp;d=2">Link2</a>'; // Link provided by TinyMCE via node.innerHTML
    $links .= '<a href="https://example.com?e=1&amp;amp;f=2">Link3</a>'; // Link pasted via smart paste and provided by TinyMCE via node.innerHTML

    $body = [
      'content' => [
        'type' => 'container',
        'blocks' => [[
          'type' => 'container',
          'styles' => ['block' => []],
          'blocks' => [[
            'type' => 'container',
            'styles' => ['block' => []],
            'blocks' => [[
              'type' => 'text',
              'text' => '<p>' . $links . '</p>',
            ]],
          ]],
        ]],
      ],
    ];

    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    $this->assertStringContainsString('https://example.com?a=1&b=2', $template['html']);
    $this->assertStringContainsString('https://example.com?c=1&d=2', $template['html']);
    $this->assertStringContainsString('https://example.com?e=1&f=2', $template['html']);
  }

  // Test case for MAILPOET-3660
  public function testItRendersPostContentWhenMultipleQuotesInPostTitle() {
    $postTitle = 'This \"is \'a\" test';
    $postContent = '<!-- wp:paragraph -->\n<p>This is the post content</p>\n<!-- /wp:paragraph -->';
    $postId = wp_insert_post(
      [
        'post_title' => $postTitle,
        'post_content' => $postContent,
        'post_status' => 'publish',
      ]
    );

    $filename = dirname(__DIR__) . '/../../tests/_data/600x400.jpg';
    $contents = file_get_contents($filename);
    if (!$contents) {
      $this->fail('Error preparing data for test: failed to retrieve file contents.');
    }

    $upload = wp_upload_bits(basename($filename), null, $contents);
    $attachmentId = $this->makeAttachment($upload);
    set_post_thumbnail($postId, $attachmentId);

    $body = json_decode(
      (string)file_get_contents(dirname(__FILE__) . '/RendererTestALCdata.json'),
      true
    );
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);

    $template = $this->renderer->render($this->newsletter);
    verify($template['html'])->stringContainsString('This is the post content');

    wp_delete_attachment($attachmentId, true);
    wp_delete_post($postId, true);
  }

  public function testItRendersLanguageAttribute() {
    $currentLanguageOption = $this->currentGlobalLocale();
    $this->setGlobalLocale('fr_FR');
    $expectedLanguage = 'fr-FR';

    $template = $this->renderer->render($this->newsletter);
    $DOM = $this->dOMParser->parseStr($template['html']);
    $html = $DOM->query('html');
    $this->setGlobalLocale($currentLanguageOption);
    $this->assertEquals($expectedLanguage, $html->attr('lang'));
  }

  public function testItRendersScreenReaderText() {
    $body = json_decode(
      (string)file_get_contents(dirname(__FILE__) . '/RendererTestData.json'),
      true
    );
    $this->assertIsArray($body);
    $this->newsletter->setBody($body);
    $template = $this->renderer->render($this->newsletter);
    $DOM = $this->dOMParser->parseStr($template['html']);
    verify((string)$DOM)->stringContainsString('<span class="screen-reader-text" style="border:0;clip:rect(1px,1px,1px,1px);-webkit-clip-path:inset(50%);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;word-wrap:normal;color:transparent;font-size:0;line-height:0;mso-hide:all">');
  }

  private function currentGlobalLocale() {
    global $locale;
    return $locale;
  }

  private function setGlobalLocale($value) {
    global $locale;
    $locale = $value;
  }

  public function makeAttachment($upload, $parentPostId = 0) {
    if (!function_exists('wp_crop_image')) {
      include(ABSPATH . 'wp-admin/includes/image.php');
    }

    if (!empty($upload['type'])) {
      $type = $upload['type'];
    } else {
      $mime = wp_check_filetype($upload['file']);
      $type = $mime['type'];
    }

    $attachment = [
      'post_title' => basename($upload['file']),
      'post_content' => '',
      'post_type' => 'attachment',
      'post_parent' => $parentPostId,
      'post_mime_type' => $type,
      'guid' => $upload['url'],
    ];

    $id = wp_insert_attachment($attachment, $upload['file'], $parentPostId);
    $metadata = wp_generate_attachment_metadata($id, $upload['file']);
    wp_update_attachment_metadata($id, $metadata);

    return $id;
  }

  public function testItRendersDynamicProductsBlock() {
    // Create test products
    $wp = $this->diContainer->get(\MailPoet\WP\Functions::class);

    // Create a published product using the tester
    $this->tester->createWooCommerceProduct([
      'name' => 'Test Product 1',
      'status' => 'publish',
      'price' => '10.00',
    ]);

    $this->tester->createWooCommerceProduct([
      'name' => 'Test Product 2',
      'status' => 'publish',
      'price' => '10.00',
    ]);

    // Create a newsletter with a dynamic products block
    $newsletter = new \MailPoet\Entities\NewsletterEntity();
    $newsletter->setSubject('Dynamic Products Test');
    $newsletter->setType('standard');
    $newsletter->setStatus('active');

    $dynamicProductsBlock = [
      'type' => 'dynamicProducts',
      'amount' => '2',
      'contentType' => 'product',
      'terms' => [],
      'inclusionType' => 'include',
      'sortBy' => 'newest',
      'displayType' => 'excerpt',
      'titleFormat' => 'h2',
      'titleAlignment' => 'left',
      'titleIsLink' => false,
      'imageFullWidth' => true,
      'titlePosition' => 'abovePost',
      'featuredImagePosition' => 'left',
      'readMoreType' => 'button',
      'readMoreText' => 'Read more',
      'readMoreButton' => [
        'type' => 'button',
        'text' => 'Read the post',
        'url' => '[postLink]',
        'styles' => [
          'block' => [
            'backgroundColor' => '#2ea1cd',
            'borderColor' => '#0074a2',
            'borderWidth' => '1px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '160px',
            'lineHeight' => '30px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Verdana',
            'fontSize' => '16px',
            'fontWeight' => 'normal',
            'textAlign' => 'center',
          ],
        ],
      ],
      'showDivider' => true,
      'divider' => [
        'type' => 'divider',
        'styles' => [
          'block' => [
            'backgroundColor' => 'transparent',
            'padding' => '13px',
            'borderStyle' => 'solid',
            'borderWidth' => '3px',
            'borderColor' => '#aaaaaa',
          ],
        ],
      ],
    ];

    $newsletter->setBody([
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => [
          'block' => [
            'backgroundColor' => 'transparent',
          ],
        ],
        'blocks' => [
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => [
              'block' => [
                'backgroundColor' => 'transparent',
              ],
            ],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => [
                  'block' => [
                    'backgroundColor' => 'transparent',
                  ],
                ],
                'blocks' => [
                  $dynamicProductsBlock,
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    // Render the newsletter
    $template = $this->renderer->render($newsletter);

    // Verify the products are rendered
    verify(isset($template['html']))->true();
    verify($template['html'])->stringContainsString('Test Product');
  }
}
