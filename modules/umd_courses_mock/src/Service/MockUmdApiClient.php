<?php

namespace Drupal\umd_courses_mock\Service;

use Drupal\umd_courses\Service\UmdApiClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Mock service for fetching course data from a local fixture file.
 */
class MockUmdApiClient extends UmdApiClient {

  /**
   * The decorated service.
   *
   * @var \Drupal\umd_courses\Service\UmdApiClient
   */
  protected $innerService;

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
   * Constructs a new MockUmdApiClient object.
   *
   * @param \Drupal\umd_courses\Service\UmdApiClient $inner_service
   *   The decorated service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The extension list module service.
   */
  public function __construct(UmdApiClient $inner_service, ConfigFactoryInterface $config_factory, ModuleExtensionList $extension_list_module) {
    $this->innerService = $inner_service;
    $this->config = $config_factory->get('umd_courses.settings');
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourses($limit = 50) {
    if ($this->isMockModeEnabled()) {
      $module_path = $this->extensionListModule->getPath('umd_courses');
      $fixture_path = DRUPAL_ROOT . '/' . $module_path . '/fixtures/courses_api_response.json';

      if (file_exists($fixture_path)) {
        $json_data = file_get_contents($fixture_path);
        return json_decode($json_data, TRUE);
      }
      else {
        // Fallback to the real service if the fixture file is missing.
        \Drupal::messenger()->addError('UMD Courses mock fixture file not found. Falling back to live API.');
        return $this->innerService->getCourses($limit);
      }
    }

    // If mock mode is not enabled, use the original service.
    return $this->innerService->getCourses($limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getCourse($course_id) {
    // We are only mocking the getCourses method. The getCourse method will
    // still use the real API.
    return $this->innerService->getCourse($course_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isMockModeEnabled() {
    return TRUE;
  }

}
