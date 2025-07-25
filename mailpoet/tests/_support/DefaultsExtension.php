<?php declare(strict_types = 1);

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;
use MailPoet\Settings\Pages;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\UserFlags;

// phpcs:ignore PSR1.Classes.ClassDeclaration
class DefaultsExtension extends Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'setupDefaults',
  ];

  public function setupDefaults(SuiteEvent $e) {
    $this->setupWordPress();
    $this->setupWooCommerce();

    $settings = new Settings();
    $settings->withDefaultSettings();

    $userFlags = new UserFlags(1);
    $userFlags->withDefaultFlags();

    $scheduledTasks = new ScheduledTask();
    $scheduledTasks->withDefaultTasks();
  }

  private function setupWordPress() {
    update_option('siteurl', 'http://test.local', true);
    update_option('home', 'http://test.local', true);
    update_option('blogname', 'MP Dev', true);
    update_option('admin_email', 'test@example.com', true);
    update_option('gmt_offset', '0', true);
    update_option('users_can_register', '1', true);
    update_site_option('registration', 'user');
    update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/', true);

    // posts & pages
    $this->createPost('post', 'hello-world', 'Hello world!', 'Hello from WordPress.');
    $this->createPost('mailpoet_page', '', Pages::PAGE_TITLE, '[mailpoet_page]');

    // get rid of 'blog/' prefix that is added automatically to rewrite rules on multisite by default
    // (init() loads 'permalink_structure' option from DB, flush_rules() regenerates 'rewrite_rules')
    global $wp_rewrite; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $wp_rewrite->init(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $wp_rewrite->flush_rules(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // Make sure WP cron is not locked so that tests that rely on it don't timeout
    delete_transient('doing_cron');

    // Hide black Friday notice, because it can cause tests flakiness
    set_transient('dismissed-black-friday-notice', true, 3600 * 24);
  }

  private function setupWooCommerce() {
    global $wpdb;
    // address
    update_option('woocommerce_store_address', 'Address', true);
    update_option('woocommerce_store_address_2', '', true);
    update_option('woocommerce_store_city', 'Paris', true);
    update_option('woocommerce_default_country', 'FR:*', true);
    update_option('woocommerce_store_postcode', '75000', true);

    // currency
    update_option('woocommerce_currency', 'EUR', true);
    update_option('woocommerce_currency_pos', 'right', true);
    update_option('woocommerce_price_thousand_sep', ' ', true);
    update_option('woocommerce_price_decimal_sep', ',', true);

    // pages
    // block-based pages are created automatically by WooCommerce
    $shopPageId = $this->createPage('shop', 'Shop', '');
    $cartPageId = $this->createPage('shortcode-cart', 'Shortcode Cart', '[woocommerce_cart]');
    $checkoutPageId = $this->createPage('shortcode-checkout', 'Shortcode Checkout', '[woocommerce_checkout]');
    $myAccountPageId = $this->createPage('shortcode-my-account', 'Shortcode My account', '[woocommerce_my_account]');

    update_option('woocommerce_shop_page_id', $shopPageId, true);
    update_option('woocommerce_cart_page_id', $cartPageId, true);
    update_option('woocommerce_checkout_page_id', $checkoutPageId, true);
    update_option('woocommerce_myaccount_page_id', $myAccountPageId, true);

    // other
    update_option('woocommerce_bacs_settings', ['enabled' => 'yes'], true);
    update_option('woocommerce_cod_settings', ['enabled' => 'yes', 'enable_for_virtual' => 'yes'], true);
    update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes', false);
    update_option('woocommerce_enable_myaccount_registration', 'yes', false);

    // don't send customer/order emails, the mail() function is not configured and outputs warning,
    // these lines can be removed when https://github.com/lucatume/wp-browser/issues/316 is solved
    update_option('woocommerce_customer_new_account_settings', ['enabled' => 'no']);
    update_option('woocommerce_new_order_settings', ['enabled' => 'no']);
    update_option('woocommerce_customer_completed_order_settings', ['enabled' => 'no']);
    update_option('woocommerce_onboarding_profile', ['completed' => true]);
    update_option('woocommerce_task_list_welcome_modal_dismissed', 'yes');
    update_option('woocommerce_task_list_hidden', 'yes');
    delete_transient('_wc_activation_redirect');

    // mark all WC cron actions complete
    update_option('wc_pending_batch_processes', []);
    $tableName = !empty($wpdb->actionscheduler_actions) ? $wpdb->actionscheduler_actions : $wpdb->prefix . 'actionscheduler_actions';// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $wpdb->query($wpdb->prepare("UPDATE %i SET status = 'complete'", $tableName));
  }

  private function createPage($name, $tile, $content) {
    return $this->createPost('page', $name, $tile, $content);
  }

  private function createPost($type, $name, $tile, $content) {
    return wp_insert_post([
      'post_author' => 1,
      'post_type' => $type,
      'post_name' => $name,
      'post_title' => $tile,
      'post_content' => $content,
      'post_status' => 'publish',
    ]);
  }
}
