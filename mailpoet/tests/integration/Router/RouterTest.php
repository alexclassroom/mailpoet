<?php declare(strict_types = 1);

namespace MailPoet\Test\Router;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\AccessControl;
use MailPoet\DI\ContainerConfigurator;
use MailPoet\DI\ContainerFactory;
use MailPoet\Router\Endpoints\RouterTestMockEndpoint;
use MailPoet\Router\Router;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;

require_once('RouterTestMockEndpoint.php');

class RouterTest extends \MailPoetTest {
  public $router;
  public $accessControl;
  public $routerData;
  /** @var Container */
  private $container;

  public function _before() {
    parent::_before();
    $this->routerData = [
      Router::NAME => '',
      'endpoint' => 'router_test_mock_endpoint',
      'action' => 'test',
      'data' => base64_encode((string)json_encode(['data' => 'dummy data'])),
    ];
    $this->accessControl = new AccessControl();
    $containerFactory = new ContainerFactory(new ContainerConfigurator());
    $this->container = $containerFactory->getConfiguredContainer();
    $this->container->register(RouterTestMockEndpoint::class)->setPublic(true);
    $this->container->compile();
    $this->router = new Router($this->accessControl, $this->container, $this->routerData);
  }

  public function testItCanGetAPIDataFromGetRequest() {
    $data = ['data' => 'dummy data'];
    $url = 'http://example.com/?' . Router::NAME . '&endpoint=view_in_browser&action=view&data='
      . base64_encode((string)json_encode($data));
    parse_str((string)parse_url($url, PHP_URL_QUERY), $_GET);
    $router = new Router($this->accessControl, $this->container);
    verify($router->apiRequest)->equals(true);
    verify($router->endpoint)->equals('viewInBrowser');
    verify($router->endpointAction)->equals('view');
    verify($router->data)->equals($data);
  }

  public function testItContinuesExecutionWhenAPIRequestNotDetected() {
    $routerData = $this->routerData;
    unset($routerData[Router::NAME]);
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $routerData]
    );
    $result = $router->init();
    verify($result)->null();
  }

  public function testItTerminatesRequestWhenEndpointNotFound() {
    $routerData = $this->routerData;
    $routerData['endpoint'] = 'invalid_endpoint';
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $routerData],
      [
        'terminateRequest' => function($code, $error) {
          return [
            $code,
            $error,
          ];
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      [
        404,
        'Invalid router endpoint',
      ]
    );
  }

  public function testItTerminatesRequestForIncorrectEndpointDataType() {
    $routerData = $this->routerData;
    $routerData['endpoint'] = ['invalid_endpoint'];
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $routerData],
      [
        'terminateRequest' => function($code, $error) {
          return [
            $code,
            $error,
          ];
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      [
        404,
        'Invalid router endpoint',
      ]
    );
  }

  public function testItTerminatesRequestWhenEndpointActionNotFound() {
    $routerData = $this->routerData;
    $routerData['action'] = 'invalid_action';
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $routerData],
      [
        'terminateRequest' => function($code, $error) {
          return [
            $code,
            $error,
          ];
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      [
        404,
        'Invalid router endpoint action',
      ]
    );
  }

  public function testItTerminatesRequestForIncorrectActionDataType() {
    $routerData = $this->routerData;
    $routerData['action'] = ['invalid_action'];
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $routerData],
      [
        'terminateRequest' => function($code, $error) {
          return [
            $code,
            $error,
          ];
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      [
        404,
        'Invalid router endpoint action',
      ]
    );
  }

  public function testItValidatesGlobalPermission() {
    $router = $this->router;

    $permissions = [
      'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
    ];
    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          verify($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        }),
      ]
    );
    $router->accessControl = $accessControl;
    verify($router->validatePermissions(null, $permissions))->false();

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          verify($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        }),
      ]
    );
    $router->accessControl = $accessControl;
    verify($router->validatePermissions(null, $permissions))->true();
  }

  public function testItValidatesEndpointActionPermission() {
    $router = $this->router;

    $permissions = [
      'global' => null,
      'actions' => [
        'test' => AccessControl::PERMISSION_MANAGE_SETTINGS,
      ],
    ];

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          verify($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        }),
      ]
    );
    $router->accessControl = $accessControl;
    verify($router->validatePermissions('test', $permissions))->false();

    $accessControl = Stub::make(
      new AccessControl(),
      [
        'validatePermission' => Expected::once(function($cap) {
          verify($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        }),
      ]
    );
    $router->accessControl = $accessControl;
    verify($router->validatePermissions('test', $permissions))->true();
  }

  public function testItValidatesPermissionBeforeProcessingEndpointAction() {
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $this->routerData],
      [
        'validatePermissions' => function($action, $permissions) {
          verify($action)->equals($this->routerData['action']);
          verify($permissions)->equals(
            [
              'global' => AccessControl::NO_ACCESS_RESTRICTION,
            ]
          );
          return true;
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      ['data' => 'dummy data']
    );
  }

  public function testItReturnsForbiddenResponseWhenPermissionFailsValidation() {
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      [$this->accessControl, $this->container, $this->routerData],
      [
        'validatePermissions' => false,
        'terminateRequest' => function($code, $error) {
          return [
            $code,
            $error,
          ];
        },
      ]
    );
    $result = $router->init();
    verify($result)->equals(
      [
        403,
        'You do not have the required permissions.',
      ]
    );
  }

  public function testItCallsEndpointAction() {
    $data = ['data' => 'dummy data'];
    $result = $this->router->init();
    verify($result)->equals($data);
  }

  public function testItExecutesUrlParameterConflictResolverAction() {
    $this->router->init();
    verify((boolean)did_action('mailpoet_conflict_resolver_router_url_query_parameters'))->true();
  }

  public function testItCanEncodeRequestData() {
    $data = ['data' => 'dummy data'];
    $result = Router::encodeRequestData($data);
    verify($result)->equals(
      rtrim(base64_encode((string)json_encode($data)), '=')
    );
  }

  public function testItReturnsEmptyArrayWhenRequestDataIsAString() {
    $encodedData = 'test';
    $result = Router::decodeRequestData($encodedData);
    verify($result)->equals([]);
  }

  public function testItCanDecodeRequestData() {
    $data = ['data' => 'dummy data'];
    $encodedData = rtrim(base64_encode((string)json_encode($data)), '=');
    $result = Router::decodeRequestData($encodedData);
    verify($result)->equals($data);
  }

  public function testItCanConvertInvalidRequestDataToArray() {
    $result = Router::decodeRequestData('some_invalid_data');
    verify($result)->equals([]);
    $result = Router::decodeRequestData(['key' => 'some_invalid_data']);
    verify($result)->equals([]);
  }

  public function testItCanBuildRequest() {
    $data = ['data' => 'dummy data'];
    $encodedData = rtrim(base64_encode((string)json_encode($data)), '=');
    $result = Router::buildRequest(
      'router_test_mock_endpoint',
      'test',
      $data
    );
    verify($result)->stringContainsString(Router::NAME . '&endpoint=router_test_mock_endpoint&action=test&data=' . $encodedData);
  }
}
