<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "field_formatter_from_view_display",
 *   label = @Translation("Field formatter from view display"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldFormatterFromViewDisplay extends FieldFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldFormatterFromViewDisplay object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'view_mode' => 'default',
      'field_name' => '',
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $options = [];
    foreach ($this->entityTypeManager->getStorage('entity_view_mode')->loadMultiple() as $id => $view_mode) {
      // Filter out view modes that have status set to FALSE since they will
      // reuse the 'default' display settings by default.
      if ($view_mode->getTargetType() == $this->fieldDefinition->getSetting('target_type') && $view_mode->status()) {
        $options[$id] = $view_mode->label();
      }
    }
    $form['view_mode'] = [
      '#title' => $this->t('View mode'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('view_mode'),
      '#empty_option' => 'Default',
      '#empty_value' => 'default',
    ];

    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->getSetting('field_name'),
      '#options' => $this->getAvailableFieldNames(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewDisplay($bundle_id) {
    if (!isset($this->viewDisplay[$bundle_id])) {
      $field_name = $this->getSetting('field_name');
      $entity_type_id = $this->fieldDefinition->getSetting('target_type');
      if (($view_mode = $this->getSetting('view_mode')) && $view_display = EntityViewDisplay::load($entity_type_id . '.' . $bundle_id . '.' . $view_mode)) {
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
        $components = $view_display->getComponents();
        foreach ($components as $component_name => $component) {
          if ($component_name != $field_name) {
            $view_display->removeComponent($component_name);
          }
        }
        $this->viewDisplay[$bundle_id] = $view_display;
      }
    }
    return $this->viewDisplay[$bundle_id];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($field_name = $this->getSetting('field_name')) {
      $summary[] = $this->t('Field %field_name displayed.', ['%field_name' => $field_name]);
    }
    else {
      $summary[] = $this->t('Field not configured.');
    }

    if ($view_mode = $this->getSetting('view_mode')) {
      $summary[] = $this->t('View display %view_mode used.', ['%view_mode' => $view_mode]);
    }
    else {
      $summary[] = $this->t('View display not configured.');
    }

    return $summary;
  }

}
