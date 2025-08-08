<?php

namespace Drupal\umd_courses\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for fetching course data from the UMD API.
 */
class UmdApiClient {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The configuration object.
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
   * The UMD API base URL.
   */
  const API_BASE_URL = 'https://api.umd.io/v1';

  /**
   * Cache expiration time (1 hour).
   */
  const CACHE_EXPIRE = 3600;

  /**
   * Constructs a new UmdApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $extension_list_module
   *   The extension list module service.
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory, TimeInterface $time, ConfigFactoryInterface $config_factory, $extension_list_module = NULL) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
    $this->time = $time;
    $this->config = $config_factory->get('umd_courses.settings');
    // Inject the extension.list.module service if provided, otherwise fallback to \Drupal::service for BC.
    $this->extensionListModule = $extension_list_module ?: \Drupal::service('extension.list.module');
  }

  /**
   * Fetches courses from the UMD API.
   *
   * @param int $limit
   *   The maximum number of courses to fetch.
   *
   * @return array
   *   An array of course data.
   */
  public function getCourses($limit = 50) {
    // Check for a configuration setting to enable mock mode.
    if ($this->config->get('mock_mode_enabled')) {
      // Use the injected extension.list.module service to get the module path.
      $module_path = $this->extensionListModule->getPath('umd_courses');
      $fixture_path = DRUPAL_ROOT . '/' . $module_path . '/fixtures/courses_api_response.json';

      if (file_exists($fixture_path)) {
        $json_data = file_get_contents($fixture_path);
        return json_decode($json_data, TRUE);
      }
      else {
        $this->loggerFactory->get('umd_courses')->warning('Fixture file not found: @path', ['@path' => $fixture_path]);
        return [];
      }
    }

    $cache_key = 'umd_courses:courses:' . $limit;

    // Try to get data from cache first.
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }

    try {
      $response = $this->httpClient->request('GET', self::API_BASE_URL . '/courses', [
        'query' => [
          'per_page' => $limit,
        ],
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Invalid JSON response from UMD API');
      }

      // Cache the data for 1 hour.
      $this->cache->set($cache_key, $data, $this->time->getRequestTime() + self::CACHE_EXPIRE);

      return $data;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('umd_courses')->error('Failed to fetch courses from UMD API: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('umd_courses')->error('Error processing UMD API response: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Fetches a specific course by ID from the UMD API.
   *
   * @param string $course_id
   *   The course ID.
   *
   * @return array|null
   *   The course data or NULL if not found.
   */
  public function getCourse($course_id) {
    // The getCourse method is not affected by mock mode in this example,
    // as we're only mocking the list of courses. We could extend this logic
    // to include a fixture for single courses if needed.
    $cache_key = 'umd_courses:course:' . $course_id;

    // Try to get data from cache first.
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }

    try {
      $response = $this->httpClient->request('GET', self::API_BASE_URL . '/courses/' . $course_id, [
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Invalid JSON response from UMD API');
      }

      // Cache the data for 1 hour.
      $this->cache->set($cache_key, $data, $this->time->getRequestTime() + self::CACHE_EXPIRE);

      return $data;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('umd_courses')->error('Failed to fetch course @course_id from UMD API: @error', [
        '@course_id' => $course_id,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('umd_courses')->error('Error processing UMD API response for course @course_id: @error', [
        '@course_id' => $course_id,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
