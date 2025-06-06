<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Config\Env;
use MailPoet\EmailEditor\Engine\Renderer\Renderer as GuntenbergRenderer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\NewsletterProcessingException;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\pQuery\DomNode;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Html2Text\Html2Text;

class Renderer {
  const NEWSLETTER_TEMPLATE = 'Template.html';
  const FILTER_POST_PROCESS = 'mailpoet_rendering_post_process';

  /** @var BodyRenderer */
  private $bodyRenderer;

  /** @var GuntenbergRenderer */
  private $guntenbergRenderer;

  /** @var Preprocessor */
  private $preprocessor;

  /** @var \MailPoetVendor\CSS */
  private $cSSInliner;

  /** @var WPFunctions */
  private $wp;

  /*** @var LoggerFactory */
  private $loggerFactory;

  /*** @var NewslettersRepository */
  private $newslettersRepository;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  private CapabilitiesManager $capabilitiesManager;

  public function __construct(
    BodyRenderer $bodyRenderer,
    GuntenbergRenderer $guntenbergRenderer,
    Preprocessor $preprocessor,
    \MailPoetVendor\CSS $cSSInliner,
    WPFunctions $wp,
    LoggerFactory $loggerFactory,
    NewslettersRepository $newslettersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    CapabilitiesManager $capabilitiesManager
  ) {
    $this->bodyRenderer = $bodyRenderer;
    $this->guntenbergRenderer = $guntenbergRenderer;
    $this->preprocessor = $preprocessor;
    $this->cSSInliner = $cSSInliner;
    $this->wp = $wp;
    $this->loggerFactory = $loggerFactory;
    $this->newslettersRepository = $newslettersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->capabilitiesManager = $capabilitiesManager;
  }

  public function render(NewsletterEntity $newsletter, ?SendingQueueEntity $sendingQueue = null, $type = false) {
    return $this->_render($newsletter, $sendingQueue, $type);
  }

  public function renderAsPreview(NewsletterEntity $newsletter, $type = false, ?string $subject = null) {
    return $this->_render($newsletter, null, $type, true, $subject);
  }

  private function _render(NewsletterEntity $newsletter, ?SendingQueueEntity $sendingQueue = null, $type = false, $preview = false, $subject = null) {
    $language = $this->wp->getBloginfo('language');
    $metaRobots = $preview ? '<meta name="robots" content="noindex, nofollow" />' : '';
    $subject = $subject ?: $newsletter->getSubject();
    $wpPostEntity = $newsletter->getWpPost();
    $wpPost = $wpPostEntity ? $wpPostEntity->getWpPostInstance() : null;
    if ($wpPost instanceof \WP_Post) {
      $renderedNewsletter = $this->guntenbergRenderer->render($wpPost, $subject, $newsletter->getPreheader(), $language, $metaRobots);
    } else {
      $body = (is_array($newsletter->getBody()))
        ? $newsletter->getBody()
        : [];
      $content = (array_key_exists('content', $body))
        ? $body['content']
        : [];
      $styles = (array_key_exists('globalStyles', $body))
        ? $body['globalStyles']
        : [];

      $mailPoetLogoInEmails = $this->capabilitiesManager->getCapability('mailpoetLogoInEmails');
      if (
        (isset($mailPoetLogoInEmails) && $mailPoetLogoInEmails->isRestricted) && !$preview
      ) {
        $content = $this->addMailpoetLogoContentBlock($content, $styles);
      }

      $renderedBody = "";
      try {
        $content = $this->preprocessor->process($newsletter, $content, $preview, $sendingQueue);
        $renderedBody = $this->bodyRenderer->renderBody($newsletter, $content);
      } catch (NewsletterProcessingException $e) {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_COUPONS)->error(
          $e->getMessage(),
          ['newsletter_id' => $newsletter->getId()]
        );
        $this->newslettersRepository->setAsCorrupt($newsletter);
        if ($sendingQueue) {
          $this->sendingQueuesRepository->pause($sendingQueue);
        }
      }
      $renderedStyles = $this->renderStyles($styles);
      $customFontsLinks = StylesHelper::getCustomFontsLinks($styles);

      $template = $this->injectContentIntoTemplate(
        (string)file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE),
        [
          $language,
          $metaRobots,
          htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401),
          $renderedStyles,
          $customFontsLinks,
          EHelper::escapeHtmlText($newsletter->getPreheader()),
          $renderedBody,
        ]
      );
      if ($template === null) {
        $template = '';
      }
      $templateDom = $this->inlineCSSStyles($template);
      $template = $this->postProcessTemplate($templateDom);

      $renderedNewsletter = [
        'html' => $template,
        'text' => $this->renderTextVersion($template),
      ];
    }

    return ($type && !empty($renderedNewsletter[$type])) ?
      $renderedNewsletter[$type] :
      $renderedNewsletter;
  }

  /**
   * @param array $styles
   * @return string
   */
  private function renderStyles(array $styles) {
    $css = '';
    foreach ($styles as $selector => $style) {
      switch ($selector) {
        case 'text':
          $selector = 'td.mailpoet_paragraph, td.mailpoet_blockquote, li.mailpoet_paragraph';
          break;
        case 'body':
          $selector = 'body, .mailpoet-wrapper';
          break;
        case 'link':
          $selector = '.mailpoet-wrapper a';
          break;
        case 'wrapper':
          $selector = '.mailpoet_content-wrapper';
          break;
      }

      if (!is_array($style)) {
        continue;
      }

      $css .= StylesHelper::setStyle($style, $selector);
    }
    return $css;
  }

  /**
   * @param string $template
   * @param string[] $content
   * @return string|null
   */
  private function injectContentIntoTemplate($template, $content) {
    return preg_replace_callback('/{{\w+}}/', function($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  /**
   * @param string $template
   * @return DomNode
   */
  private function inlineCSSStyles($template) {
    return $this->cSSInliner->inlineCSS($template);
  }

  /**
   * @param string $template
   * @return string
   */
  private function renderTextVersion($template) {
    $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : mb_convert_encoding($template, 'UTF-8', mb_list_encodings());
    return @Html2Text::convert($template);
  }

  /**
   * @param DomNode $templateDom
   * @return string
   */
  private function postProcessTemplate(DomNode $templateDom) {
    // replace spaces in image tag URLs
    foreach ($templateDom->query('img') as $image) {
      $image->src = str_replace(' ', '%20', $image->src);
    }
    foreach ($templateDom->query('a') as $anchor) {
      // Fix for a TinyMCE bug in smart paste which encodes & as &amp; which is then additionally encoded to &amp;amp;
      // when saving the text block content in the editor
      $href = str_replace('&amp;amp;', '&amp;', $anchor->href);
      // Replace &amp; with & in the href attributes of anchors. URLs are encoded when TinyMCE extracts Text block content via content.innerHTML.
      // Links containing &amp; work when placed in an anchor tag in a browser, but they don't work when we redirect to them for example in tracking.
      $href = str_replace('&amp;', '&', $href);
      $anchor->href = $href;
    }
    $template = $templateDom->__toString();
    $template = $this->wp->applyFilters(
      self::FILTER_POST_PROCESS,
      $template
    );
    return $template;
  }

  /**
   * @param array $content
   * @param array $styles
   * @return array
   */
  private function addMailpoetLogoContentBlock(array $content, array $styles) {
    if (empty($content['blocks'])) return $content;
    $content['blocks'][] = [
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => [
        'block' => [
          'backgroundColor' => (!empty($styles['body']['backgroundColor'])) ?
            $styles['body']['backgroundColor'] :
            'transparent',
        ],
      ],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => [
          ],
          'blocks' => [
            [
              'type' => 'image',
              'link' => 'https://www.mailpoet.com/?ref=free-plan-user-email&utm_source=free_plan_user_email&utm_medium=email',
              'src' => Env::$assetsUrl . '/img/mailpoet_logo_newsletter.png',
              'fullWidth' => false,
              'alt' => 'Email Marketing Powered by MailPoet',
              'width' => '108px',
              'height' => '65px',
              'styles' => [
                'block' => [
                  'textAlign' => 'center',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    return $content;
  }
}
