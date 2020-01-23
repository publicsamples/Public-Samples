<?php

namespace Drupal\ace_editor\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines AceEditor as an Editor plugin.
 *
 * @Editor(
 *   id = "ace_editor",
 *   label = "Ace Editor",
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = FALSE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class AceEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    $config = \Drupal::config('ace_editor.settings')->get();
    return $config;
  }

  /**
   * Returns a settings form to configure this text editor.
   *
   * @param array $settings
   *   An array containing form configuration.
   *
   * @return array
   *   A primary render array for the settings form.
   */
  public function getForm(array $settings) {

    $config = \Drupal::config('ace_editor.settings');

    return [
      'theme' => [
        '#type' => 'select',
        '#title' => t('Theme'),
        '#options' => $config->get('theme_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['theme'],
      ],
      'syntax' => [
        '#type' => 'select',
        '#title' => t('Syntax'),
        '#description' => t('The syntax that will be highlighted.'),
        '#options' => $config->get('syntax_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['syntax'],
      ],
      'height' => [
        '#type' => 'textfield',
        '#title' => t('Height'),
        '#description' => t('The height of the editor in either pixels or percents.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['height'],
      ],
      'width' => [
        '#type' => 'textfield',
        '#title' => t('Width'),
        '#description' => t('The width of the editor in either pixels or percents.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['width'],
      ],
      'font_size' => [
        '#type' => 'textfield',
        '#title' => t('Font size'),
        '#description' => t('The font size used in the editor.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['font_size'],
      ],
      'line_numbers' => [
        '#type' => 'checkbox',
        '#title' => t('Show line numbers'),
        '#default_value' => $settings['line_numbers'],
      ],
      'print_margins' => [
        '#type' => 'checkbox',
        '#title' => t('Show print margin (80 chars)'),
        '#default_value' => $settings['print_margins'],
      ],
      'show_invisibles' => [
        '#type' => 'checkbox',
        '#title' => t('Show invisible characters (whitespaces, EOL...)'),
        '#default_value' => $settings['show_invisibles'],
      ],
      'use_wrap_mode' => [
        '#type' => 'checkbox',
        '#title' => t('Toggle word wrapping'),
        '#default_value' => $settings['use_wrap_mode'],
      ],
      'auto_complete' => [
        '#type' => 'checkbox',
        '#title' => t('Enable Autocomplete (Ctrl+Space'),
        '#default_value' => isset($settings['auto_complete']) ? $settings['auto_complete'] : TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState, Editor $editor) {

    $settings = $editor->getSettings();

    $form = [];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Ace Editor Settings'),
      '#collapsable' => TRUE,
    ];

    if (array_key_exists('fieldset', $settings)) {
      $form['fieldset'] = array_merge($form['fieldset'], $this->getForm($settings['fieldset']));
    }
    else {
      $form['fieldset'] = array_merge($form['fieldset'], $this->getForm($settings));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $formState) {

  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    // Get default ace_editor configuration.
    $config = $config = \Drupal::config('ace_editor.settings');

    // Get theme and mode.
    $theme = trim($editor->getSettings()['fieldset']['theme']);
    $mode = trim($editor->getSettings()['fieldset']['syntax']);

    // Check if theme and mode library exist.
    $theme_exist = \Drupal::service('library.discovery')->getLibraryByName('ace_editor', 'theme.' . $theme);
    $mode_exist = \Drupal::service('library.discovery')->getLibraryByName('ace_editor', 'mode.' . $mode);

    // ace_editor/primary the basic library for ace_editor.
    $libs = [ 'ace_editor/primary' ];

    if ($theme_exist) {
      $libs[] = 'ace_editor/theme.' . $theme;
    }
    else {
      $libs[] = 'ace_editor/theme.' . $config->get('theme');
    }

    if ($mode_exist) {
      $libs[] = 'ace_editor/mode.' . $mode;
    }
    else {
      $libs[] = 'ace_editor/mode.' . $config->get('syntax');
    }

    return $libs;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsSettings(Editor $editor) {
    // Pass settings to javascript.
    return $editor->getSettings()['fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit(array $form, FormStateInterface $formState) {
    return $form;
  }

}
