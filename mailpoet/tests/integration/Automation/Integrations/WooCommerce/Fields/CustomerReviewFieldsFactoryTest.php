<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use WC_Customer;

/**
 * @group woo
 */
class CustomerReviewFieldsFactoryTest extends \MailPoetTest {
  public function testReviewCountField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $reviewCountField = $fields['woocommerce:customer:review-count'];
    $this->assertSame('Review count', $reviewCountField->getName());
    $this->assertSame('integer', $reviewCountField->getType());
    $this->assertSame([], $reviewCountField->getArgs());

    // check values (guest)
    $this->createProductReview(0, '', 1);
    $this->createProductReview(0, 'guest@example.com', 1);
    $this->assertSame(0, $reviewCountField->getValue(new CustomerPayload()));

    // check values (registered)
    $id = $this->tester->createCustomer('customer@example.com');
    $this->createProductReview($id, 'customer@example.com', 1); // product 1 (by ID and email)
    $this->createProductReview(0, 'customer@example.com', 1); // product 1 (by email; duplicate - shouldn't be counted)
    $this->createProductReview($id, '', 1); // product 1 (by ID; duplicate - shouldn't be counted)
    $this->createProductReview($id, '', 2); // product 2 (by ID)
    $this->createProductReview(0, 'customer@example.com', 3); // product 3 (by email)

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertSame(3, $reviewCountField->getValue($customerPayload));
  }

  public function testLastReviewDateField(): void {
    $fields = $this->getFieldsMap();

    // check definitions
    $lastReviewDateField = $fields['woocommerce:customer:last-review-date'];
    $this->assertSame('Last review date', $lastReviewDateField->getName());
    $this->assertSame('datetime', $lastReviewDateField->getType());
    $this->assertSame([], $lastReviewDateField->getArgs());

    // check values (guest)
    $this->createProductReview(0, '', 1, '2023-05-04 12:08:29');
    $this->createProductReview(0, 'guest@example.com', 1, '2023-05-04 12:08:29');
    $this->assertNull($lastReviewDateField->getValue(new CustomerPayload()));

    // check values (registered) - by ID
    $id = $this->tester->createCustomer('customer1@example.com');
    $this->createProductReview($id, 'customer1@example.com', 1, '2023-05-04 12:08:29');
    $this->createProductReview($id, 'customer1@example.com', 1, '2023-05-14 19:16:38');
    $this->createProductReview($id, '', 1, '2023-05-19 23:14:27');

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertEquals(new DateTimeImmutable('2023-05-19 23:14:27'), $lastReviewDateField->getValue($customerPayload));

    // check values (registered) - by email
    $id = $this->tester->createCustomer('customer2@example.com');
    $this->createProductReview($id, 'customer2@example.com', 1, '2023-05-04 12:08:29');
    $this->createProductReview($id, 'customer2@example.com', 1, '2023-05-14 19:16:38');
    $this->createProductReview(0, 'customer2@example.com', 1, '2023-05-19 23:14:27');

    $customerPayload = new CustomerPayload(new WC_Customer($id));
    $this->assertEquals(new DateTimeImmutable('2023-05-19 23:14:27'), $lastReviewDateField->getValue($customerPayload));
  }

  private function createProductReview(int $customerId, string $customerEmail, int $productId, string $date = '2023-06-01 14:03:27'): void {
    wp_insert_comment([
      'comment_type' => 'review',
      'user_id' => $customerId,
      'comment_author_email' => $customerEmail,
      'comment_post_ID' => $productId,
      'comment_parent' => 0,
      'comment_date' => $date,
      'comment_approved' => 1,
    ]);
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(CustomerSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}