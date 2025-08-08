<?php

namespace Drupal\umd_courses\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\umd_courses\Service\UmdApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying UMD courses.
 */
class CoursesController extends ControllerBase {

  /**
   * The UMD API client service.
   *
   * @var \Drupal\umd_courses\Service\UmdApiClient
   */
  protected $umdApiClient;

  /**
   * Constructs a new CoursesController object.
   *
   * @param \Drupal\umd_courses\Service\UmdApiClient $umd_api_client
   *   The UMD API client service.
   */
  public function __construct(UmdApiClient $umd_api_client) {
    $this->umdApiClient = $umd_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('umd_courses.api_client')
    );
  }

  /**
   * Helper function to safely convert array values to strings.
   *
   * @param array $array
   *   The array to process.
   *
   * @return string
   *   A comma-separated string of the array values.
   */
  private function arrayToString($array) {
    if (!is_array($array)) {
      return (string) $array;
    }
    
    $strings = [];
    foreach ($array as $value) {
      if (is_scalar($value) || $value === NULL) {
        $strings[] = (string) $value;
      }
      elseif (is_array($value)) {
        // Handle nested arrays recursively.
        $strings[] = $this->arrayToString($value);
      }
      else {
        // For objects or other complex types, try to convert to string.
        $strings[] = 'Complex Value';
      }
    }
    
    return implode(', ', array_filter($strings));
  }

  /**
   * Displays the courses page.
   *
   * @return array
   *   A render array for the courses page.
   */
  public function coursesPage() {
    $courses = $this->umdApiClient->getCourses(30);
    
    // Process courses to handle array fields properly.
    $processed_courses = [];
    foreach ($courses as $course) {
      $processed_course = $course;
      
      // Convert array fields to strings to avoid Twig join issues.
      if (isset($course['grading_method']) && is_array($course['grading_method'])) {
        $processed_course['grading_method'] = $this->arrayToString($course['grading_method']);
      }
      
      if (isset($course['gen_ed']) && is_array($course['gen_ed'])) {
        $processed_course['gen_ed'] = $this->arrayToString($course['gen_ed']);
      }
      
      if (isset($course['core']) && is_array($course['core'])) {
        $processed_course['core'] = $this->arrayToString($course['core']);
      }
      
      if (isset($course['sections']) && is_array($course['sections'])) {
        $processed_course['sections'] = $this->arrayToString($course['sections']);
      }
      
      $processed_courses[] = $processed_course;
    }
    
    $build = [
      '#theme' => 'umd_courses_page',
      '#courses' => $processed_courses,
      '#attached' => [
        'library' => [
          'umd_courses/courses_page',
        ],
      ],
    ];

    return $build;
  }

}
