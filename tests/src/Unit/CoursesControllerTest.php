<?php

namespace Drupal\Tests\umd_courses\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\umd_courses\Controller\CoursesController;
use Drupal\umd_courses\Service\UmdApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for CoursesController.
 *
 * @coversDefaultClass \Drupal\umd_courses\Controller\CoursesController
 * @group umd_courses
 */
class CoursesControllerTest extends UnitTestCase {

  /**
   * The mocked UMD API client service.
   *
   * @var \Drupal\umd_courses\Service\UmdApiClient|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $umdApiClient;

  /**
   * The CoursesController under test.
   *
   * @var \Drupal\umd_courses\Controller\CoursesController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->umdApiClient = $this->createMock(UmdApiClient::class);
    $this->controller = new CoursesController($this->umdApiClient);
  }

  /**
   * Test the create method.
   *
   * @covers ::create
   */
  public function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('umd_courses.api_client')
      ->willReturn($this->umdApiClient);

    $controller = CoursesController::create($container);

    $this->assertInstanceOf(CoursesController::class, $controller);
  }

  /**
   * Test arrayToString helper method with simple array.
   *
   * @covers ::arrayToString
   */
  public function testArrayToStringSimple() {
    $reflection = new \ReflectionClass($this->controller);
    $method = $reflection->getMethod('arrayToString');
    $method->setAccessible(TRUE);

    $input = ['Regular', 'Pass-Fail', 'Audit'];
    $result = $method->invokeArgs($this->controller, [$input]);

    $this->assertEquals('Regular, Pass-Fail, Audit', $result);
  }

  /**
   * Test arrayToString helper method with nested array.
   *
   * @covers ::arrayToString
   */
  public function testArrayToStringNested() {
    $reflection = new \ReflectionClass($this->controller);
    $method = $reflection->getMethod('arrayToString');
    $method->setAccessible(TRUE);

    $input = ['Regular', ['Sub1', 'Sub2'], 'Audit'];
    $result = $method->invokeArgs($this->controller, [$input]);

    $this->assertEquals('Regular, Sub1, Sub2, Audit', $result);
  }

  /**
   * Test arrayToString helper method with non-array input.
   *
   * @covers ::arrayToString
   */
  public function testArrayToStringNonArray() {
    $reflection = new \ReflectionClass($this->controller);
    $method = $reflection->getMethod('arrayToString');
    $method->setAccessible(TRUE);

    $result = $method->invokeArgs($this->controller, ['simple string']);

    $this->assertEquals('simple string', $result);
  }

  /**
   * Test arrayToString helper method with mixed data types.
   *
   * @covers ::arrayToString
   */
  public function testArrayToStringMixedTypes() {
    $reflection = new \ReflectionClass($this->controller);
    $method = $reflection->getMethod('arrayToString');
    $method->setAccessible(TRUE);

    $input = ['String', 123, TRUE, NULL];
    $result = $method->invokeArgs($this->controller, [$input]);

    $this->assertEquals('String, 123, 1', $result);
  }

  /**
   * Test coursesPage method with successful API response.
   *
   * @covers ::coursesPage
   */
  public function testCoursesPageSuccess() {
    $mockCourses = [
      [
        'course_id' => 'AAAS100',
        'name' => 'Introduction to African American Studies',
        'department' => 'African American and Africana Studies',
        'credits' => '3',
        'grading_method' => ['Regular', 'Pass-Fail'],
        'gen_ed' => ['DSHU'],
        'core' => [],
        'sections' => ['AAAS100-0101', 'AAAS100-0102'],
      ],
      [
        'course_id' => 'AAAS101',
        'name' => 'Advanced Studies',
        'department' => 'African American and Africana Studies',
        'credits' => '4',
        'grading_method' => ['Regular'],
        'gen_ed' => [],
        'core' => [],
        'sections' => ['AAAS101-0101'],
      ],
    ];

    $this->umdApiClient->expects($this->once())
      ->method('getCourses')
      ->with(30)
      ->willReturn($mockCourses);

    $result = $this->controller->coursesPage();

    // Verify the render array structure.
    $this->assertIsArray($result);
    $this->assertEquals('umd_courses_page', $result['#theme']);
    $this->assertArrayHasKey('#courses', $result);
    $this->assertArrayHasKey('#attached', $result);

    // Verify processed courses.
    $courses = $result['#courses'];
    $this->assertCount(2, $courses);

    // Check that arrays were converted to strings.
    $this->assertEquals('Regular, Pass-Fail', $courses[0]['grading_method']);
    $this->assertEquals('DSHU', $courses[0]['gen_ed']);
    $this->assertEquals('AAAS100-0101, AAAS100-0102', $courses[0]['sections']);

    $this->assertEquals('Regular', $courses[1]['grading_method']);
    $this->assertEquals('AAAS101-0101', $courses[1]['sections']);

    // Verify library is attached.
    $this->assertEquals(['umd_courses/courses_page'], $result['#attached']['library']);
  }

  /**
   * Test coursesPage method with empty API response.
   *
   * @covers ::coursesPage
   */
  public function testCoursesPageEmpty() {
    $this->umdApiClient->expects($this->once())
      ->method('getCourses')
      ->with(30)
      ->willReturn([]);

    $result = $this->controller->coursesPage();

    $this->assertIsArray($result);
    $this->assertEquals('umd_courses_page', $result['#theme']);
    $this->assertEquals([], $result['#courses']);
  }

  /**
   * Test coursesPage method with complex nested data.
   *
   * @covers ::coursesPage
   */
  public function testCoursesPageComplexData() {
    $mockCourses = [
      [
        'course_id' => 'COMPLEX100',
        'name' => 'Complex Course',
        'grading_method' => [
          'Regular',
          ['Nested1', 'Nested2'],
          'Pass-Fail',
        ],
        'gen_ed' => 'Already a string',
        'sections' => NULL,
      ],
    ];

    $this->umdApiClient->expects($this->once())
      ->method('getCourses')
      ->with(30)
      ->willReturn($mockCourses);

    $result = $this->controller->coursesPage();

    $courses = $result['#courses'];

    // Check complex array processing.
    $this->assertEquals('Regular, Nested1, Nested2, Pass-Fail', $courses[0]['grading_method']);
    
    // Check that strings remain unchanged.
    $this->assertEquals('Already a string', $courses[0]['gen_ed']);
    
    // Check that NULL sections field is preserved.
    $this->assertNull($courses[0]['sections']);
  }

  /**
   * Test coursesPage method processes only array fields.
   *
   * @covers ::coursesPage
   */
  public function testCoursesPageProcessesOnlyArrays() {
    $mockCourses = [
      [
        'course_id' => 'TEST100',
        'grading_method' => 'String grading method',  // Not an array.
        'gen_ed' => ['DSHU', 'DVUP'],  // Array.
        'core' => 'String core',  // Not an array.
        'sections' => ['SEC1', 'SEC2'],  // Array.
        'other_field' => ['Not processed'],  // Should remain as array.
      ],
    ];

    $this->umdApiClient->expects($this->once())
      ->method('getCourses')
      ->with(30)
      ->willReturn($mockCourses);

    $result = $this->controller->coursesPage();

    $courses = $result['#courses'];

    // Arrays should be converted to strings.
    $this->assertEquals('DSHU, DVUP', $courses[0]['gen_ed']);
    $this->assertEquals('SEC1, SEC2', $courses[0]['sections']);

    // Non-arrays should remain unchanged.
    $this->assertEquals('String grading method', $courses[0]['grading_method']);
    $this->assertEquals('String core', $courses[0]['core']);

    // Other fields should not be processed.
    $this->assertEquals(['Not processed'], $courses[0]['other_field']);
  }

}
