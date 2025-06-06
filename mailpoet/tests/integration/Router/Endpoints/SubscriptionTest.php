<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Router\Endpoints\Subscription;
use MailPoet\Subscription\Pages;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionTest extends \MailPoetTest {
  public $data;

  /** @var WPFunctions */
  private $wp;

  /*** @var Request */
  private $request;

  public function _before() {
    $this->data = [];
    $this->wp = WPFunctions::get();
    $this->request = $this->diContainer->get(Request::class);
  }

  public function testItDisplaysConfirmPage() {
    $pages = Stub::make(Pages::class, [
      'wp' => $this->wp,
      'confirm' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages, $this->wp, $this->request);
    $subscription->confirm($this->data);
  }

  public function testItDisplaysManagePage() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'getManageLink' => Expected::exactly(1),
      'getManageContent' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages, $this->wp, $this->request);
    $subscription->manage($this->data);
    do_shortcode('[mailpoet_manage]');
    do_shortcode('[mailpoet_manage_subscription]');
  }

  public function testItDisplaysConfirmationPageOnGetRequest() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'confirmUnsubscribe' => Expected::exactly(1),
    ], $this);
    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(false);
    $subscription = new Subscription($pages, $this->wp, $this->request);
    $subscription->unsubscribe($this->data);
  }
}
