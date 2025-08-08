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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new CoursesController object.
   *
   * @param \Drupal\umd_courses\Service\UmdApiClient $umd_api_client
   *   The UMD API client service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(UmdApiClient $umd_api_client, $module_handler, $messenger) {
    $this->umdApiClient = $umd_api_client;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('umd_courses.api_client'),
      $container->get('module_handler'),
      $container->get('messenger'),
    );
  }

  /**
   * Displays the courses page.
   *
   * @return array
   *   A render array for the courses page.
   */
  public function coursesPage() {
    $courses = $this->umdApiClient->getCourses(30);

    // Display a message if mock mode is enabled.
    if ($this->moduleHandler->moduleExists('umd_courses_mock')) {
      $this->messenger->addStatus($this->t('Mock service: Mock data is currently being used for course listings.'));
    }
    elseif ($this->umdApiClient->isMockModeEnabled()) {
      $this->messenger->addStatus($this->t('Mock mode: Mock data is currently being used for course listings.'));
    }
    elseif ($this->moduleHandler->moduleExists('umd_courses_http_mock')) {
      $this->messenger->addStatus($this->t('Mock HTTP: Mock data is currently being used for course listings.'));
    }

    $build = [
      '#theme' => 'umd_courses_page',
      '#courses' => $courses,
      '#attached' => [
        'library' => [
          'umd_courses/courses_page',
        ],
      ],
    ];

    return $build;
  }

}
