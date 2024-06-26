<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Listing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\Handler;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterOptionField;

class NewsletterListingRepositoryTest extends \MailPoetTest {
  public function testItAppliesGroup() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // all/trash groups
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'all']));
    verify($newsletters)->arrayCount(1);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'trash']));
    verify($newsletters)->arrayCount(0);

    // mark the newsletter sent
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'sent']));
    verify($newsletters)->arrayCount(1);

    // delete the newsletter
    $newsletter->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'all']));
    verify($newsletters)->arrayCount(0);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'trash']));
    verify($newsletters)->arrayCount(1);
  }

  public function testItAppliesSearch() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Search for "pineapple" here');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['search' => 'pineapple']));
    verify($newsletters)->arrayCount(1);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['search' => 'tomato']));
    verify($newsletters)->arrayCount(0);
  }

  public function testItAppliesSegmentFilter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter without segment');
    $this->entityManager->persist($newsletter);

    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment);

    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter with segment');
    $this->entityManager->persist($newsletter);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->entityManager->persist($newsletterSegment);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // without filter
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([]));
    verify($newsletters)->arrayCount(2);

    // with filter
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment' => $segment->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(1);
  }

  public function testItAppliesTypeParameter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // without type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([]));
    verify($newsletters)->arrayCount(1);

    // with 'standard' type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => 'standard',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // with 'welcome' type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => 'welcome',
      ],
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItAppliesAutomaticEmailsGroupParameter() {
    $newsletterOptionField = (new NewsletterOptionField())->findOrCreate('group', NewsletterEntity::TYPE_AUTOMATIC);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter1->setSubject('Automatic email 1');
    $this->entityManager->persist($newsletter1);

    $newsletter1Option = new NewsletterOptionEntity($newsletter1, $newsletterOptionField);
    $newsletter1Option->setValue('woocommerce');
    $this->entityManager->persist($newsletter1Option);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter2->setSubject('Automatic email 2');
    $this->entityManager->persist($newsletter2);

    $newsletter2Option = new NewsletterOptionEntity($newsletter2, $newsletterOptionField);
    $newsletter2Option->setValue('unicorns');
    $this->entityManager->persist($newsletter2Option);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // get 'woocommerce' group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => NewsletterEntity::TYPE_AUTOMATIC,
        'group' => 'woocommerce',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // get 'unicorns' group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => NewsletterEntity::TYPE_AUTOMATIC,
        'group' => 'unicorns',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // get all emails group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['type' => NewsletterEntity::TYPE_AUTOMATIC]));
    verify($newsletters)->arrayCount(2);
  }

  public function testItAppliesParentIdParameter() {
    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $parent->setSubject('Newsletter subject');
    $this->entityManager->persist($parent);

    $newsletter = new NewsletterEntity();
    $newsletter->setParent($parent);
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // existing parent ID
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // non-existent parent ID
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$newsletter->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItSearchesInStaticAndRenderedSubjectsForPostNotification() {
    $originalSubject = 'Notification history [newsletter:post_title]';
    $renderedSubject = 'Notification history Hello World Post';

    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $parent->setSubject($originalSubject);
    $this->entityManager->persist($parent);
    $this->entityManager->flush();

    $notificationHistoryNotSent = (new Newsletter())
    ->withParent($parent)
    ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
    ->withSubject($originalSubject)
    ->create();

    $notificationHistorySent = (new Newsletter())
      ->withParent($parent)
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withSubject($originalSubject)
      ->withSendingQueue([
        'status' => 'sent',
        'subject' => $renderedSubject,
      ])
      ->create();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Search by original subject with placeholder
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => $originalSubject,
    ]));
    verify($newsletters)->arrayCount(2);

    // Search by common part of subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => 'history',
    ]));
    verify($newsletters)->arrayCount(2);

    // Search by rendered subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => $renderedSubject,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getId())->equals($notificationHistorySent->getId());

    // Search by part of rendered subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => 'Hello World',
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getId())->equals($notificationHistorySent->getId());
  }

  public function testItAppliesSort() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter A');
    $this->entityManager->persist($newsletter1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter2->setSubject('Newsletter B');
    $this->entityManager->persist($newsletter2);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // ASC
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'sort_by' => 'subject',
      'sort_order' => 'asc',
    ]));
    verify($newsletters)->arrayCount(2);
    verify($newsletters[0]->getSubject())->same('Newsletter A');
    verify($newsletters[1]->getSubject())->same('Newsletter B');

    // DESC
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'sort_by' => 'subject',
      'sort_order' => 'desc',
    ]));
    verify($newsletters)->arrayCount(2);
    verify($newsletters[0]->getSubject())->same('Newsletter B');
    verify($newsletters[1]->getSubject())->same('Newsletter A');
  }

  public function testItAppliesLimitAndOffset() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter A');
    $this->entityManager->persist($newsletter1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter2->setSubject('Newsletter B');
    $this->entityManager->persist($newsletter2);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // first page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 0,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->same('Newsletter A');

    // second page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 1,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->same('Newsletter B');

    // third page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 2,
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItReturnsCorrectSegmentFilterData() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter with segment 1');
    $this->entityManager->persist($newsletter1);

    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Segment without any newsletter');
    $this->entityManager->persist($segment2);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);
    $filters = $newsletterListingRepository->getFilters($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 0,
    ]));

    verify($filters['segment'])->arrayCount(2); // All list + 1 segments
    verify($filters['segment'][0]['label'])->equals('All Lists');
    verify($filters['segment'][1]['label'])->equals('Segment 1 (1)');
    verify($filters['segment'][1]['value'])->equals($segment1->getId());
  }
}
