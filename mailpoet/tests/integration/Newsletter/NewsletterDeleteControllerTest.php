<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Test\DataFactories\NewsletterOptionField;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterDeleteControllerTest extends \MailPoetTest {
  private NewsletterDeleteController $controller;
  private NewslettersRepository $repository;
  private ScheduledTaskSubscribersRepository $taskSubscribersRepository;
  private WPFunctions $wp;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(NewsletterDeleteController::class);
    $this->repository = $this->diContainer->get(NewslettersRepository::class);
    $this->taskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItBulkDeleteNewslettersAndChildren() {
    $standardNewsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $standardQueue = $this->createQueueWithTaskAndSegmentAndSubscribers($standardNewsletter, null); // Null for scheduled task being processed
    $notification = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
    $notificationHistory = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED, $notification);
    $notificationHistoryQueue = $this->createQueueWithTaskAndSegmentAndSubscribers($notificationHistory);

    $standardSegment = $standardNewsletter->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $standardSegment);
    $standardScheduledTaks = $standardQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $standardScheduledTaks);
    $standardScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $standardScheduledTaks]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $standardScheduledTaskSubscriber);
    $notificationHistoryScheduledTask = $notificationHistoryQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $notificationHistoryScheduledTask);
    $notificationHistorySegment = $notificationHistory->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $notificationHistorySegment);
    $notificationHistoryScheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $notificationHistoryScheduledTask]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $notificationHistoryScheduledTaskSubscriber);
    $standardStatsNotification = $this->createStatNotification($standardNewsletter);
    $standardStatsNotificationScheduledTask = $standardStatsNotification->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $standardStatsNotificationScheduledTask);
    $notificationHistoryStatsNotification = $this->createStatNotification($notificationHistory);
    $notificationHistoryStatsNotificationScheduledTask = $notificationHistoryStatsNotification->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $notificationHistoryStatsNotificationScheduledTask);
    $standardLink = $this->createNewsletterLink($standardNewsletter, $standardQueue);
    $notificationHistoryLink = $this->createNewsletterLink($notificationHistory, $notificationHistoryQueue);
    $optionField = (new NewsletterOptionField())->findOrCreate('name', NewsletterEntity::TYPE_NOTIFICATION);
    $optionValue = $this->createNewsletterOption($notificationHistory, $optionField, 'value');
    $newsletterPost = $this->createNewsletterPost($notification, 1);

    $subscriber = $standardScheduledTaskSubscriber->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $statisticsNewsletter = $this->createNewsletterStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsOpen = $this->createOpenStatistics($standardNewsletter, $standardQueue, $subscriber);
    $statisticsClick = $this->createClickStatistics($standardNewsletter, $standardQueue, $subscriber, $standardLink);
    $statisticsPurchase = $this->createPurchaseStatistics($standardNewsletter, $standardQueue, $statisticsClick, $subscriber);

    // Trash
    $this->repository->bulkTrash([(int)$standardNewsletter->getId(), (int)$notification->getId()]);
    // Delete
    $this->controller->bulkDelete([(int)$standardNewsletter->getId(), (int)$notification->getId()]);

    // Clear entity manager to forget all entities
    $this->entityManager->clear();

    // Check they were all deleted
    // Newsletters
    verify($this->repository->findOneById($standardNewsletter->getId()))->null();
    verify($this->repository->findOneById($notification->getId()))->null();
    verify($this->repository->findOneById($notificationHistory->getId()))->null();

    // Sending queues
    verify($this->entityManager->find(SendingQueueEntity::class, $standardQueue->getId()))->null();
    verify($this->entityManager->find(SendingQueueEntity::class, $notificationHistoryQueue->getId()))->null();

    // Scheduled tasks subscribers
    verify($this->taskSubscribersRepository->findOneBy(['task' => $standardScheduledTaks]))->null();
    verify($this->taskSubscribersRepository->findOneBy(['task' => $notificationHistoryScheduledTask]))->null();

    // Scheduled tasks
    verify($this->entityManager->find(ScheduledTaskEntity::class, $standardScheduledTaks->getId()))->null();
    verify($this->entityManager->find(ScheduledTaskEntity::class, $notificationHistoryScheduledTask->getId()))->null();

    // Newsletter segments
    verify($this->entityManager->find(NewsletterSegmentEntity::class, $standardSegment->getId()))->null();
    verify($this->entityManager->find(NewsletterSegmentEntity::class, $notificationHistorySegment->getId()))->null();

    // Newsletter stats notifications
    verify($this->entityManager->find(StatsNotificationEntity::class, $standardStatsNotificationScheduledTask->getId()))->null();
    verify($this->entityManager->find(StatsNotificationEntity::class, $notificationHistoryStatsNotification->getId()))->null();

    // Newsletter stats notifications scheduled tasks
    verify($this->entityManager->find(ScheduledTaskEntity::class, $standardStatsNotificationScheduledTask->getId()))->null();
    verify($this->entityManager->find(ScheduledTaskEntity::class, $notificationHistoryStatsNotificationScheduledTask->getId()))->null();

    // Newsletter links
    verify($this->entityManager->find(NewsletterLinkEntity::class, $standardLink->getId()))->null();
    verify($this->entityManager->find(NewsletterLinkEntity::class, $notificationHistoryLink->getId()))->null();

    // Option fields values
    verify($this->entityManager->find(NewsletterOptionEntity::class, $optionValue->getId()))->null();

    // Newsletter post
    verify($this->entityManager->find(NewsletterPostEntity::class, $newsletterPost->getId()))->null();

    // Statistics data
    verify($this->entityManager->find(StatisticsNewsletterEntity::class, $statisticsNewsletter->getId()))->null();
    verify($this->entityManager->find(StatisticsOpenEntity::class, $statisticsOpen->getId()))->null();
    verify($this->entityManager->find(StatisticsClickEntity::class, $statisticsClick->getId()))->null();
    $statisticsPurchase = $this->entityManager->find(StatisticsWooCommercePurchaseEntity::class, $statisticsPurchase->getId());
    $this->assertNotNull($statisticsPurchase);
    verify($statisticsPurchase->getNewsletter())->null();
  }

  public function testItDeletesMultipleNewslettersWithPurchaseStatsAndKeepsStats() {
    $standardNewsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $statisticsPurchase1 = $this->createPurchaseStatsForNewsletter($standardNewsletter1);
    $standardNewsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $statisticsPurchase2 = $this->createPurchaseStatsForNewsletter($standardNewsletter2);

    // Delete
    $this->controller->bulkDelete([(int)$standardNewsletter1->getId(), (int)$standardNewsletter2->getId()]);

    // Clear entity manager to forget all entities
    $this->entityManager->clear();

    // Check Newsletters were deleted
    verify($this->repository->findOneById($standardNewsletter1->getId()))->null();
    verify($this->repository->findOneById($standardNewsletter2->getId()))->null();

    // Check purchase stats were not deleted
    $statisticsPurchase1 = $this->entityManager->find(StatisticsWooCommercePurchaseEntity::class, $statisticsPurchase1->getId());
    $statisticsPurchase2 = $this->entityManager->find(StatisticsWooCommercePurchaseEntity::class, $statisticsPurchase2->getId());
    $this->assertNotNull($statisticsPurchase1);
    verify($statisticsPurchase1->getNewsletter())->null();
    $this->assertNotNull($statisticsPurchase2);
    verify($statisticsPurchase2->getNewsletter())->null();
  }

  public function testItDeletesWpPostsBulkDelete() {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);
    $post1Id = $this->wp->wpInsertPost(['post_title' => 'Post 1']);
    $this->wp->updatePostMeta($post1Id, 'some_meta', ['some_value' => 'value1']);
    $newsletter1->setWpPost($this->entityManager->getReference(WpPostEntity::class, $post1Id));
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_SENDING);
    $post2Id = $this->wp->wpInsertPost(['post_title' => 'Post 2']);
    $this->wp->updatePostMeta($post2Id, 'some_meta', ['some_value' => 'value2']);
    $newsletter2->setWpPost($this->entityManager->getReference(WpPostEntity::class, $post2Id));
    $newsletter3 = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENDING);

    $blogPost = $this->wp->wpInsertPost(['post_title' => 'Regular blog post']);

    verify($this->wp->getPost($post1Id))->instanceOf(\WP_Post::class);
    verify($this->wp->getPost($post2Id))->instanceOf(\WP_Post::class);

    $this->entityManager->flush();
    $this->entityManager->clear();

    $this->controller->bulkDelete([(int)$newsletter1->getId(), (int)$newsletter2->getId(), (int)$newsletter3->getId()]);
    verify($this->wp->getPost($post1Id))->null();
    verify($this->wp->getPost($post2Id))->null();
    verify($this->wp->getPostMeta($post1Id, 'some_meta'))->empty();
    verify($this->wp->getPostMeta($post2Id, 'some_meta'))->empty();
    verify($this->wp->getPost($blogPost))->instanceOf(\WP_Post::class);
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT, $parent = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $newsletter->setParent($parent);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createQueueWithTaskAndSegmentAndSubscribers(NewsletterEntity $newsletter, $status = ScheduledTaskEntity::STATUS_SCHEDULED): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus($status);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $segment = new SegmentEntity("List for newsletter id {$newsletter->getId()}", SegmentEntity::TYPE_DEFAULT, 'Description');
    $this->entityManager->persist($segment);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail("sub{$newsletter->getId()}@mailpoet.com");
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($scheduledTaskSubscriber);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $newsletter->getNewsletterSegments()->add($newsletterSegment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();
    return $queue;
  }

  private function createStatNotification(NewsletterEntity $newsletter): StatsNotificationEntity {
    $task = new ScheduledTaskEntity();
    $task->setType('stats_notification');
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);
    $statsNotification = new StatsNotificationEntity($newsletter, $task);
    $this->entityManager->persist($statsNotification);
    $this->entityManager->flush();
    return $statsNotification;
  }

  private function createNewsletterLink(NewsletterEntity $newsletter, SendingQueueEntity $queue): NewsletterLinkEntity {
    $link = new NewsletterLinkEntity($newsletter, $queue, 'http://example.com', 'abcd');
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    return $link;
  }

  private function createNewsletterOption(NewsletterEntity $newsletter, NewsletterOptionFieldEntity $field, $value): NewsletterOptionEntity {
    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue($value);
    $this->entityManager->persist($option);
    $this->entityManager->flush();
    return $option;
  }

  private function createNewsletterPost(NewsletterEntity $newsletter, int $postId): NewsletterPostEntity {
    $post = new NewsletterPostEntity($newsletter, $postId);
    $this->entityManager->persist($post);
    $this->entityManager->flush();
    return $post;
  }

  private function createNewsletterStatistics(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): StatisticsNewsletterEntity {
    $statisticsNewsletter = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($statisticsNewsletter);
    $this->entityManager->flush();
    return $statisticsNewsletter;
  }

  private function createOpenStatistics(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): StatisticsOpenEntity {
    $statistics = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }

  private function createClickStatistics(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    SubscriberEntity $subscriber,
    NewsletterLinkEntity $link
  ): StatisticsClickEntity {
    $statistics = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }

  private function createPurchaseStatistics(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    StatisticsClickEntity $click,
    SubscriberEntity $subscriber
  ): StatisticsWooCommercePurchaseEntity {
    $statistics = new StatisticsWooCommercePurchaseEntity($newsletter, $queue, $click, 1, 'EUR', 100, 'completed');
    $statistics->setSubscriber($subscriber);
    $this->entityManager->persist($statistics);
    $this->entityManager->flush();
    return $statistics;
  }

  private function createPurchaseStatsForNewsletter(NewsletterEntity $newsletter): StatisticsWooCommercePurchaseEntity {
    $queue = $this->createQueueWithTaskAndSegmentAndSubscribers($newsletter, NewsletterEntity::STATUS_SENT); // Null for scheduled task being processed
    $segment = $newsletter->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $segment);
    $scheduledTask = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $scheduledTaskSubscriber = $this->taskSubscribersRepository->findOneBy(['task' => $scheduledTask]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscriber);
    $link = $this->createNewsletterLink($newsletter, $queue);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $link);
    $subscriber = $scheduledTaskSubscriber->getSubscriber();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $statisticsClick = $this->createClickStatistics($newsletter, $queue, $subscriber, $link);
    $this->assertInstanceOf(StatisticsClickEntity::class, $statisticsClick);
    return $this->createPurchaseStatistics($newsletter, $queue, $statisticsClick, $subscriber);
  }
}
