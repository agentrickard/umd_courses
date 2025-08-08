<?php

namespace Drupal\Tests\umd_courses\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\umd_courses\Service\UmdApiClient;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Datetime\TimeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Unit tests for UmdApiClient service.
 *
 * @coversDefaultClass \Drupal\umd_courses\Service\UmdApiClient
 * @group umd_courses
 */
class UmdApiClientTest extends UnitTestCase {

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mocked time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * The UmdApiClient service under test.
   *
   * @var \Drupal\umd_courses\Service\UmdApiClient
   */
  protected $apiClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->time = $this->createMock(TimeInterface::class);

    $this->loggerFactory->method('get')
      ->with('umd_courses')
      ->willReturn($this->logger);

    $this->time->method('getRequestTime')
      ->willReturn(1234567890);

    $this->apiClient = new UmdApiClient(
      $this->httpClient,
      $this->cache,
      $this->loggerFactory,
      $this->time
    );
  }

  /**
   * Test getCourses with successful API response.
   *
   * @covers ::getCourses
   */
  public function testGetCoursesSuccess() {
    $mockResponseData = [
      [
        'course_id' => 'AAAS100',
        'name' => 'Introduction to African American Studies',
        'department' => 'African American and Africana Studies',
        'credits' => '3',
        'description' => 'Test course description',
      ],
      [
        'course_id' => 'AAAS101',
        'name' => 'Advanced Studies',
        'department' => 'African American and Africana Studies',
        'credits' => '4',
        'description' => 'Another test course',
      ],
    ];

    // Mock cache miss.
    $this->cache->expects($this->once())
      ->method('get')
      ->with('umd_courses:courses:50')
      ->willReturn(FALSE);

    // Mock successful HTTP response.
    $response = new Response(200, [], json_encode($mockResponseData));
    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://api.umd.io/v1/courses', [
        'query' => ['per_page' => 50],
        'timeout' => 30,
      ])
      ->willReturn($response);

    // Mock cache set.
    $this->cache->expects($this->once())
      ->method('set')
      ->with('umd_courses:courses:50', $mockResponseData, 1234567890 + 3600);

    $result = $this->apiClient->getCourses(50);

    $this->assertEquals($mockResponseData, $result);
  }

  /**
   * Test getCourses with cache hit.
   *
   * @covers ::getCourses
   */
  public function testGetCoursesCacheHit() {
    $cachedData = [
      [
        'course_id' => 'CACHED100',
        'name' => 'Cached Course',
        'department' => 'Cached Department',
      ],
    ];

    $cacheObject = (object) ['data' => $cachedData];

    // Mock cache hit.
    $this->cache->expects($this->once())
      ->method('get')
      ->with('umd_courses:courses:30')
      ->willReturn($cacheObject);

    // HTTP client should not be called.
    $this->httpClient->expects($this->never())
      ->method('request');

    // Cache should not be set again.
    $this->cache->expects($this->never())
      ->method('set');

    $result = $this->apiClient->getCourses(30);

    $this->assertEquals($cachedData, $result);
  }

  /**
   * Test getCourses with HTTP request exception.
   *
   * @covers ::getCourses
   */
  public function testGetCoursesHttpException() {
    // Mock cache miss.
    $this->cache->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    // Mock HTTP request exception.
    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Network error', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    // Expect error to be logged.
    $this->logger->expects($this->once())
      ->method('error')
      ->with('Failed to fetch courses from UMD API: @error', [
        '@error' => 'Network error',
      ]);

    $result = $this->apiClient->getCourses();

    $this->assertEquals([], $result);
  }

  /**
   * Test getCourses with invalid JSON response.
   *
   * @covers ::getCourses
   */
  public function testGetCoursesInvalidJson() {
    // Mock cache miss.
    $this->cache->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    // Mock response with invalid JSON.
    $response = new Response(200, [], 'invalid json');
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    // Expect error to be logged.
    $this->logger->expects($this->once())
      ->method('error')
      ->with('Error processing UMD API response: @error', [
        '@error' => 'Invalid JSON response from UMD API',
      ]);

    $result = $this->apiClient->getCourses();

    $this->assertEquals([], $result);
  }

  /**
   * Test getCourse with successful response.
   *
   * @covers ::getCourse
   */
  public function testGetCourseSuccess() {
    $courseData = [
      'course_id' => 'AAAS100',
      'name' => 'Introduction to African American Studies',
      'department' => 'African American and Africana Studies',
      'credits' => '3',
    ];

    // Mock cache miss.
    $this->cache->expects($this->once())
      ->method('get')
      ->with('umd_courses:course:AAAS100')
      ->willReturn(FALSE);

    // Mock successful HTTP response.
    $response = new Response(200, [], json_encode($courseData));
    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://api.umd.io/v1/courses/AAAS100', [
        'timeout' => 30,
      ])
      ->willReturn($response);

    // Mock cache set.
    $this->cache->expects($this->once())
      ->method('set')
      ->with('umd_courses:course:AAAS100', $courseData, 1234567890 + 3600);

    $result = $this->apiClient->getCourse('AAAS100');

    $this->assertEquals($courseData, $result);
  }

  /**
   * Test getCourse with request exception.
   *
   * @covers ::getCourse
   */
  public function testGetCourseException() {
    // Mock cache miss.
    $this->cache->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    // Mock HTTP request exception.
    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Not found', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    // Expect error to be logged.
    $this->logger->expects($this->once())
      ->method('error')
      ->with('Failed to fetch course @course_id from UMD API: @error', [
        '@course_id' => 'NONEXISTENT',
        '@error' => 'Not found',
      ]);

    $result = $this->apiClient->getCourse('NONEXISTENT');

    $this->assertNull($result);
  }

}
