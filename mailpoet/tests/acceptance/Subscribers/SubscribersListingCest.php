<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\Tag;

class SubscribersListingCest {
  public function subscribersListing(\AcceptanceTester $i) {
    $i->wantTo('Open subscribers listings page');

    $tag1 = (new Tag())
      ->withName('My Tag 1')
      ->create();
    $tag2 = (new Tag())
      ->withName('My Tag 2')
      ->create();
    $tag3 = (new Tag())
      ->withName('My Tag 3')
      ->create();

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->withTags([$tag1, $tag2, $tag3])
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('wp@example.com');
    $i->waitForText('wp@example.com');
    $i->waitForText('My Tag 1');
    $i->waitForText('My Tag 2');
    $i->waitForText('My Tag 3');
  }

  public function useTagFilter(\AcceptanceTester $i) {
    $i->wantTo('Open subscribers listings page');

    $tag = (new Tag())
      ->withName('My Tag')
      ->create();

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->create();

    (new Subscriber())
      ->withEmail('wp@mailpoet.com')
      ->withTags([$tag])
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText('All Tags');
    $i->selectOption('[data-automation-id="listing_filter_tag"]', $tag->getName());
    $i->waitForText('wp@mailpoet.com');
    $i->dontSee('wp@example.com');
    $i->waitForText('My Tag');
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Send confirmation email');

    $maxConfirmationsEmail = 'disallowed@example.com';
    $allowedEmail = 'allowed@example.com';

    $subscriberResendDisallowed = (new Subscriber())
      ->withEmail($maxConfirmationsEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $subscriberResendAllowed = (new Subscriber())
      ->withEmail($allowedEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->waitForText($maxConfirmationsEmail);
    $i->moveMouseOver(['xpath' => '//*[text()="' . $maxConfirmationsEmail . '"]//ancestor::tr']);
    // Admins can resend confirmation emails even when the subscriber reached the limit
    $i->see('Resend confirmation email', '//*[text()="' . $maxConfirmationsEmail . '"]//ancestor::tr');

    $i->clickItemRowActionByItemName($allowedEmail, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->checkEmailWasReceived('Confirm your subscription');
  }

  public function bulkUnsubscribe(\AcceptanceTester $i) {
    $i->wantTo('Unsubscribe subscribers using a bulk action');
    $i->wantTo('Setup data');
    $subscriber1 = (new Subscriber())
      ->withEmail('subscriber1@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('subscriber2@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('subscriber3@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber4 = (new Subscriber())
      ->withEmail('subscriber4@example.com')
      ->withStatus('subscribed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->wantTo('Select first two subscribers and unsubscribe them');
    $i->waitForText('subscriber1@example.com');
    $i->click("[data-automation-id='listing-row-checkbox-{$subscriber1->getId()}']");
    $i->click("[data-automation-id='listing-row-checkbox-{$subscriber2->getId()}']");

    $i->waitForElement("[data-automation-id='action-unsubscribe']");
    $i->click("[data-automation-id='action-unsubscribe']");

    $i->wantTo('Confirm the action in the modal window');
    $i->waitForElement("[data-automation-id='bulk-unsubscribe-confirm']");
    $i->click("[data-automation-id='bulk-unsubscribe-confirm']");

    $i->wantTo('Check the final status');
    $i->waitForText('subscriber2@example.com');
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_{$subscriber1->getId()}']");
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_{$subscriber2->getId()}']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_{$subscriber3->getId()}']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_{$subscriber4->getId()}']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_{$subscriber3->getId()}']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_{$subscriber4->getId()}']");
  }
}
