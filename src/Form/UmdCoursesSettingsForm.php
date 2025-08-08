<?php

namespace Drupal\umd_courses\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for UMD Courses settings.
 */
class UmdCoursesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'umd_courses_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['umd_courses.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('umd_courses.settings');

    $form['mock_mode_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable mock mode'),
      '#description' => $this->t('If enabled, the module will use local fixture data instead of making a live API call to the UMD API. This is useful for development and testing.'),
      '#default_value' => $config->get('mock_mode_enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('umd_courses.settings')
      ->set('mock_mode_enabled', $form_state->getValue('mock_mode_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
