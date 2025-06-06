<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * @group woo
 */
class ManageWooCommerceSegmentsCest {
  public function _before(\AcceptanceTester $i) {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
    $i->activateWooCommerce();
  }

  public function _after(\AcceptanceTester $i) {
    $i->deactivateWooCommerce();
  }

  public function createAndEditWooCommercePurchasedInCategorySegment(\AcceptanceTester $i) {
    $productFactory = new WooCommerceProduct($i);
    $category1Id = $productFactory->createCategory('Category 1');
    $category2Id = $productFactory->createCategory('Category 2');
    $category3Id = $productFactory->createCategory('Category 3');
    $productFactory->withCategoryIds([$category1Id, $category2Id, $category3Id])->create();
    $categorySelectElement = '[data-automation-id="select-segment-category"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $operatorSelectElement = '[data-automation-id="select-operator"]';
    $segmentNameField = '[data-automation-id="input-name"]';
    $segmentDescriptionField = '[data-automation-id="input-description"]';

    $i->wantTo('Create a new WooCommerce purchased in category segment');
    $segmentTitle = 'Segment Woo Category Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($segmentNameField, $segmentTitle);
    $i->fillField($segmentDescriptionField, $segmentDesc);
    $i->selectOptionInReactSelect('purchased in category', $actionSelectElement);
    $i->waitForElement($categorySelectElement);
    $i->selectOptionInReactSelect('Category 2', $categorySelectElement);
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($categorySelectElement);
    $i->seeInField($segmentNameField, $segmentTitle);
    $i->seeInField($segmentDescriptionField, $segmentDesc);
    $i->see('purchased in category', $actionSelectElement);
    $i->see('Category 2', $categorySelectElement);
    $i->seeOptionIsSelected($operatorSelectElement, 'any of'); // default value should be selected

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Category Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->clearFormField($segmentNameField);
    $i->clearFormField($segmentDescriptionField);
    $i->waitForElementVisible('input[value=""]' . $segmentNameField);
    $i->waitForElementVisible($segmentDescriptionField . ':empty');
    $i->fillField($segmentNameField, $editedTitle);
    $i->fillField($segmentDescriptionField, $editedDesc);
    $i->selectOptionInReactSelect('Category 1', $categorySelectElement);
    $i->selectOptionInReactSelect('Category 3', $categorySelectElement);
    $i->selectOption($operatorSelectElement, 'none of');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($editedTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($categorySelectElement);
    $i->seeInField($segmentNameField, $editedTitle);
    $i->seeInField($segmentDescriptionField, $editedDesc);
    $i->see('purchased in category', $actionSelectElement);
    $i->see('Category 1', $categorySelectElement);
    $i->see('Category 3', $categorySelectElement);
    $i->seeOptionIsSelected($operatorSelectElement, 'none of');
  }

  public function createAndEditWooCommercePurchasedProductSegment(\AcceptanceTester $i) {
    $productFactory = new WooCommerceProduct($i);
    $productFactory->withName('Product 1')->create();
    $productFactory->withName('Product 2')->create();
    $productFactory->withName('Product 3')->create();
    $segmentNameField = '[data-automation-id="input-name"]';
    $segmentDescriptionField = '[data-automation-id="input-description"]';
    $productSelectElement = '[data-automation-id="select-segment-products"]';
    $operatorSelectElement = '[data-automation-id="select-operator"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';

    $i->wantTo('Create a new WooCommerce purchased product segment');
    $segmentTitle = 'Segment Woo Product Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($segmentNameField, $segmentTitle);
    $i->fillField($segmentDescriptionField, $segmentDesc);
    $i->selectOptionInReactSelect('purchased product', $actionSelectElement);
    $i->selectOption($operatorSelectElement, 'all of');
    $i->waitForElement($productSelectElement);
    $i->selectOptionInReactSelect('Product 2', $productSelectElement);
    $i->selectOptionInReactSelect('Product 3', $productSelectElement);
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($productSelectElement);
    $i->seeInField($segmentNameField, $segmentTitle);
    $i->seeInField($segmentDescriptionField, $segmentDesc);
    $i->see('purchased product', $actionSelectElement);
    $i->seeOptionIsSelected($operatorSelectElement, 'all of');
    $i->see('Product 2', $productSelectElement);
    $i->see('Product 3', $productSelectElement);

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Product Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->clearFormField($segmentNameField);
    $i->clearFormField($segmentDescriptionField);
    $i->waitForElementVisible('input[value=""]' . $segmentNameField);
    $i->waitForElementVisible($segmentDescriptionField . ':empty');
    $i->fillField($segmentNameField, $editedTitle);
    $i->fillField($segmentDescriptionField, $editedDesc);
    $i->selectOption($operatorSelectElement, 'none of');
    $i->selectOptionInReactSelect('Product 1', $productSelectElement);
    $i->click('[aria-label="Remove Product 3"]');
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($editedTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($productSelectElement);
    $i->seeInField($segmentNameField, $editedTitle);
    $i->seeInField($segmentDescriptionField, $editedDesc);
    $i->see('purchased product', $actionSelectElement);
    $i->seeOptionIsSelected($operatorSelectElement, 'none of');
    $i->see('Product 1', $productSelectElement);
    $i->see('Product 2', $productSelectElement);
    $i->dontSee('Product 3', $productSelectElement);
  }

  public function createAndEditWooCommerceNumberOfOrdersSegment(\AcceptanceTester $i) {
    $segmentNameField = '[data-automation-id="input-name"]';
    $segmentDescriptionField = '[data-automation-id="input-description"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $numberOfOrdersTypeElement = '[data-automation-id="select-number-of-orders-type"]';
    $numberOfOrdersCountElement = '[data-automation-id="input-number-of-orders-count"]';
    $numberOfOrdersDaysElement = '[data-automation-id="segment-number-of-days"]';

    $i->wantTo('Create a new WooCommerce Number of Orders segment');
    $segmentTitle = 'Segment Woo Number of Orders Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($segmentNameField, $segmentTitle);
    $i->fillField($segmentDescriptionField, $segmentDesc);
    $i->selectOptionInReactSelect('number of orders', $actionSelectElement);
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->selectOption($numberOfOrdersTypeElement, '>');
    $i->fillField($numberOfOrdersCountElement, 2);
    $i->fillField($numberOfOrdersDaysElement, 10);

    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->seeInField($segmentNameField, $segmentTitle);
    $i->seeInField($segmentDescriptionField, $segmentDesc);
    $i->see('number of orders', $actionSelectElement);
    $i->see('more than', $numberOfOrdersTypeElement);
    $i->seeInField($numberOfOrdersCountElement, '2');
    $i->seeInField($numberOfOrdersDaysElement, '10');

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Number of Orders Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->clearFormField($numberOfOrdersCountElement);
    $i->clearFormField($numberOfOrdersDaysElement);
    $i->clearFormField($segmentNameField);
    $i->clearFormField($segmentDescriptionField);
    $i->waitForElementVisible('input[value=""]' . $segmentNameField);
    $i->waitForElementVisible($segmentDescriptionField . ':empty');
    $i->fillField($segmentNameField, $editedTitle);
    $i->fillField($segmentDescriptionField, $editedDesc);
    $i->waitForElementVisible('input[value=""]' . $numberOfOrdersCountElement);
    $i->waitForElementVisible('input[value=""]' . $numberOfOrdersDaysElement);
    $i->selectOption($numberOfOrdersTypeElement, '=');
    $i->fillField($numberOfOrdersCountElement, 4);
    $i->fillField($numberOfOrdersDaysElement, 20);
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($editedTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($numberOfOrdersTypeElement);
    $i->seeInField($segmentNameField, $editedTitle);
    $i->seeInField($segmentDescriptionField, $editedDesc);
    $i->see('number of orders', $actionSelectElement);
    $i->see('equals', $numberOfOrdersTypeElement);
    $i->seeInField($numberOfOrdersCountElement, '4');
    $i->seeInField($numberOfOrdersDaysElement, '20');
  }

  public function createAndEditWooCommerceTotalSpentSegment(\AcceptanceTester $i) {
    $segmentNameField = '[data-automation-id="input-name"]';
    $segmentDescriptionField = '[data-automation-id="input-description"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $totalSpentTypeElement = '[data-automation-id="select-total-spent-type"]';
    $totalSpentAmountElement = '[data-automation-id="input-total-spent-amount"]';
    $totalSpentDaysElement = '[data-automation-id="segment-number-of-days"]';

    $i->wantTo('Create a new WooCommerce Total Spent segment');
    $segmentTitle = 'Segment Woo Total Spent Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($segmentNameField, $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('total spent', $actionSelectElement);
    $i->waitForElement($totalSpentTypeElement);
    $i->selectOption($totalSpentTypeElement, '>');
    $i->fillField($totalSpentAmountElement, 2);
    $i->fillField($totalSpentDaysElement, 10);

    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($totalSpentTypeElement);
    $i->seeInField($segmentNameField, $segmentTitle);
    $i->seeInField($segmentDescriptionField, $segmentDesc);
    $i->see('total spent', $actionSelectElement);
    $i->see('more than', $totalSpentTypeElement);
    $i->seeInField($totalSpentAmountElement, '2');
    $i->seeInField($totalSpentDaysElement, '10');

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Total Spent Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->clearFormField($totalSpentAmountElement);
    $i->clearFormField($totalSpentDaysElement);
    $i->clearFormField($segmentNameField);
    $i->clearFormField($segmentDescriptionField);
    $i->waitForElementVisible('input[value=""]' . $segmentNameField);
    $i->waitForElementVisible($segmentDescriptionField . ':empty');
    $i->fillField($segmentNameField, $editedTitle);
    $i->fillField($segmentDescriptionField, $editedDesc);
    $i->waitForElementVisible('input[value=""]' . $totalSpentAmountElement);
    $i->waitForElementVisible('input[value=""]' . $totalSpentDaysElement);
    $i->selectOption($totalSpentTypeElement, '<');
    $i->fillField($totalSpentAmountElement, 4);
    $i->fillField($totalSpentDaysElement, 20);
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($editedTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($totalSpentTypeElement);
    $i->seeInField($segmentNameField, $editedTitle);
    $i->seeInField($segmentDescriptionField, $editedDesc);
    $i->see('total spent', $actionSelectElement);
    $i->see('less than', $totalSpentTypeElement);
    $i->seeInField($totalSpentAmountElement, '4');
    $i->seeInField($totalSpentDaysElement, '20');
  }

  public function createAndEditWooCommerceSingleOrderValueSegment(\AcceptanceTester $i) {
    $segmentNameField = '[data-automation-id="input-name"]';
    $segmentDescriptionField = '[data-automation-id="input-description"]';
    $actionSelectElement = '[data-automation-id="select-segment-action"]';
    $singleOrderValueTypeElement = '[data-automation-id="select-single-order-value-type"]';
    $singleOrderValueAmountElement = '[data-automation-id="input-single-order-value-amount"]';
    $singleOrderValueDaysElement = '[data-automation-id="segment-number-of-days"]';

    $i->wantTo('Create a new WooCommerce Single Order Value segment');
    $segmentTitle = 'Segment Single Order Value Test';
    $segmentDesc = 'Segment description';
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField($segmentNameField, $segmentTitle);
    $i->fillField(['name' => 'description'], $segmentDesc);
    $i->selectOptionInReactSelect('single order value', $actionSelectElement);
    $i->waitForElement($singleOrderValueTypeElement);
    $i->selectOption($singleOrderValueTypeElement, '>');
    $i->fillField($singleOrderValueAmountElement, 2);
    $i->fillField($singleOrderValueDaysElement, 10);

    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($segmentTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($singleOrderValueTypeElement);
    $i->seeInField($segmentNameField, $segmentTitle);
    $i->seeInField($segmentDescriptionField, $segmentDesc);
    $i->see('single order value', $actionSelectElement);
    $i->see('more than', $singleOrderValueTypeElement);
    $i->seeInField($singleOrderValueAmountElement, '2');
    $i->seeInField($singleOrderValueDaysElement, '10');

    $i->wantTo('Edit segment and save');
    $editedTitle = 'Segment Woo Single Order Value Test Edited';
    $editedDesc = 'Segment description Edited';
    $i->clearFormField($singleOrderValueAmountElement);
    $i->clearFormField($singleOrderValueDaysElement);
    $i->clearFormField($segmentNameField);
    $i->clearFormField($segmentDescriptionField);
    $i->waitForElementVisible('input[value=""]' . $segmentNameField);
    $i->waitForElementVisible($segmentDescriptionField . ':empty');
    $i->fillField($segmentNameField, $editedTitle);
    $i->fillField($segmentDescriptionField, $editedDesc);
    $i->waitForElementVisible('input[value=""]' . $singleOrderValueAmountElement);
    $i->waitForElementVisible('input[value=""]' . $singleOrderValueDaysElement);
    $i->selectOption($singleOrderValueTypeElement, '<');
    $i->fillField($singleOrderValueAmountElement, 4);
    $i->fillField($singleOrderValueDaysElement, 20);
    $i->waitForElementClickable('button[type="submit"]');
    $i->click('Save');
    $i->waitForText($editedTitle);
    $i->seeNoJSErrors();

    $i->wantTo('Open edit form and check that all values were saved correctly');
    $i->clickWooTableActionByItemName($editedTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElement($singleOrderValueTypeElement);
    $i->seeInField($segmentNameField, $editedTitle);
    $i->seeInField($segmentDescriptionField, $editedDesc);
    $i->see('single order value', $actionSelectElement);
    $i->see('less than', $singleOrderValueTypeElement);
    $i->seeInField($singleOrderValueAmountElement, '4');
    $i->seeInField($singleOrderValueDaysElement, '20');
  }
}
