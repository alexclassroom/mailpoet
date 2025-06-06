<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNewsletterCest {
  public function moveNewsletterToTrash(\AcceptanceTester $i) {
    $i->wantTo('Move a newsletter to trash');
    $newsletterName = 'Trash Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $newsletter->withSubject($newsletterName . '2')->create();
    $newsletter->withSubject($newsletterName . '3')->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->waitForNoticeAndClose('1 email was moved to the trash.');
    $i->waitForListingItemsToLoad();
    $i->selectAllListingItems();
    $i->waitForText('Move to trash', 10, '.mailpoet-listing-bulk-actions');
    $i->waitForElementClickable('[data-automation-id="action-trash"]');
    $i->click('[data-automation-id="action-trash"]');
    $i->waitForNoticeAndClose('2 emails were moved to the trash.', 20);
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
  }

  public function restoreNewsletterFromTrash(\AcceptanceTester $i) {
    $i->wantTo('Restore a newsletter from trash');
    $newsletterName = 'Restore Trashed Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '3')->withDeleted()->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->waitForText('1 email has been restored from the Trash.');
    $i->waitForListingItemsToLoad();
    $i->selectAllListingItems();
    $i->waitForText('Restore');
    $i->waitForElementClickable('[data-automation-id="action-restore"]');
    $i->click('[data-automation-id="action-restore"]');
    $i->waitForText('2 emails have been restored from the Trash.', 20);
    $i->changeGroupInListingFilter('all');
    $i->waitForText($newsletterName);
  }

  public function deleteNewsletterPermanently(\AcceptanceTester $i) {
    $i->wantTo('Forever delete a newsletter');
    $newsletterName = 'Goodbye Forever Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '3')->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '4')->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Delete permanently');
    $i->waitForText('1 email was permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->waitForText($newsletterName . '2');
    $i->waitForText($newsletterName . '3');
    $i->selectAllListingItems();
    $i->click('Delete permanently');
    $i->waitForText('2 emails were permanently deleted.');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($newsletterName . '4');
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $i->wantTo('Empty a trash on Newsletters page');
    $newsletterName = 'Goodbye Forever Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '3')->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->click('[data-automation-id="empty_trash"]');
    $i->waitForText('2 emails were permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->changeGroupInListingFilter('all');
    $i->waitForText($newsletterName . '3');
  }

  public function selectAllAvailableNewslettersAndDelete(\AcceptanceTester $i) {
    $i->wantTo('Select all available newsletters and proceed with deletion');
    $newsletterName = 'Sample Newsletter';
    $newsletter = new Newsletter();
    for ($itemCount = 1; $itemCount <= 22; $itemCount++) {
      $newsletter->withSubject($newsletterName . $itemCount)->withSentStatus()->create();
    }
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->selectAllListingItems();
    $i->waitForText('All items on this page are selected.');
    $i->click('Select all items on all pages');
    $i->waitForText('All 22 items are selected.');
    $i->waitForElementVisible('[data-automation-id="action-trash"]');
    $i->click('[data-automation-id="action-trash"]');
    $i->waitForText('22 emails were moved to the trash.');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterName);
    $i->selectAllListingItems();
    $i->waitForText('All items on this page are selected.');
    $i->click('Select all items on all pages');
    $i->waitForText('All 22 items are selected.');
    $i->click('Delete permanently');
    $i->waitForText('22 emails were permanently deleted.');
  }
}
