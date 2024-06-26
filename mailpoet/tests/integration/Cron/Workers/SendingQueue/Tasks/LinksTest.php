<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class LinksTest extends \MailPoetTest {
  /** @var Links */
  private $links;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  protected function _before() {
    parent::_before();
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory->withSendingQueue()->create();
    $this->queue = $this->newsletter->getQueues()->first();
    $this->links = $this->diContainer->get(Links::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
  }

  public function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => 'some_hash',
      ],
    ];

    $this->links->saveLinks($links, $this->newsletter, $this->queue);

    $newsletterLink = $this->newsletterLinkRepository->findOneBy(['hash' => $links[0]['hash']]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $newsletterLink);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterLink->getNewsletter());
    verify($newsletterLink->getNewsletter()->getId())->equals($this->newsletter->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $newsletterLink->getQueue());
    verify($newsletterLink->getQueue()->getId())->equals($this->queue->getId());
    verify($newsletterLink->getUrl())->equals($links[0]['link']);
  }

  public function testItCanHashAndReplaceLinks() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $result = $this->links->hashAndReplaceLinks($renderedNewsletter, 0, 0);
    $processedRenderedNewsletterBody = $result[0];
    $processedAndHashedLinks = $result[1];
    verify($processedRenderedNewsletterBody['html'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    verify($processedRenderedNewsletterBody['text'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    verify($processedAndHashedLinks[0]['link'])->equals('http://example.com');
  }

  public function testItCanProcessRenderedBody() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];

    $result = $this->links->process($renderedNewsletter, $this->newsletter, $this->queue);

    $newsletterLink = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $newsletterLink);
    verify($result['html'])->stringContainsString($newsletterLink->getHash());
  }

  public function testItCanEnsureThatInstantUnsubscribeLinkIsAlwaysPresent() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];

    $this->links->process($renderedNewsletter, $this->newsletter, $this->queue);

    $unsubscribeCount = $this->newsletterLinkRepository->countBy(
      [
        'newsletter' => $this->newsletter->getId(),
        'url' => NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      ]
    );
    verify($unsubscribeCount)->equals(1);
  }
}
