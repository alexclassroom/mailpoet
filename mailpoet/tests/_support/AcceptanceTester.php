<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

use Codeception\Util\Locator;
use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Cache\TransientCache;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\FormMessageController;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Assert;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration
class AcceptanceTester extends \Codeception\Actor {
  use _generated\AcceptanceTesterActions {
    cli as _cli;
    cliToArray as _cliToArray;
    cliToString as _cliToString;
    click as _click;
    switchToNextTab as _switchToNextTab;
    waitForElement as _waitForElement;
    waitForElementChange as _waitForElementChange;
    waitForElementClickable as _waitForElementClickable;
    waitForElementNotVisible as _waitForElementNotVisible;
    waitForElementVisible as _waitForElementVisible;
    waitForJS as _waitForJS;
    waitForText as _waitForText;
  }

  const WP_DOMAIN = 'test.local';
  const WP_URL = 'http://' . self::WP_DOMAIN;
  const MAIL_URL = 'http://mailhog:8025';
  const AUTHORIZED_SENDING_EMAIL = 'staff@mailpoet.com';
  const LISTING_LOADING_SELECTOR = '.mailpoet-listing-loading';
  const WOO_COMMERCE_PLUGIN = 'woocommerce';
  const WOO_COMMERCE_BLOCKS_PLUGIN = 'woo-gutenberg-products-block';
  const WOO_COMMERCE_MEMBERSHIPS_PLUGIN = 'woocommerce-memberships';
  const WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN = 'woocommerce-subscriptions';
  const AUTOMATE_WOO_PLUGIN = 'automatewoo';
  const MAILHOG_DATA_PATH = '/mailhog-data';
  const ADMIN_EMAIL = 'test@test.com';
  const EMAIL_EDITOR_MINIMAL_WP_VERSION = '6.7';

  /**
   * Define custom actions here
   */
  public function login() {
    $i = $this;
    $i->amOnPage('/wp-login.php');
    if ($i->loadSessionSnapshot('login')) {
      return;
    }

    // remove any other WP auth & login cookies to avoid login/logout errors
    $authCookies = $i->grabCookiesWithPattern('/^wordpress_[a-z0-9]{32}$/') ?: [];
    $loginCookies = $i->grabCookiesWithPattern('/^wordpress_logged_in_[a-z0-9]{32}$/') ?: [];
    foreach (array_merge($authCookies, $loginCookies) as $cookie) {
      $i->resetCookie($cookie->getName());
    }

    $i->wait(1);// this needs to be here, Username is not filled properly without this line
    $i->fillField('Username', 'admin');
    $i->fillField('Password', 'password');
    $i->click('Log In');
    $i->waitForText('MailPoet', 10);
    $i->saveSessionSnapshot('login');
  }

  /**
   * Define custom actions here
   */
  public function logOut() {
    $i = $this;
    $i->amOnPage('/wp-login.php?action=logout');
    $i->click('log out');
    $i->waitForText('You are now logged out.');
    $i->deleteSessionSnapshot('login');
  }

  /**
   * Navigate to the specified MailPoet page in the admin.
   *
   * @param string $page The page to visit e.g. Inbox or Status
   */
  public function amOnMailpoetPage($page) {
    $i = $this;
    if ($page === 'Emails') {
      $path = 'newsletters';
    } else {
      $path = strtolower($page);
    }
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-$path");
  }

  /**
   * Navigate to Mailhog page and wait for angular to load
   */
  public function amOnMailboxAppPage() {
    $i = $this;
    $i->amOnUrl(self::MAIL_URL);
    // ensure that angular is loaded by checking angular specific class
    $i->waitForElement('.messages.ng-scope');
  }

  /**
   * Define custom actions here
   */
  public function amOnSiteHomepage() {
    $i = $this;
    $i->amOnUrl(self::WP_URL);
  }

  /**
   * Clear the Mailbox so it's empty
   */
  public function emptyMailbox() {
    exec('rm -rf ' . self::MAILHOG_DATA_PATH . '/*', $output);
  }

  public function clickItemRowActionByItemName($itemName, $link) {
    $i = $this;
    for ($x = 1; $x <= 3; $x++) {
      try {
        $itemNameCellXpath = ['xpath' => '//tr//*[text()="' . $itemName . '"]//ancestor::td'];
        $linkXpath = ['xpath' => '//*[text()="' . $itemName . '"]//ancestor::td//a[text()="' . $link . '"]'];
        $i->moveMouseOver($itemNameCellXpath);
        $i->waitForElementClickable($linkXpath, 3);
        $i->click($linkXpath);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
  }

  public function clickWooTableActionByItemName($itemName, $actionLinkText) {
    $i = $this;
    $xpath = ['xpath' => '//tr[.//a[text()="' . $itemName . '"]]//a[text()="' . $actionLinkText . '"]'];
    $i->waitForElementVisible($xpath);
    $i->waitForElementClickable($xpath);
    $i->moveMouseOver($xpath);
    $i->click($xpath);
  }

  public function clickWooTableMoreButtonByItemName($itemName) {
    $i = $this;
    $xpath = ['xpath' => '//tr[.//a[text()="' . $itemName . '"]]//div[contains(@class, "mailpoet-listing-more-button")]'];
    $i->waitForElementClickable($xpath);
    $i->click($xpath);
  }

  public function clickWooTableActionInsideMoreButton(string $itemName, string $moreButtonAction, ?string $confirmAction = null) {
    $i = $this;
    $i->clickWooTableMoreButtonByItemName($itemName);
    $i->waitForText($itemName);
    $i->click($moreButtonAction);
    if ($confirmAction) {
      $i->click($confirmAction);
    }
  }

  /**
   * Select a value from select2 input field.
   * For multiple selection the element is textarea.select2-search__field (default),
   * for single selection specify the input.select2-search__field element.
   *
   * @param string $value
   * @param string $element
   */
  public function selectOptionInSelect2($value, $element = 'textarea.select2-search__field') {
    $i = $this;
    for ($x = 1; $x <= 3; $x++) {
      try {
        $i->waitForElement($element);
        $i->fillField($element, $value);
        $optionsContainer = $i->grabAttributeFrom($element, 'aria-controls');
        Assert::assertIsString($optionsContainer);
        // Wait until the searched value is in select options. There might be some delay on API
        $i->waitForText($value, 5, "#$optionsContainer");
        $i->pressKey($element, WebDriverKeys::ENTER);
        $i->seeSelectedInSelect2($value);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
    $i->seeSelectedInSelect2($value);
  }

  /**
   * Check selected value in select2..
   *
   * @param string $value
   * @param string $element
   */
  public function seeSelectedInSelect2($value, $element = '.select2-container') {
    $i = $this;
    $i->waitForElement($element);
    $i->see($value, $element);
  }

  public function selectOptionInReactSelect($value, $selector) {
    $i = $this;
    $i->waitForElement($selector);
    $i->fillField($selector . ' input', $value);
    $i->pressKey($selector . ' input', WebDriverKeys::RETURN_KEY);
  }

  /**
   * Navigate to the editor for a newsletter.
   *
   * @param int|null $id
   */
  public function amEditingNewsletter($id) {
    $i = $this;
    if (is_null($id)) {
      throw new \Exception('No valid id passed');
    }
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $id);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
    $i->waitForElementNotVisible('.velocity-animating');
  }

  public function createFormAndSubscribe() {
    $i = $this;
    // create form
    $formName = 'Subscription Acceptance Test Form';
    $formFactory = new Form();
    $formId = $formFactory->withName($formName)->create()->getId();

    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test',
      'post_title' => 'Form Test',
      'post_content' => '
        Regular form:
          [mailpoet_form id="' . $formId . '"]
        Iframe form:
          <iframe class="mailpoet_form_iframe" id="mailpoet_form_iframe" tabindex="0" src="http://test.local?mailpoet_form_iframe=1" width="100%" height="100%" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe>
      ',
      'post_status' => 'publish',
    ]);

    $i->amOnPage('/form-test');
    $i->waitForElement('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $messageController = ContainerWrapper::getInstance()->get(FormMessageController::class);
    $i->waitForText($messageController->getDefaultSuccessMessage(), 30, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  public function selectAllListingItems() {
    $i = $this;
    $i->waitForElementVisible('[data-automation-id="select_all"]');
    $i->click('[data-automation-id="select_all"]');
  }

  public function waitForListingItemsToLoad() {
    $i = $this;
    $i->waitForElementNotVisible('.mailpoet-listing-loading');
  }

  public function waitForEmailSendingOrSent() {
    $i = $this;
    $i->waitForElement('.mailpoet-listing-status:not(.mailpoet-listing-status-unknown)', 30);
  }

  public function clickLabelWithInput($inputName, $inputValue) {
    $i = $this;
    $i->click("//*[name()='label'][.//*[name()='input'][@name='{$inputName}'][@value='{$inputValue}']]");
  }

  public function clickToggleYes($yesNoCSSSelector) {
    $i = $this;
    $i->click($yesNoCSSSelector . ' .mailpoet-form-yesno-yes');
  }

  public function clickToggleNo($yesNoCSSSelector) {
    $i = $this;
    $i->click($yesNoCSSSelector . ' .mailpoet-form-yesno-no');
  }

  public function assertAttributeContains($selector, $attribute, $contains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    verify($attributeValue)->stringContainsString($contains);
  }

  public function assertCssProperty($cssSelector, $cssProperty, $value) {
    $i = $this;
    $attributeValue = $i->executeInSelenium(function (\Facebook\WebDriver\WebDriver $webdriver) use ($cssSelector, $cssProperty){
      return $webdriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($cssSelector))->getCSSValue($cssProperty);
    });
    verify($attributeValue)->equals($value);
  }

  public function assertAttributeNotContains($selector, $attribute, $notContains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    verify($attributeValue)->stringNotContainsString($notContains);
  }

  public function searchFor($query, $element = '#search_input') {
    $i = $this;
    $i->waitForElement($element);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
    $i->clearField($element);
    $i->fillField($element, $query);
    $i->pressKey($element, WebDriverKeys::ENTER);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
  }

  public function createListWithSubscriber() {
    $segmentFactory = new Segment();
    $segmentName = 'List ' . \MailPoet\Util\Security::generateRandomString();
    $segment = $segmentFactory->withName($segmentName)->create();

    $subscriberFactory = new Subscriber();
    $subscriberEmail = \MailPoet\Util\Security::generateRandomString() . '@domain.com';
    $subscriberFirstName = 'John';
    $subscriberLastName = 'Doe';
    $subscriberFactory->withSegments([$segment])
      ->withEmail($subscriberEmail)
      ->withFirstName($subscriberFirstName)
      ->withLastName($subscriberLastName)
      ->create();

    return $segmentName;
  }

  public function switchToNextTab($offset = 1) {
    // Try switching multiple times. Sometimes we get an exception and maybe the tab is not ready.
    for ($x = 1; $x <= 3; $x++) {
      try {
        $this->_switchToNextTab($offset);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
    // workaround for frozen tabs when opened by clicking on links
    $this->wait(1);
  }

  public function click($link, $context = null) {
    // retry click in case of "element click intercepted... Other element would receive the click" error
    $retries = 3;
    while (true) {
      try {
        $retries--;
        $this->_click($link, $context);
        break;
      } catch (WebDriverException $e) {
        if ($retries > 0 && preg_match('(element click intercepted|element not interactable)', $e->getMessage()) === 1) {
          $this->wait(0.2);
          continue;
        }
        throw $e;
      }
    }
  }

  /**
   * Override waitFor* methods to have a common default timeout
   */
  public function waitForElement($element, $timeout = 10) {
    return $this->_waitForElement($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementChange($element, \Closure $callback, $timeout = 30) {
    return $this->_waitForElementChange($element, $callback, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementClickable($element, $timeout = 10) {
    return $this->_waitForElementClickable($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementNotVisible($element, $timeout = 10) {
    return $this->_waitForElementNotVisible($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementVisible($element, $timeout = 10) {
    return $this->_waitForElementVisible($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForJS($script, $timeout = 5) {
    return $this->_waitForJS($script, $this->getDefaultTimeout($timeout));
  }

  public function waitForText($text, $timeout = 10, $selector = null) {
    return $this->_waitForText($text, $this->getDefaultTimeout($timeout), $selector);
  }

  public function waitForNoticeAndClose($text, $timeout = 10, $selector = null) {
    $this->_waitForText($text, $this->getDefaultTimeout($timeout), $selector);
    $this->waitForElementVisible('.notice-dismiss', 1);
    $this->click('.notice-dismiss');
  }

  public function closeNoticeIfVisible() {
    // Dismiss notice if it is visible. This is needed especially for the tests with the lowest supported PHP version
    // because we display a warning about the minimum required PHP version.
    $noticeIsVisible = $this->executeJS('return document.getElementsByClassName("notice-dismiss")');
    if ($noticeIsVisible) {
      $this->click('.notice-dismiss');
      $this->waitForElementNotVisible('.notice-dismiss', 3);
    }
  }

  public function scrollToTop() {
    return $this->scrollTo('#wpcontent');
  }

  private function getDefaultTimeout($timeout) {
    return (int)getenv('WAIT_TIMEOUT') ?: $timeout;
  }

  public function activateWooCommerce() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_PLUGIN]);
    $i->cli(['option', 'update', 'woocommerce_coming_soon', 'no']);
  }

  public function deactivateWooCommerce() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_PLUGIN]);
  }

  public function activateWooCommerceMemberships() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_MEMBERSHIPS_PLUGIN]);
  }

  public function deactivateWooCommerceMemberships() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_MEMBERSHIPS_PLUGIN]);
  }

  public function activateWooCommerceSubscriptions() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN]);
  }

  public function deactivateWooCommerceSubscriptions() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN]);
  }

  public function activateAutomateWoo() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::AUTOMATE_WOO_PLUGIN]);
  }

  public function deactivateAutomateWoo() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::AUTOMATE_WOO_PLUGIN]);
  }

  public function deactivateMailpoetFreeFromPluginPage(): void {
    $i = $this;
    $i->amOnPluginsPage();
    if ($i->checkPluginIsActive('mailpoet-premium/mailpoet-premium.php')) {
      $i->click('#deactivate-mailpoet-premium');
      $i->waitForElementClickable('#deactivate-mailpoet');
    }
    $i->click('#deactivate-mailpoet');
    $i->wantTo('Close the poll about MailPoet deactivation.');
    $i->pressKey('body', WebDriverKeys::ESCAPE);
    $i->waitForText('Plugin deactivated.');
  }

  public function checkPluginIsActive(string $plugin): bool {
    $i = $this;
    $activePlugins = $i->grabOptionFromDatabase('active_plugins', true);
    Assert::assertIsArray($activePlugins);
    return in_array($plugin, $activePlugins);
  }

  public function getWooCommerceVersion(): string {
    $i = $this;
    return $i->cliToString(['plugin', 'get', self::WOO_COMMERCE_PLUGIN, '--field=version']);
  }

  public function getWooCommerceBlocksVersion(): string {
    $i = $this;
    return $i->cliToString(['plugin', 'get', self::WOO_COMMERCE_BLOCKS_PLUGIN, '--field=version']);
  }

  public function getWordPressVersion(): string {
    $i = $this;
    $version = $i->cliToString(['core', 'version']);
    // Clean version from beta and RC strings
    return preg_replace('/[- ]?(beta|RC|alpha)[0-9]*/i', '', $version);
  }

  public function orderProductWithoutRegistration(array $product, $userEmail, $doSubscribe = true) {
    $this->orderProduct($product, $userEmail, false, $doSubscribe);
  }

  public function orderProductWithRegistration(array $product, $userEmail, $doSubscribe = true) {
    $this->orderProduct($product, $userEmail, true, $doSubscribe);
  }

  /**
   * Order a product and create an account within the order process
   * We are also checking the WooCommerce version and if it has new or old checkout experience
   */
  public function orderProduct(array $product, $userEmail, $doRegister = true, $doSubscribe = true) {
    $i = $this;
    // Reset WooCommerce session cookies to avoid conflicts with previous tests
    $wcSessionCookies = $i->grabCookiesWithPattern('/^wp_woocommerce_session_[a-z0-9]+$/') ?: [];
    foreach ($wcSessionCookies as $cookie) {
      $i->resetCookie($cookie->getName());
    }

    $i->amOnPage('checkout/?add-to-cart=' . $product['id']);
    $i->fillCustomerInfo($userEmail);

    $wooCommerceVersion = $i->getWooCommerceVersion();

    if ($doSubscribe) {
      if (version_compare($wooCommerceVersion, '8.3.0', '>=')) {
        $settings = (ContainerWrapper::getInstance())->get(SettingsController::class);
        $i->click(Locator::contains('label', $settings->get('woocommerce.optin_on_checkout.message')));
      } else {
        $isCheckboxVisible = $i->executeJS('return document.getElementById("mailpoet_woocommerce_checkout_optin")');
        if ($isCheckboxVisible) {
          $i->checkOption('#mailpoet_woocommerce_checkout_optin');
        }
      }
    } else {
      if (version_compare($wooCommerceVersion, '8.3.0', '<')) {
        $isCheckboxVisible = $i->executeJS('return document.getElementById("mailpoet_woocommerce_checkout_optin")');
        if ($isCheckboxVisible) {
          $i->uncheckOption('#mailpoet_woocommerce_checkout_optin');
        }
      }
    }
    if ($doRegister) {
      $i->optInForRegistration();
    }
    $i->selectPaymentMethod();
    $i->placeOrder();
    if ($doRegister) {
      $i->logOut();
    }
    // ensure action scheduler jobs are done
    $i->triggerMailPoetActionScheduler();
  }

  /**
   * WooCommerce ordering process methods, should be used sequentially
   */

  /**
   * Add a product to cart
   */
  public function addProductToCart(array $product) {
    $i = $this;
    $i->amOnPage('product/' . $product['slug']);
    $i->click('Add to cart');
    $i->waitForText("“{$product['name']}” has been added to your cart.");
  }

  /**
   * Go to the shortcode checkout page
   */
  public function goToShortcodeCheckout() {
    $i = $this;
    $i->amOnPage('shortcode-checkout');
  }

  /**
   * Go to the block checkout page
   */
  public function goToBlockCheckout() {
    $i = $this;
    $i->amOnPage('checkout');
  }

  /**
   * Fill the customer info
   * We are also checking the WooCommerce version and if it has new or old checkout experience
   */
  public function fillCustomerInfo($userEmail) {
    $i = $this;
    $wooCommerceVersion = $i->getWooCommerceVersion();
    if (version_compare($wooCommerceVersion, '8.3.0', '>=')) {
      $i->fillField('#billing-first_name', 'John');
      $i->fillField('#billing-last_name', 'Doe');
      $i->fillField('#billing-address_1', 'Address 1');
      $i->fillField('#billing-city', 'Paris');
      $i->fillField('#email', $userEmail);
      $i->fillField('#billing-postcode', '75000');
      $i->fillField('#billing-phone', '0555666777');
    } else {
      $i->fillField('billing_first_name', 'John');
      $i->fillField('billing_last_name', 'Doe');
      $i->fillField('billing_address_1', 'Address 1');
      $i->fillField('billing_city', 'Paris');
      $i->fillField('billing_email', $userEmail);
      $i->fillField('billing_postcode', '75000');
      $i->fillField('billing_phone', '123456');
    }
  }

  /**
   * Check the option for creating an account
   * We are also checking the WooCommerce version and if it has new or old checkout experience
   */
  public function optInForRegistration() {
    $i = $this;
    $wooCommerceVersion = $i->getWooCommerceVersion();
    if (version_compare($wooCommerceVersion, '8.3.0', '>=')) {
      $isCheckboxVisible = $i->executeJS('return document.getElementsByClassName("wc-block-checkout__create-account")');
      if ($isCheckboxVisible) {
        $i->click(Locator::contains('label', 'Create an account'));
      }
    } else {
      $isCheckboxVisible = $i->executeJS('return document.getElementById("createaccount")');
      if ($isCheckboxVisible) {
        $i->checkOption('#createaccount');
      }
    }
  }

  /**
   * Check the option for subscribing to the WC list
   */
  public function optInForSubscription() {
    $settings = (ContainerWrapper::getInstance())->get(SettingsController::class);
    $i = $this;
    $isCheckboxVisible = $i->executeJS('return document.getElementById("checkbox-control-0")');
    if ($isCheckboxVisible) {
      $i->click(Locator::contains('label', $settings->get('woocommerce.optin_on_checkout.message')));
    }
  }

  /**
   * Uncheck the option for subscribing to the WC list
   */
  public function optOutOfSubscription() {
    $settings = (ContainerWrapper::getInstance())->get(SettingsController::class);
    $i = $this;
    $isCheckboxVisible = $i->executeJS('return document.getElementById("checkbox-control-0")');
    if ($isCheckboxVisible) {
      $i->click(Locator::contains('label', $settings->get('woocommerce.optin_on_checkout.message')));
    }
  }

  /**
   * Select a payment method (cheque, cod, ppec_paypal)
   * We are also checking the WooCommerce version and if it has new or old checkout experience
   */
  public function selectPaymentMethod($method = 'cod') {
    $i = $this;
    $wooCommerceVersion = $i->getWooCommerceVersion();
    if (version_compare($wooCommerceVersion, '8.3.0', '>=')) {
      // We need to scroll with some negative offset so that the input is not hidden above the top page fold
      $approximatePaymentMethodInputHeight = 40;
      $i->waitForElementNotVisible('.blockOverlay', 30); // wait for payment method loading overlay to disappear
      $i->scrollTo('#radio-control-wc-payment-method-options-' . $method, 0, -$approximatePaymentMethodInputHeight);
      $i->click('label[for="radio-control-wc-payment-method-options-' . $method . '"]');
      $i->wait(0.5); // Wait for animation after selecting the method.
    } else {
      $approximatePaymentMethodInputHeight = 40;
      $i->waitForElementNotVisible('.blockOverlay', 30); // wait for payment method loading overlay to disappear
      $i->scrollTo('#payment_method_' . $method, 0, -$approximatePaymentMethodInputHeight);
      $i->click('label[for="payment_method_' . $method . '"]');
      $i->wait(0.5); // Wait for animation after selecting the method.
    }
  }

  /**
   * Place the order
   * We are also checking the WooCommerce version and if it has new or old checkout experience
   */
  public function placeOrder() {
    $i = $this;
    $wooCommerceVersion = $i->getWooCommerceVersion();
    if (version_compare($wooCommerceVersion, '8.3.0', '>=')) {
      // Add a note to order just to avoid flakiness due to race conditions
      $i->click(Locator::contains('label', 'Add a note to your order'));
      $i->fillField('.wc-block-components-textarea', 'This is a note');
      $i->waitForText('Place Order');
      $i->waitForElementClickable(Locator::contains('button', 'Place Order'));
      $i->click(Locator::contains('button', 'Place Order'));
      $i->waitForText('Thank you. Your order has been received.');
    } else {
      $i->waitForText('Place order');
      $i->click('Place order');
      $i->waitForText('Thank you. Your order has been received.');
    }
  }

  /**
   * Register a customer on my-account page
   * @param string $email
   * @param bool $optIn  Whether to check optin checkbox or not
   * @throws UnrecognizedExceptionException
   */
  public function registerCustomerOnMyAccountPage(string $email, $optIn = false) {
    $i = $this;
    $i->amOnPage('my-account');
    $i->fillField('Email address', $email);
    if ($optIn) {
      $i->checkOption('mailpoet[subscribe_on_register]');
    }
    $i->click('Register', '.woocommerce-form-register');
    $i->waitForText('From your account dashboard you can view your recent orders');
  }

  // Enforce WP-CLI to be called with array because:
  //  - It's recommended now (https://github.com/lucatume/wp-browser/commit/6dbf93709194c630191c0c7de527b577105be743).
  //  - It's default in Symfony\Process now.
  //  - String variant is still buggy even after a fix (https://github.com/lucatume/wp-browser/commit/b078ef37917b4f0668d064ea950e4b41f1773cb6).

  public function cli(array $userCommand) {
    return $this->_cli($userCommand);
  }

  public function cliToArray(array $userCommand) {
    return $this->_cliToArray($userCommand);
  }

  public function cliToString(array $userCommand) {
    return $this->_cliToString($userCommand);
  }

  /**
   * Creates post and returns its URL
   */
  public function createPost(string $title, string $body): string {
    $post = $this->cliToString(['post', 'create', '--format=json', '--porcelain', '--post_status=publish', '--post_type=post', '--post_title="' . $title . '"', '--post_content="' . $body . '"']);
    $postData = $this->cliToString(['post', 'get', $post, '--format=json']);
    $postData = json_decode($postData, true);
    Assert::assertIsArray($postData);
    Assert::assertIsString($postData['guid']);
    return $postData['guid'];
  }

  public function addFromBlockInEditor($name, $context = null) {
    $i = $this;
    $appender = '[data-automation-id="form_inserter_open"]';
    if ($context) {
      $appender = "$context $appender";
    }
    $i->click($appender);// CLICK the button that adds new blocks
    $i->fillField('.block-editor-inserter__search [placeholder="Search"]', $name);
    $i->waitForText($name, 5, '.block-editor-block-types-list__item-title');
    $i->click($name, '.block-editor-block-types-list__list-item');
    $i->click($appender);// close the inserter
  }

  public function saveFormInEditor() {
    $i = $this;
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
  }

  /**
   * Select the panel color in the form editor
   * Selectable colors are: Black [1], Gray [2], White [3], Pink [4], Red [5], Orange [6],
   * Yellow [7], Turquoise [8], Green [9], Cyan [10], Blue [11] and Purple [12].
   * Please select colors by providing [number] as string.
   * @param string $colorOrder
   */
  public function selectPanelColor($colorOrder) {
    $i = $this;
    $i->click('(//div[@class="components-circular-option-picker__option-wrapper"])' . $colorOrder);
  }

  /**
   * Checks that email was received by looking for a subject in inbox.
   * In case it was not found reloads the inbox and check once more.
   * Emails are sent via cron and might not be sent immediately.
   * @param string $subject
   */
  public function checkEmailWasReceived($subject) {
    $i = $this;
    $i->amOnMailboxAppPage();
    try {
      $i->waitForText($subject, 30);
    } catch (\Exception $e) {
      $i->amOnMailboxAppPage();
      $i->waitForText($subject, 60);
    }
  }

  /**
   * Checks that email was not received by looking for a subject in inbox.
   * @param string $subject
   */
  public function checkEmailWasNotReceived($subject) {
    $i = $this;
    $i->triggerMailPoetActionScheduler();
    $i->amOnMailboxAppPage();
    $i->dontSee($subject);
    // click refresh button to seek for new emails once again
    $i->click('.glyphicon-refresh');
    $i->dontSee($subject);
  }

  /**
   * Checks if the subscriber has correct global status
   * and if some lists are passed also validates that they are subscribed in those lists
   * @param string $email
   * @param string $status
   * @param string[]|null $listsSubscribed Array of lists in that subscriber should be subscribed
   * @param string[]|null $listsNotSubscribed Array of lists in that subscriber shouldn't be subscribed
   */
  public function checkSubscriberStatusAndLists(string $email, string $status, $listsSubscribed = null, $listsNotSubscribed = null) {
    $i = $this;
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor($email);
    $i->waitForListingItemsToLoad();
    $i->waitForText($email);
    $i->see(ucfirst($status), 'td[data-colname="Status"]');
    if (is_array($listsSubscribed)) {
      foreach ($listsSubscribed as $list) {
        $i->see($list, 'td[data-colname="Lists"]');
      }
    }
    if (is_array($listsNotSubscribed)) {
      foreach ($listsNotSubscribed as $list) {
        $i->dontSee($list, 'td[data-colname="Lists"]');
      }
    }
  }

  /**
   * Checks if any confirmation email is in mailbox
   */
  public function seeConfirmationEmailWasReceived() {
    $this->checkEmailWasReceived('Confirm your subscription to');
  }

  /**
   * Checks if there are no confirmation emails in mailbox
   */
  public function seeConfirmationEmailWasNotReceived() {
    $i = $this;
    $i->amOnMailboxAppPage();
    $i->dontSee('Confirm your subscription to');
  }

  /**
   * Makes sure that there is a newsletter template of given order on given template tab
   * @return string Template element selector
   */
  public function checkTemplateIsPresent(int $templateIndex, string $templateCategory = 'standard'): string {
    $templateTab = "[data-automation-id=\"templates-$templateCategory\"]";
    $i = $this;
    $i->waitForElement($templateTab);
    $i->click($templateTab);
    $template = "[data-automation-id=\"select_template_$templateIndex\"]";
    $i->waitForElement($template);
    return $template;
  }

  public function clearFormField(string $selector) {
    $i = $this;
    $i->click($selector); // Focus in the field
    $value = $i->grabAttributeFrom($selector, 'value');
    Assert::assertIsString($value);

    for ($j = 0; $j < mb_strlen($value); $j++) {
      $i->pressKey($selector, WebDriverKeys::BACKSPACE);// delete the field
    }
  }

  public function canTestWithPlugin(string $pluginSlug): bool {
    $i = $this;
    try {
      $result = $i->cli(['plugin', 'is-installed', $pluginSlug]);
    } catch (\Exception $e) {
      return false;
    }
    return (int)$result === 0;
  }

  /**
   * Some tests rely on background job processing.
   * The processing runs in 1 minute interval (default Action Scheduler interval)
   * This method triggers the processing immediately so that tests don't have to wait.
   */
  public function triggerMailPoetActionScheduler(): void {
    $i = $this;
    // Reschedule MailPoet's daemon trigger action to run immediately
    $i->importSql([
      "UPDATE mp_actionscheduler_actions SET scheduled_date_gmt = SUBTIME(now(), '01:00:00'), scheduled_date_local = SUBTIME(now(), '01:00:00') WHERE hook = 'mailpoet/cron/daemon-trigger' AND status = 'pending';",
    ]);
    $i->cli(['action-scheduler', 'run', '--force']);
  }

  public function triggerAutomationActionScheduler(): void {
    $i = $this;
    // Reschedule automation trigger action to run immediately
    $i->importSql([
      "UPDATE mp_actionscheduler_actions SET scheduled_date_gmt = SUBTIME(now(), '01:00:00'), scheduled_date_local = SUBTIME(now(), '01:00:00') WHERE hook = 'mailpoet/automation/step' AND status = 'pending';",
    ]);
    $i->cli(['action-scheduler', 'run', '--force']);
  }

  public function isWooCustomOrdersTableEnabled(): bool {
    return (bool)getenv('ENABLE_COT');
  }

  public function changeGroupInListingFilter(string $name): void {
    $i = $this;
    for ($x = 1; $x <= 3; $x++) {
      try {
        $i->waitForElementClickable('[data-automation-id="filters_' . $name . '"]');
        $i->click('[data-automation-id="filters_' . $name . '"]');
        $i->seeInCurrentURL(urlencode('group[' . $name . ']'));
        break;
      } catch (Exception $exception) {
        $this->wait(0.5);
        continue;
      }
    }
    $i->seeInCurrentURL(urlencode('group[' . $name . ']'));
  }

  public function checkWooTableCheckboxForItemName(string $itemName): void {
    $i = $this;
    $xpath = ['xpath' => '//tr[.//a[text()="' . $itemName . '"]]//input[@type="checkbox"]'];
    $i->click($xpath);
  }

  public function changeWooTableTab(string $name): void {
    $i = $this;
    $i->click('button.mailpoet-tab-' . $name);
  }

  public function clearTransientCache(): void {
    $cache = ContainerWrapper::getInstance()->get(TransientCache::class);
    $cache->invalidateAllItems();
  }

  public function selectSegmentTemplate($templateName) {
    $i = $this;
    $segmentName = Locator::contains('.mailpoet-templates-card-header-title', $templateName);
    $i->waitForElement($segmentName);
    $i->click($segmentName);
  }

  public function checkEmailEditorRequiredWordpressVersion(): bool {
    $i = $this;
    $wordPressVersion = $i->getWordPressVersion();
    // New email editor is not compatible with WP versions below 6.7
    if (version_compare($wordPressVersion, self::EMAIL_EDITOR_MINIMAL_WP_VERSION, '<')) {
      return false;
    }
    return true;
  }
}
