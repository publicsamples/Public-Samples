<?php

namespace Drupal\scanner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configuring scanner settings. 
 */
class ScannerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scanner_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scanner.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scanner.admin_settings');

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Default options'),
    ];
    $form['settings']['scanner_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Default: Case Sensitive Search Mode'),
      '#default_value' => $config->get('scanner_mode'),
    ];
    $form['settings']['scanner_wholeword'] = [
      '#type' => 'checkbox',
      '#title' => t('Default: Match Whole Word'),
      '#default_value' => $config->get('scanner_wholeword'),
    ];
    $form['settings']['scanner_regex'] = [
      '#type' => 'checkbox',
      '#title' => t('Default: Regular Expression Search'),
      '#default_value' => $config->get('scanner_regex'),
    ];
    $form['settings']['scanner_published'] = [
      '#type' => 'checkbox',
      '#title' => t('Default: Search Published Nodes Only'),
      '#default_value' => $config->get('scanner_published'),
    ];
    $form['settings']['scanner_pathauto'] = [
      '#type' => 'checkbox',
      '#title' => t('Default: Maintain Custom Aliases'),
      '#default_value' => $config->get('scanner_pathauto'),
    ];

    $langs = $this->getLanguages();
    $form['settings']['scanner_language'] = [
      '#type' => 'select',
      '#title' => t('Default: Content Language'),
      '#options' => !empty($langs) ? $langs : 'en',
      '#default_value' => $config->get('scanner_language'),
    ];

    $content_types = $this->getContentTypes();
    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enabled Entity Types',
      '#options' => !empty($content_types) ? $content_types : null,
      '#default_value' => $config->get('enabled_content_types'),
      '#description' => $this->t('Plugins can be written to add more options here.'),
      '#ajax' => [
        'callback' => [$this, 'getFieldsCallback'],
        'event' => 'change',
        'wrapper' => 'content-type-fields'
      ],
    ];

    if ($form_state->getValue('enabled_content_types')) {
      $fields = $this->getContentTypesFields($form_state);
    }
    else {
      $fields = $this->getContentTypesFields([]);
    }
    $form['fields_of_selected_content_type'] = [
      '#title' => 'Enabled Fields',
      '#type' => 'checkboxes',
      '#options' => !empty($fields) ? $fields : null,
      '#default_value' => $config->get('fields_of_selected_content_type'),
      '#prefix' => '<div id="content-type-fields">',
      '#suffix' => '</div>',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $config = $this->config('scanner.admin_settings')
      ->set('scanner_mode', $form_state->getValue('scanner_mode'))
      ->set('scanner_regex', $form_state->getValue('scanner_regex'))
      ->set('scanner_wholeword', $form_state->getValue('scanner_wholeword'))
      ->set('scanner_published', $form_state->getValue('scanner_published'))
      ->set('scanner_pathauto', $form_state->getValue('scanner_pathauto'))
      ->set('scanner_language', $form_state->getValue('scanner_language'))
      ->set('enabled_content_types', array_filter($form_state->getValue('enabled_content_types')))
      ->set('fields_of_selected_content_type', array_filter($form_state->getValue('fields_of_selected_content_type')))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to build the list of options (entities).
   *
   * @return array
   *   An array containing to options for the field.
   */
  public function getContentTypes() {
    $pluginManager = \Drupal::service('plugin.manager.scanner');
    $entityManager = \Drupal::service('entity.manager');
    // Iterate over the available plugins to get their 'types'
    foreach ($pluginManager->getDefinitions() as $key => $value) {
      switch ($value['type']) {
        case 'node':
          $bundles = $entityManager->getBundleInfo('node');
          foreach ($bundles as $id => $bundle) {
            $options["node:$id"] = 'Node:' . $bundle['label'];
          }
          break;
        case 'user':
          $options['user:user'] = 'User';
          break;
        case 'taxonomy_term':
          $options['taxonomy_term:taxonomy_term'] = 'Taxonomy Term';
          break;
        case 'paragraph':
          $bundles = $entityManager->getBundleInfo('paragraph');
          foreach ($bundles as $id => $bundle) {
            $options["paragraph:$id"] = 'Paragraph: '. $bundle['label'];
          }
          break;
      }
    }
    return $options;
  }

  /**
   * Helper function to build the list of options (fields).
   *
   * @param array $values
   *  The $form_state array.
   * 
   * @return array
   *   An array containing the fields of the configured entities.
   */
  public function getContentTypesFields($values) {
    $options = [];
    if (empty($values)) {
      $config = $this->config('scanner.admin_settings');
      $selected_content_type = $config->get('enabled_content_types');
    }
    else {
      // Removes the options with no value.
      $selected_content_type = array_filter($values->getValue('enabled_content_types'));
    }

    $field_manager = \Drupal::service('entity_field.manager');

    // Iterate through each of the selected content types and get their fields.
    if (!empty($selected_content_type)) {
      foreach ($selected_content_type as $key => $value) {
        list($entityType, $bundle) = explode(':', $key);
        // Get the fields for the given entity type and bundle.
        $field_definitions = $field_manager->getFieldDefinitions($entityType, $bundle);
        foreach ($field_definitions as $field_name => $field_value) {
          $allowed_field_type = ['string', 'text_with_summary', 'text', 'text_long'];
          // We are only interested in certain field types.
          if (in_array($field_value->getType(), $allowed_field_type)) {
            // Skip fields prefixed with parent_.
            if (strpos($field_name,'parent_') !== false) {
              continue;
            }
            $name_with_type = "$entityType:$bundle:$field_name";
            $options[$name_with_type] = '<b>'. ucwords($bundle) . '</b>:' . $field_name;
          }
        }
      }
    }
    return $options;
  }

  /**
   * Helper function to build the list of options (languages).
   *
   * @return array
   *   An array containing the list of enabled languages.
   */
  public function getLanguages() {
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      $langs[$language->getId()] = $this->t($language->getName());
    }
    // Add 'all' option.
    $langs['all'] = 'All';
    return $langs;
  }

  public function getFieldsCallback(array $form, FormStateInterface $form_state) {
    return $form['fields_of_selected_content_type'];
  }

}
