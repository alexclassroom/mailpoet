<?php declare(strict_types = 1);

namespace MailPoet\REST;

use MailPoetTest;
use RuntimeException;

use function json_decode;

abstract class Test extends MailPoetTest {
  protected function get(string $path) {
    return $this->request($path, 'GET');
  }

  protected function post(string $path, $data = null) {
    return $this->request($path, 'POST', $data);
  }

  protected function put(string $path, $data = null) {
    return $this->request($path, 'PUT', $data);
  }

  protected function patch(string $path, $data = null) {
    return $this->request($path, 'PATCH', $data);
  }

  protected function delete(string $path, $data = null) {
    return $this->request($path, 'DELETE', $data);
  }

  protected function request(string $path, string $method, $data = null) {
    $_SERVER['SERVER_NAME'] = 'tests';
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

    if ($data !== null) {
      $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($data);
    }

    $server = rest_get_server();
    ob_start();
    $server->serve_request($path);
    $response = ob_get_clean();

    if (!$response) {
      throw new RuntimeException();
    }

    $value = json_decode($response, true);
    $error = json_last_error();
    if ($error) {
      throw new RuntimeException(json_last_error_msg(), $error);
    }
    return $value;
  }

  protected function assertResponseCode(int $httpCode): void {
    $this->assertSame($httpCode, http_response_code());
  }
}
