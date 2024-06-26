<?php declare(strict_types = 1);

namespace MailPoet\Entities;

class SubscriberEntityTest extends \MailPoetUnitTest {
  public function testMagicGetterReturnsData() {
    $subscriber = new SubscriberEntity();
    $subscriber->setWpUserId(4);
    verify($subscriber->wp_user_id)->equals(4);// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testMagicGetterReturnsNullForUnknown() {
    $subscriber = new SubscriberEntity();
    verify($subscriber->non_existing_property)->null();// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
