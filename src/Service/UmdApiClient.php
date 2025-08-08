<?php

namespace Drupal\umd_courses\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

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
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory, TimeInterface $time) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
    $this->time = $time;
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
