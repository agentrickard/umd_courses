<?php

namespace Drupal\umd_courses_http_mock;

use Drupal\Core\Extension\ModuleExtensionList;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Mocks the HTTP client to return local data for the UMD API.
 */
class MockClient implements ClientInterface {

  /**
   * The decorated HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $innerClient;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The extension list module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Constructs a new MockClient object.
   *
   * @param \GuzzleHttp\ClientInterface $inner_client
   *   The decorated HTTP client.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The extension list module service.
   */
  public function __construct(ClientInterface $inner_client, ModuleExtensionList $extension_list_module) {
    $this->innerClient = $inner_client;
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri = '', array $options = []): ResponseInterface {
    // Check if mock mode is enabled and the request is for the UMD API.
    if (strpos($uri, 'https://api.umd.io') === 0) {
      $module_path = $this->extensionListModule->getPath('umd_courses');
      $fixture_path = DRUPAL_ROOT . '/' . $module_path . '/fixtures/courses_api_response.json';

      // Check if the fixture file exists.
      if (file_exists($fixture_path)) {
        $json_data = file_get_contents($fixture_path);
        // Return a mock Guzzle Response object.
        return new Response(200, ['Content-Type' => 'application/json'], $json_data);
      }
      else {
        // Log an error and pass the request through if the fixture is missing.
        \Drupal::logger('umd_courses_http_mock')->error('UMD Courses mock fixture file not found. Falling back to live API.');
      }
    }

    // Pass the request to the original client for live API calls or other URLs.
    return $this->innerClient->request($method, $uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, array $arguments) {
    return $this->innerClient->__call($name, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []): ResponseInterface {
    // @todo Implement send() method.
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface {
    // @todo Implement sendAsync() method.
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync(string $method, $uri, array $options = []): PromiseInterface {
    // @todo Implement requestAsync() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(?string $option = NULL) {
    // @todo Implement getConfig() method.
  }

}
