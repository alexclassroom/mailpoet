<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Config\Populator;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Subscribers\Source;

class SegmentsSimpleListRepositoryTest extends \MailPoetTest {
  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  public function _before(): void {
    parent::_before();
    $segmentRepository = $this->diContainer->get(SegmentsRepository::class);

    // Prepare Segments
    $this->createDynamicSegmentEntityForEditorUsers();
    $defaultSegment = $segmentRepository->createOrUpdate('Segment Default 1' . rand(0, 10000));
    $segmentRepository->createOrUpdate('Segment Default 2' . rand(0, 10000));
    $populator = $this->diContainer->get(Populator::class);
    $populator->up(); // Prepare WooCommerce and WP Users segments
    // Remove synced WP Users
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);

    // Prepare Subscribers
    $wpUserEmail = 'user-role-test1@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $wpUserSubscriber = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $wpUserEmail]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpUserSubscriber);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $subscriber1 = $this->createSubscriberEntity();
    $subscriber2 = $this->createSubscriberEntity();
    $subscriber2->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberEntity(); // Subscriber without list
    $this->createSubscriberSegmentEntity($defaultSegment, $subscriber1);
    $this->createSubscriberSegmentEntity($defaultSegment, $subscriber2);
    $this->entityManager->flush();

    $this->segmentsListRepository = $this->diContainer->get(SegmentsSimpleListRepository::class);
  }

  public function testItReturnsCorrectlyFormattedOutput(): void {
    [$list] = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    verify($list['id'])->isString();
    verify($list['name'])->isString();
    verify($list['type'])->isString();
    verify($list['subscribers'])->isInt();
  }

  public function testItReturnsSegmentsWithSubscribedSubscribersCount(): void {
    $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts();
    verify($segments)->arrayCount(5);
    // Default 1
    verify($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[0]['subscribers'])->equals(1);
    // Default 2
    verify($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[1]['subscribers'])->equals(0);
    // Dynamic
    verify($segments[2]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    verify($segments[2]['subscribers'])->equals(1);
    // WooCommerce Users Segment
    verify($segments[3]['type'])->equals(SegmentEntity::TYPE_WC_USERS);
    verify($segments[3]['subscribers'])->equals(0);
    // WordPress Users
    verify($segments[4]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    verify($segments[4]['subscribers'])->equals(1);
  }

  public function testItReturnsSegmentsWithSubscribedSubscribersCountFilteredBySegmentType(): void {
    $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts([SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS]);
    verify($segments)->arrayCount(3);
    // Default 1
    verify($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[0]['subscribers'])->equals(1);
    // Default 2
    verify($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[1]['subscribers'])->equals(0);
    // WordPress Users
    verify($segments[2]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    verify($segments[2]['subscribers'])->equals(1);
  }

  public function testItReturnsSegmentsWithAssociatedSubscribersCount(): void {
    $segments = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    verify($segments)->arrayCount(5);
    // Default 1
    verify($segments[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[0]['subscribers'])->equals(2);
    // Default 2
    verify($segments[1]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    verify($segments[1]['subscribers'])->equals(0);
    // Dynamic
    verify($segments[2]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    verify($segments[2]['subscribers'])->equals(1);
    // WooCommerce Users Segment
    verify($segments[3]['type'])->equals(SegmentEntity::TYPE_WC_USERS);
    verify($segments[3]['subscribers'])->equals(0);
    // WordPress Users
    verify($segments[4]['type'])->equals(SegmentEntity::TYPE_WP_USERS);
    verify($segments[4]['subscribers'])->equals(1);
  }

  public function testItCanAddSegmentForSubscribersWithoutList(): void {
    $segments = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
    $segments = $this->segmentsListRepository->addVirtualSubscribersWithoutListSegment($segments);
    verify($segments)->arrayCount(6);
    verify($segments[5]['type'])->equals(SegmentEntity::TYPE_WITHOUT_LIST);
    verify($segments[5]['id'])->equals('0');
    verify($segments[5]['subscribers'])->equals(1);
  }

  private function createSubscriberEntity(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $rand = rand(0, 100000);
    $subscriber->setEmail("john{$rand}@mailpoet.com");
    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setSource(Source::API);
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createSubscriberSegmentEntity(SegmentEntity $segment, SubscriberEntity $subscriber): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    return $subscriberSegment;
  }

  private function createDynamicSegmentEntityForEditorUsers(): SegmentEntity {
    $segment = new SegmentEntity('Segment' . rand(0, 10000), SegmentEntity::TYPE_DYNAMIC, 'Segment description');
    $dynamicFilterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $dynamicFilterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }
}
