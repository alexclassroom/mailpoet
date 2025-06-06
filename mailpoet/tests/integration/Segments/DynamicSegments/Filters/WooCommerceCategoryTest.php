<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class WooCommerceCategoryTest extends \MailPoetTest {
  /** @var WooCommerceCategory */
  private $wooCommerceCategoryFilter;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int[] */
  private $productIds;

  /** @var int[] */
  private $orderIds;

  /** @var int[] */
  private $categoryIds;

  public function _before(): void {
    $this->wooCommerceCategoryFilter = $this->diContainer->get(WooCommerceCategory::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanUp();
  }

  public function testItGetsSubscribersThatPurchasedProductsInAnyCategory(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerId3OnHold = $this->tester->createCustomer('customer-on-hold@example.com');
    $customerId4PendingPayment = $this->tester->createCustomer('customer-pending-payment@example.com');
    $customerId5 = $this->tester->createCustomer('customer5@example.com');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $category1 = $this->createCategory('productCat1');
    $category2 = $this->createCategory('productCat2');

    $productId1 = $this->createProduct('testProduct1', [$category1]);
    $productId2 = $this->createProduct('testProduct2', [$category2]);

    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $productId1, $customerId1);
    $orderId2 = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $orderId2, $productId2, $customerId2);
    $orderId3 = $this->createOrder($customerId3OnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $orderId3, $productId2, $customerId3OnHold);
    $orderId4 = $this->createOrder($customerId4PendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $orderId4, $productId2, $customerId4PendingPayment);
    $orderId5 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(5, $orderId5, $productId1, $customerId5);
    $orderId6 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(6, $orderId6, $productId2, $customerId5);

    $expectedEmails = ['customer1@example.com', 'customer2@example.com', 'customer5@example.com'];
    $segmentFilterData = $this->getSegmentFilterData($this->categoryIds, DynamicSegmentFilterData::OPERATOR_ANY);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatDidNotPurchaseProducts(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerId3OnHold = $this->tester->createCustomer('customer-on-hold@example.com');
    $customerId4PendingPayment = $this->tester->createCustomer('customer-pending-payment@example.com');
    $customerId5 = $this->tester->createCustomer('customer5@example.com');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $category1 = $this->createCategory('productCat1');
    $category2 = $this->createCategory('productCat2');

    $productId1 = $this->createProduct('testProduct1', [$category1]);
    $productId2 = $this->createProduct('testProduct2', [$category2]);

    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $productId1, $customerId1);
    $orderId2 = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $orderId2, $productId2, $customerId2);
    $orderId3 = $this->createOrder($customerId3OnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $orderId3, $productId2, $customerId3OnHold);
    $orderId4 = $this->createOrder($customerId4PendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $orderId4, $productId2, $customerId4PendingPayment);
    $orderId5 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(5, $orderId5, $productId1, $customerId5);
    $orderId6 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(6, $orderId6, $productId2, $customerId5);
    $expectedEmails = [
      'a1@example.com',
      'a2@example.com',
      'customer-on-hold@example.com',
      'customer-pending-payment@example.com',
      'customer2@example.com',
    ];
    $segmentFilterData = $this->getSegmentFilterData([$this->categoryIds[0]], DynamicSegmentFilterData::OPERATOR_NONE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasedAllProducts(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerId3OnHold = $this->tester->createCustomer('customer-on-hold@example.com');
    $customerId4PendingPayment = $this->tester->createCustomer('customer-pending-payment@example.com');
    $customerId5 = $this->tester->createCustomer('customer5@example.com');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $category1 = $this->createCategory('productCat1');
    $category2 = $this->createCategory('productCat2');

    $productId1 = $this->createProduct('testProduct1', [$category1]);
    $productId2 = $this->createProduct('testProduct2', [$category2]);

    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $productId1, $customerId1);
    $orderId2 = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $orderId2, $productId2, $customerId2);
    $orderId3 = $this->createOrder($customerId3OnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $orderId3, $productId2, $customerId3OnHold);
    $orderId4 = $this->createOrder($customerId4PendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $orderId4, $productId2, $customerId4PendingPayment);
    $orderId5 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(5, $orderId5, $productId1, $customerId5);
    $orderId6 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(6, $orderId6, $productId2, $customerId5);

    $segmentFilterData = $this->getSegmentFilterData($this->categoryIds, DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    verify($emails)->arrayCount(1); // customer5
    $expectedEmails = ['customer1@example.com', 'customer5@example.com'];
    $segmentFilterData = $this->getSegmentFilterData([$this->categoryIds[0]], DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItGetsSubscribersThatPurchasesAllProductsInMultipleOrders(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $customerId2 = $this->tester->createCustomer('customer2@example.com');
    $customerId3OnHold = $this->tester->createCustomer('customer-on-hold@example.com');
    $customerId4PendingPayment = $this->tester->createCustomer('customer-pending-payment@example.com');
    $customerId5 = $this->tester->createCustomer('customer5@example.com');

    $this->createSubscriber('a1@example.com');
    $this->createSubscriber('a2@example.com');

    $category1 = $this->createCategory('productCat1');
    $category2 = $this->createCategory('productCat2');

    $productId1 = $this->createProduct('testProduct1', [$category1]);
    $productId2 = $this->createProduct('testProduct2', [$category2]);

    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $productId1, $customerId1);
    $orderId2 = $this->createOrder($customerId2, Carbon::now());
    $this->addToOrder(2, $orderId2, $productId2, $customerId2);
    $orderId3 = $this->createOrder($customerId3OnHold, Carbon::now(), 'wc-on-hold');
    $this->addToOrder(3, $orderId3, $productId2, $customerId3OnHold);
    $orderId4 = $this->createOrder($customerId4PendingPayment, Carbon::now(), 'wc-pending');
    $this->addToOrder(4, $orderId4, $productId2, $customerId4PendingPayment);
    $orderId5 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(5, $orderId5, $productId1, $customerId5);
    $orderId6 = $this->createOrder($customerId5, Carbon::now());
    $this->addToOrder(6, $orderId6, $productId2, $customerId5);
    $segmentFilterData = $this->getSegmentFilterData($this->categoryIds, DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $expectedEmails = ['customer5@example.com'];
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItWorksWithHierarchicalCategories(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $category1 = $this->createCategory('productCat1');
    $category1child = $this->createCategory('productCat1Child', $category1);
    $product1 = $this->createProduct('testProduct1', [$category1child]);
    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $product1, $customerId1);
    $segmentFilterData = $this->getSegmentFilterData([$category1], DynamicSegmentFilterData::OPERATOR_ANY);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $expectedEmails = ['customer1@example.com'];
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItWorksWithMultipleChildCategories(): void {
    $customerId1 = $this->tester->createCustomer('customer1@example.com');
    $category1 = $this->createCategory('productCat1');
    $category1child1 = $this->createCategory('productCat1Child1', $category1);
    $category2 = $this->createCategory('productCat2');

    $product1 = $this->createProduct('testProduct1', [$category1child1]);
    $product2 = $this->createProduct('testProduct2', [$category2]);

    $orderId1 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(1, $orderId1, $product1, $customerId1);
    $orderId2 = $this->createOrder($customerId1, Carbon::now());
    $this->addToOrder(2, $orderId2, $product2, $customerId1);

    $segmentFilterData = $this->getSegmentFilterData([$category1, $category2], DynamicSegmentFilterData::OPERATOR_ALL);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->wooCommerceCategoryFilter);
    $expectedEmails = ['customer1@example.com'];
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  public function testItRetrievesLookupData(): void {
    $category1name = 'category' . rand();
    $category2name = 'category' . rand();

    $category1 = wp_insert_term($category1name, 'product_cat');
    $category2 = wp_insert_term($category2name, 'product_cat');

    $this->assertIsArray($category1);
    $this->assertIsArray($category2);

    $data = $this->getSegmentFilterData([$category1['term_id'], $category2['term_id']], 'none');
    $lookupData = $this->wooCommerceCategoryFilter->getLookupData($data);

    $this->assertEqualsCanonicalizing([
      'categories' => [
        $category1['term_id'] => $category1name,
        $category2['term_id'] => $category2name,
      ],
    ], $lookupData);
  }

  private function getSegmentFilterData(array $categoryIds, string $operator): DynamicSegmentFilterData {
    $filterData = [
      'category_ids' => $categoryIds,
      'operator' => $operator,
    ];

    return new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCategory::ACTION_CATEGORY,
      $filterData
    );
  }

  private function createOrder(int $customerId, Carbon $createdAt, string $status = 'wc-completed'): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $orderId = $order->get_id();
    $this->tester->updateWooOrderStats($orderId);
    $this->orderIds[] = $orderId;

    return $orderId;
  }

  private function createProduct(string $name, array $terms): int {
    $productData = [
      'post_type' => 'product',
      'post_status' => 'publish',
      'post_title' => $name,
    ];
    $productId = wp_insert_post($productData);
    if (is_int($productId)) {
      wp_set_object_terms($productId, $terms, 'product_cat');
    }
    $this->productIds[] = $productId;
    return $productId;
  }

  private function createCategory(string $name, int $categoryParentId = 0): int {
    // Check if the term already exists
    $existingTerm = get_term_by('name', $name, 'product_cat');

    if ($existingTerm instanceof \WP_Term) {
      //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $categoryId = $existingTerm->term_id;
    } else {
      // Term does not exist, create a new one
      $result = wp_insert_term(
        $name,
        'product_cat',
        ['parent' => $categoryParentId]
      );

      if (is_wp_error($result)) {
        throw new \Exception('Unable to create category: ' . $result->get_error_message());
      }

      $categoryId = $result['term_id'];
    }
    $this->assertIsInt($categoryId);
    $this->categoryIds[] = $categoryId;
    return $categoryId;
  }

  private function addToOrder(int $orderItemId, int $orderId, int $productId, int $customerId): void {
    global $wpdb;
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_order_product_lookup (order_item_id, order_id, product_id, customer_id, variation_id, product_qty, date_created)
      VALUES ({$orderItemId}, {$orderId}, {$productId}, {$customerId}, 0, 1, now())
    ");
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    return $subscriber;
  }

  public function _after(): void {
    parent::_after();
    $this->cleanUp();
  }

  private function cleanUp(): void {
    global $wpdb;

    if (!empty($this->orderIds)) {
      foreach ($this->orderIds as $orderId) {
        wp_delete_post($orderId);
      }
    }

    if (!empty($this->productIds)) {
      foreach ($this->productIds as $productId) {
        wp_delete_post($productId);
      }
    }

    if (!empty($this->categoryIds)) {
      foreach ($this->categoryIds as $categoryId) {
        wp_delete_term($categoryId, 'product_cat');
      }
    }

    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_items");
    $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta");
  }
}
