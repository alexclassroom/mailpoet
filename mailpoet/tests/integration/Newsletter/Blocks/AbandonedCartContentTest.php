<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class AbandonedCartContentTest extends \MailPoetTest {
  /** @var AbandonedCartContent */
  private $block;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var array */
  private $productIds = [];

  private $accBlock = [
    'type' => 'abandonedCartContent',
    'amount' => '2',
    'withLayout' => true,
    'contentType' => 'product',
    'postStatus' => 'publish',
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h1',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => false,
    'featuredImagePosition' => 'alternate',
    'pricePosition' => 'below',
    'readMoreType' => 'none',
    'readMoreText' => '',
    'readMoreButton' => [],
    'sortBy' => 'newest',
    'showDivider' => true,
    'divider' => [
      'type' => 'divider',
      'context' => 'abandonedCartContent.divider',
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
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(AbandonedCartContent::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);

    // Clear old products
    $products = $this->wp->getPosts(['post_type' => 'product']);
    foreach ($products as $product) {
      $this->wp->wpDeletePost((int)$product->ID);
    }

    $this->productIds = [];
    $this->productIds[] = $this->tester->createWooCommerceProduct(['name' => 'ACC Product 1'])->get_id();
    $this->productIds[] = $this->tester->createWooCommerceProduct(['name' => 'ACC Product 2'])->get_id();
    $this->productIds[] = $this->tester->createWooCommerceProduct(['name' => 'ACC Product 3'])->get_id();
    $this->productIds[] = $this->tester->createWooCommerceProduct(['name' => 'ACC Product 4'])->get_id();
  }

  public function testItDoesNotRenderIfNewsletterTypeIsNotAutomatic() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_STANDARD);
    $sendingTask = $this->createSendingTask($newsletter);
    $queue = $sendingTask->getSendingQueue();
    $result = $this->block->render($newsletter, $this->accBlock, false, $queue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfAutomaticNewsletterIsNotForAbandonedCart() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter, WooCommerceEmail::SLUG, 'some_event');
    $sendingTask = $this->createSendingTask($newsletter);
    $queue = $sendingTask->getSendingQueue();
    $result = $this->block->render($newsletter, $this->accBlock, false, $queue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->equals('[]');
  }

  public function testItRendersLatestProductsInPreviewMode() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->block->render($newsletter, $this->accBlock, true);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('ACC Product 4');
    verify($encodedResult)->stringContainsString('ACC Product 3');
    verify($encodedResult)->stringNotContainsString('ACC Product 2');
    verify($encodedResult)->stringNotContainsString('ACC Product 1');
  }

  public function testItDoesNotRenderIfNoSendingTaskIsSupplied() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->block->render($newsletter, $this->accBlock, false);
    $encodedResult = json_encode($result);
    verify($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfCartIsEmpty() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter, [AbandonedCart::TASK_META_NAME => []]);
    $queue = $sendingTask->getSendingQueue();
    $result = $this->block->render($newsletter, $this->accBlock, false, $queue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->equals('[]');
  }

  public function testItRendersAbandonedCartContentBlock() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter);
    $queue = $sendingTask->getSendingQueue();
    $result = $this->block->render($newsletter, $this->accBlock, false, $queue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('ACC Product 4');
    verify($encodedResult)->stringContainsString('ACC Product 3');
    verify($encodedResult)->stringContainsString('ACC Product 2');
    verify($encodedResult)->stringContainsString('ACC Product 1');
  }

  private function createNewsletter($subject, $type, $parent = null) {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($subject);
    $newsletter->setType($type);
    $newsletter->setParent($parent);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function setGroupAndEventOptions($newsletter, $group = WooCommerceEmail::SLUG, $event = AbandonedCart::SLUG) {
    (new NewsletterOption())->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_GROUP => $group,
      NewsletterOptionFieldEntity::NAME_EVENT => $event,
    ]);
  }

  private function createSendingTask(NewsletterEntity $newsletter, ?array $meta = null): ScheduledTaskEntity {
    $subscriber = (new Subscriber())->create(); // dummy default value
    $meta = $meta ?: [AbandonedCart::TASK_META_NAME => array_slice($this->productIds, 0, 3)];
    return $this->automaticEmailScheduler->createAutomaticEmailScheduledTask($newsletter, $subscriber, $meta);
  }
}
