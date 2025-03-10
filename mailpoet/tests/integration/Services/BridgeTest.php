<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Util\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Services\Bridge\BridgeTestMockAPI as MockAPI;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

require_once('BridgeTestMockAPI.php');

class BridgeTest extends \MailPoetTest {
  public $usedPremiumKey;
  public $expiringPremiumKey;
  public $uncheckableKey;
  public $underPrivilegedKey;
  public $usedKey;
  public $expiringKey;
  public $invalidKey;
  public $validKey;

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  public function _before() {
    parent::_before();
    $this->validKey = 'abcdefghijklmnopqrstuvwxyz';
    $this->invalidKey = '401' . $this->validKey;
    $this->expiringKey = 'expiring' . $this->validKey;
    $this->usedKey = '402' . $this->validKey;
    $this->underPrivilegedKey = '403' . $this->validKey;
    $this->uncheckableKey = '503' . $this->validKey;

    $this->expiringPremiumKey = 'expiring' . $this->validKey;
    $this->usedPremiumKey = '402' . $this->validKey;

    $this->bridge = new Bridge();

    $this->bridge->api = new MockAPI('key');
    $this->settings = SettingsController::getInstance();
  }

  public function testItChecksIfCurrentSendingMethodIsMailpoet() {
    $this->setMailPoetSendingMethod();
    verify(Bridge::isMPSendingServiceEnabled())->true();
  }

  public function testMPCheckReturnsFalseWhenMailerThrowsException() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, '');
    verify(Bridge::isMPSendingServiceEnabled())->false();
  }

  public function testItChecksIfPremiumKeyIsSpecified() {
    verify(Bridge::isPremiumKeySpecified())->false();
    $this->fillPremiumKey();
    verify(Bridge::isPremiumKeySpecified())->true();
  }

  public function testItInstantiatesDefaultAPI() {
    $this->bridge->api = null;
    verify($this->bridge->getApi('key') instanceof API)->true();
  }

  public function testItChecksValidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->validKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_VALID);
  }

  public function testItChecksInvalidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->invalidKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_INVALID);
  }

  public function testItChecksExpiringMSSKey() {
    $result = $this->bridge->checkMSSKey($this->expiringKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_EXPIRING);
    verify($result['data']['expire_at'])->notEmpty();
  }

  public function testItChecksAlreadyUsed() {
    $result = $this->bridge->checkMSSKey($this->usedKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  public function testItChecksForbiddenEndpointMSSKey() {
    $result = $this->bridge->checkMSSKey($this->underPrivilegedKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
  }

  public function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringMSSCheck() {
    $api = Stub::make(new API(null), ['checkMSSKey' => []], $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkMSSKey($this->validKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  public function testItStoresExpectedMSSKeyStates() {
    $states = [
      Bridge::KEY_VALID => $this->validKey,
      Bridge::KEY_INVALID => $this->invalidKey,
      Bridge::KEY_EXPIRING => $this->expiringKey,
      Bridge::KEY_ALREADY_USED => $this->usedKey,
      Bridge::CHECK_ERROR_UNAVAILABLE => $this->uncheckableKey,
      Bridge::KEY_VALID_UNDERPRIVILEGED => $this->underPrivilegedKey,
    ];
    foreach ($states as $state => $key) {
      $state = ['state' => $state];
      $this->bridge->storeMSSKeyAndState($key, $state);
      verify($this->getMSSKey())->equals($key);
      verify($this->getMSSKeyState())->equals($state);
    }
  }

  public function testItDoesNotStoreErroneousOrUnexpectedMSSKeyStates() {
    $states = [
      ['state' => Bridge::KEY_CHECK_ERROR],
      [],
    ];
    foreach ($states as $state) {
      $this->bridge->storeMSSKeyAndState($this->validKey, $state);
      verify($this->getMSSKey())->notEquals($this->validKey);
      verify($this->getMSSKeyState())->notEquals($state);
    }
  }

  public function testItChecksValidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->validKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_VALID);
  }

  public function testItChecksInvalidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->invalidKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_INVALID);
  }

  public function testItChecksAlreadyUsedPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->usedPremiumKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  public function testItChecksForbiddenEndpointPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->underPrivilegedKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
  }

  public function testItChecksExpiringPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->expiringPremiumKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_EXPIRING);
    verify($result['data']['expire_at'])->notEmpty();
  }

  public function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringPremiumCheck() {
    $api = Stub::make(new API(null), ['checkPremiumKey' => []], $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkPremiumKey($this->validKey);
    verify($result)->notEmpty();
    verify($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  public function testItStoresExpectedPremiumKeyStates() {
    $states = [
      Bridge::KEY_VALID => $this->validKey,
      Bridge::KEY_INVALID => $this->invalidKey,
      Bridge::KEY_ALREADY_USED => $this->usedPremiumKey,
      Bridge::KEY_EXPIRING => $this->expiringKey,
    ];
    foreach ($states as $state => $key) {
      $state = ['state' => $state];
      $this->bridge->storePremiumKeyAndState($key, $state);
      verify($this->getPremiumKey())->equals($key);
      verify($this->getPremiumKeyState())->equals($state);
    }
  }

  public function testItDoesNotStoreErroneousOrUnexpectedPremiumKeyStates() {
    $states = [
      ['state' => Bridge::KEY_CHECK_ERROR],
      [],
    ];
    foreach ($states as $state) {
      $this->bridge->storePremiumKeyAndState($this->validKey, $state);
      verify($this->getPremiumKey())->notEquals($this->validKey);
      verify($this->getPremiumKeyState())->notEquals($state);
    }
  }

  public function testItStoresSubscriptionTypeOnPremiumCheck() {
    $state = ['state' => Bridge::KEY_VALID, 'data' => ['subscription_type' => Bridge::WPCOM_SUBSCRIPTION_TYPE]];
    $this->bridge->storePremiumKeyAndState($this->validKey, $state);
    verify($this->getSubscriptionType())->equals(Bridge::WPCOM_SUBSCRIPTION_TYPE);
  }

  public function testItStoresSubscriptionTypeOnMSSCheck() {
    $state = ['state' => Bridge::KEY_VALID, 'data' => ['subscription_type' => Bridge::WCCOM_SUBSCRIPTION_TYPE]];
    $this->bridge->storeMSSKeyAndState($this->validKey, $state);
    verify($this->getSubscriptionType())->equals(Bridge::WCCOM_SUBSCRIPTION_TYPE);
  }

  public function testItDoesNotStoreInvalidSubscriptionType() {
    $state = ['state' => Bridge::KEY_VALID, 'data' => ['subscription_type' => 'INVALID']];
    $this->bridge->storePremiumKeyAndState($this->validKey, $state);
    verify($this->getSubscriptionType())->notEquals('INVALID');
    $this->bridge->storeMSSKeyAndState($this->validKey, $state);
    verify($this->getSubscriptionType())->notEquals('INVALID');
  }

  public function testItInvalidatesMSSKey() {
    $this->bridge->storeMSSKeyAndState($this->validKey, ['state' => Bridge::KEY_VALID]);
    $storedState = $this->getMssKeyState() ?? [];
    verify($storedState['state'])->equals(Bridge::KEY_VALID);
    $this->bridge->invalidateMssKey();
    $storedState = $this->getMssKeyState() ?? [];
    verify($storedState['state'])->equals(Bridge::KEY_INVALID);
  }

  public function testItPingsBridge() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $result = $this->bridge->pingBridge();
    verify($this->bridge->validateBridgePingResponse($result))->true();
  }

  public function testItAllowsChangingRequestTimeout() {
    /** @var array $wpRemotePostArgs */
    $wpRemotePostArgs = [];
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => function() use (&$wpRemotePostArgs) {
        $wpRemotePostArgs = func_get_args();
      },
    ]);
    $api = new API('test_key', $wp);

    // test default request value
    $api->sendMessages('test');
    verify($wpRemotePostArgs[1]['timeout'])->equals(API::REQUEST_TIMEOUT);

    // test custom request value
    $customRequestValue = 20;
    $filter = function() use ($customRequestValue) {
      return $customRequestValue;
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_bridge_api_request_timeout', $filter);
    $api->sendMessages('test');
    verify($wpRemotePostArgs[1]['timeout'])->equals($customRequestValue);
    $wp->removeFilter('mailpoet_bridge_api_request_timeout', $filter);
  }

  public function testItReturnsAllUserEmails() {
    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
      'main' => 'main@email.com',
    ];
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => $array], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    verify($result)->same($array);
  }

  public function testItReturnsAnEmptyArrayIfNoEmailForAllParam() {
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    verify($result)->same([]);
  }

  public function testItReturnsAnEmptyArrayIfNoEmailForAuthorizedParam() {
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    verify($result)->same([]);
  }

  public function testItReturnsTheRightDataForSenderDomains() {
    // when API returns null
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => null], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    verify($result)->same([]);

    // when API returns an empty array []
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    verify($result)->same([]);

    // when arg param is 'all'
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('all');
    verify($result)->same([]);
  }

  public function testItReturnsSenderDomainsDnsRecords() {
    $domainData = MockAPI::VERIFIED_DOMAIN_RESPONSE;
    $domainData['domain'] = 'example.com';
    $data = [$domainData];

    // with a custom sender domain param
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('example.com');
    verify($result)->same($data[0]['dns']);

    // with a custom sender domain param that does not exist
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('mailpoet.com');
    verify($result)->same([]);

    // when param is all
    $returnDataForAllParam = [
      'example.com' => $data[0]['dns'],
    ];

    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('all');
    verify($result)->same($returnDataForAllParam);

    // when param is not provided
    $returnDataForNoArgs = [
      'example.com' => $data[0]['dns'],
    ];

    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    verify($result)->same($returnDataForNoArgs);
  }

  public function testItCanCreateSenderDomain() {
    $result = $this->bridge->createAuthorizedSenderDomain('mailpoet.com');
    verify($result)->notEmpty();
    verify(isset($result['error']))->false();
    verify($result[0]['host'])->equals('mailpoet1._domainkey.example.com');
  }

  public function testItDoesntCreateSenderDomainThatExists() {
    $result = $this->bridge->createAuthorizedSenderDomain('existing.com');
    verify($result)->notEmpty();
    verify($result['error'])->equals('This domain was already added to the list.');
    verify($result['status'])->equals(false);
  }

  public function testTheSenderDomainApiReturnsValidDataType() {
    $result = $this->bridge->getAuthorizedSenderDomains('mailpoet.com');
    verify($result)->notEmpty();
    verify($result[0]['host'])->equals('mailpoet1._domainkey.example.com');
    verify($result[0]['value'])->equals('dkim1.sendingservice.net');
    verify($result[0]['type'])->equals('CNAME');
    verify($result[0]['status'])->equals('valid');
    verify($result[0]['message'])->equals('');
  }

  public function testItCanVerifySenderDomain() {
    $result = $this->bridge->verifyAuthorizedSenderDomain('mailpoet.com');
    verify($result)->notEmpty();
    verify($result['ok'])->equals(true); // verified
  }

  public function testItPreservesMSSKeyStateDataIfSubsequentCheckFails() {
    $apiMock = $this->createMock(API::class);
    $data = ['some_key' => 'some_value'];
    $okResponse = [
      'code' => 200,
      'data' => $data,
    ];
    $errorResponse = [
      'code' => 403,
      'error_message' => 'Insufficient privileges',
    ];
    $apiMock
      ->expects($this->exactly(2))
      ->method('checkMSSKey')->willReturnOnConsecutiveCalls($okResponse, $errorResponse);
    $apiMock
      ->expects($this->exactly(2))
      ->method('setKey')->willReturn(null);
    $this->bridge->api = $apiMock;

    // First check succeeds
    $result = $this->bridge->checkMSSKey('abc');
    $this->bridge->storeMSSKeyAndState('abc', $result);
    $state = $this->getMssKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID);
    verify($state['data'])->equals($data);
    // Second check fails with 403 insufficient privileges simulating that key lost access to MSS
    $result = $this->bridge->checkMSSKey('abc');
    $this->bridge->storeMSSKeyAndState('abc', $result);
    $state = $this->getMssKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
    verify($state['data'])->equals($data);
  }

  public function testItResetsMSSKeyStateDataIfSubsequentCheckFailForADiffrentKey() {
    $apiMock = $this->createMock(API::class);
    $data = ['some_key' => 'some_value'];
    $okResponse = [
      'code' => 200,
      'data' => $data,
    ];
    $errorResponse = [
      'code' => 403,
      'error_message' => 'Insufficient privileges',
    ];
    $apiMock
      ->expects($this->exactly(2))
      ->method('checkMSSKey')->willReturnOnConsecutiveCalls($okResponse, $errorResponse);
    $apiMock
      ->expects($this->exactly(2))
      ->method('setKey')->willReturn(null);
    $this->bridge->api = $apiMock;

    // First check succeeds
    $result = $this->bridge->checkMSSKey('abc');
    $this->bridge->storeMSSKeyAndState('abc', $result);
    $state = $this->getMssKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID);
    verify($state['data'])->equals($data);
    // Second check fails with 403 insufficient privileges simulating that key lost access to MSS
    $result = $this->bridge->checkMSSKey('cba');
    $this->bridge->storeMSSKeyAndState('cba', $result);
    $state = $this->getMssKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
    verify($state['data'])->null();
  }

  public function testItPreservesPremiumKeyStateDataIfSubsequentCheckFails() {
    $apiMock = $this->createMock(API::class);
    $data = ['some_key' => 'some_value'];
    $okResponse = [
      'code' => 200,
      'data' => $data,
    ];
    $errorResponse = [
      'code' => 403,
      'error_message' => 'Insufficient privileges',
    ];
    $apiMock
      ->expects($this->exactly(2))
      ->method('checkPremiumKey')->willReturnOnConsecutiveCalls($okResponse, $errorResponse);
    $apiMock
      ->expects($this->exactly(2))
      ->method('setKey')->willReturn(null);
    $this->bridge->api = $apiMock;

    // First check succeeds
    $result = $this->bridge->checkPremiumKey('abc');
    $this->bridge->storePremiumKeyAndState('abc', $result);
    $state = $this->getPremiumKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID);
    verify($state['data'])->equals($data);
    // Second check fails with 403 insufficient privileges simulating that key lost access to MSS
    $result = $this->bridge->checkPremiumKey('abc');
    $this->bridge->storePremiumKeyAndState('abc', $result);
    $state = $this->getPremiumKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
    verify($state['data'])->equals($data);
  }

  public function testItResetsPremiumKeyStateDataIfSubsequentCheckFailForADiffrentKey() {
    $apiMock = $this->createMock(API::class);
    $data = ['some_key' => 'some_value'];
    $okResponse = [
      'code' => 200,
      'data' => $data,
    ];
    $errorResponse = [
      'code' => 403,
      'error_message' => 'Insufficient privileges',
    ];
    $apiMock
      ->expects($this->exactly(2))
      ->method('checkPremiumKey')->willReturnOnConsecutiveCalls($okResponse, $errorResponse);
    $apiMock
      ->expects($this->exactly(2))
      ->method('setKey')->willReturn(null);
    $this->bridge->api = $apiMock;

    // First check succeeds
    $result = $this->bridge->checkPremiumKey('abc');
    $this->bridge->storePremiumKeyAndState('abc', $result);
    $state = $this->getPremiumKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID);
    verify($state['data'])->equals($data);
    // Second check fails with 403 insufficient privileges simulating that key lost access to MSS
    $result = $this->bridge->checkPremiumKey('cba');
    $this->bridge->storePremiumKeyAndState('cba', $result);
    $state = $this->getPremiumKeyState() ?? [];
    verify($state['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
    verify($state['data'])->null();
  }

  public function testItSavesAccessRestrictionForUnderprivilegePremiumKeys() {
    // Insufficient privileges
    $this->checkKeyAccessRestrictionSetProperly(
      'premium',
      'Insufficient privileges',
      Bridge::KEY_ACCESS_INSUFFICIENT_PRIVILEGES
    );

    // Email volume limit
    $this->checkKeyAccessRestrictionSetProperly(
      'premium',
      'Email volume limit reached',
      Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT
    );

    // Subscribers limit
    $this->checkKeyAccessRestrictionSetProperly(
      'premium',
      'Subscribers limit reached',
      Bridge::KEY_ACCESS_SUBSCRIBERS_LIMIT
    );

    // Unknown value
    $this->checkKeyAccessRestrictionSetProperly(
      'premium',
      'Nonsense message',
      null
    );
  }

  public function testItSavesAccessRestrictionForUnderprivilegeMSSKeys() {
    $api = $this->createMock(API::class);
    // Insufficient privileges
    $this->checkKeyAccessRestrictionSetProperly(
      'mss',
      'Insufficient privileges',
      Bridge::KEY_ACCESS_INSUFFICIENT_PRIVILEGES
    );

    // Email volume limit
    $this->checkKeyAccessRestrictionSetProperly(
      'mss',
      'Email volume limit reached',
      Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT
    );

    // Subscribers limit
    $this->checkKeyAccessRestrictionSetProperly(
      'mss',
      'Subscribers limit reached',
      Bridge::KEY_ACCESS_SUBSCRIBERS_LIMIT
    );

    // Unknown value
    $this->checkKeyAccessRestrictionSetProperly(
      'mss',
      'Nonsense message',
      null
    );
  }

  private function checkKeyAccessRestrictionSetProperly(string $keyType, string $errorMessage, $expectedAccessRestriction) {
    $api = $this->createMock(API::class);
    $method = $keyType === 'premium' ? 'checkPremiumKey' : 'checkMSSKey';
    $api->method($method)->willReturn([
      'code' => 403,
      'error_message' => $errorMessage,
    ]);
    $this->bridge->api = $api;
    $result = $this->bridge->$method('abc');
    verify($result)->notEmpty();
    verify($result['data'])->null();
    verify($result['access_restriction'])->equals($expectedAccessRestriction);
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  private function getMSSKey() {
    return $this->settings->get(Bridge::API_KEY_SETTING_NAME);
  }

  private function getMSSKeyState() {
    return $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME);
  }

  private function fillPremiumKey() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  private function getSubscriptionType() {
    return $this->settings->get(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME);
  }

  private function getPremiumKey() {
    return $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
  }

  private function getPremiumKeyState() {
    return $this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);
  }
}
