<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "field_formatter_with_inline_settings",
 *   label = @Translation("Field formatter with inline settings"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldFormatterWithInlineSettings extends FieldFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

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
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * Constructs a FieldFormatterWithInlineSettings object.
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
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The formatter plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFieldManager $entity_field_manager, FormatterPluginManager $formatter_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFieldManager = $entity_field_manager;
    $this->formatterPluginManager = $formatter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'field_name' => '',
      'type' => '',
      'settings' => [],
      'label' => 'above',
    ];
  }

  /**
   * Get field definition for given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The field definition.
   */
  protected function getFieldDefinition(FieldStorageDefinitionInterface $field_storage_definition) {
    return BaseFieldDefinition::createFromFieldStorageDefinition($field_storage_definition);
  }

  /**
   * Get all available formatters by loading available ones and filtering out.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   *
   * @return string[]
   *   The field formatter labels keys by plugin ID.
   */
  protected function getAvailableFormatterOptions(FieldStorageDefinitionInterface $field_storage_definition) {
    $field_definition = $this->getFieldDefinition($field_storage_definition);
    $formatters = $this->formatterPluginManager->getOptions($field_storage_definition->getType());
    $formatter_instances = array_map(function($formatter_id) use ($field_definition) {
      $configuration = [
        'field_definition' => $field_definition,
        'settings' => [],
        'label' => '',
        'view_mode' => '',
        'third_party_settings' => [],
      ];
      return $this->formatterPluginManager->createInstance($formatter_id, $configuration);
    }, array_combine(array_keys($formatters), array_keys($formatters)));
    $filtered_formatter_instances = array_filter($formatter_instances, function (FormatterInterface $formatter) use ($field_definition) {
      return $formatter->isApplicable($field_definition);
    });
    $options = array_map(function (FormatterInterface $formatter) {
      return $formatter->getPluginDefinition()['label'];
    }, $filtered_formatter_instances);
    return $options;
  }

  /**
   * Ajax submit callback for field name change.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The replaced form substructure.
   */
  public static function onFieldNameChange(array $form, FormStateInterface $form_state) {
    return $form['fields'][$form_state->getStorage()['plugin_settings_edit']]['plugin']['settings_edit_form']['settings'];
  }

  /**
   * Ajax submit callback for formatter type change.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The replaced form substructure.
   */
  public static function onFormatterTypeChange(array $form, FormStateInterface $form_state) {
    return $form['fields'][$form_state->getStorage()['plugin_settings_edit']]['plugin']['settings_edit_form']['settings']['settings'];
  }

  /**
   * Rebuilds the form on select submit.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function rebuildSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // Name of the field this formatter is currently displaying.
    $target_entity_type_id = $this->fieldDefinition->getSetting('target_type');
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($target_entity_type_id);
    $formatted_field_name = $this->getSettingFromFormState($form_state, 'field_name');
    // In case there is no definition for specified field.
    if (isset($field_storage_definitions[$formatted_field_name])) {
      $field_storage = $field_storage_definitions[$formatted_field_name];
      $formatter_options = $this->getAvailableFormatterOptions($field_storage);
    }

    $form['#prefix'] = '<div id="field-formatter-ajax">';
    $form['#suffix'] = '</div>';
    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field name'),
      '#default_value' => $formatted_field_name,
      '#options' => $this->getAvailableFieldNames(),
      // Note: We cannot use ::foo syntax, because the form is the entity form
      // display.
      '#ajax' => [
        'callback' => [get_called_class(), 'onFieldNameChange'],
        'wrapper' => 'field-formatter-ajax',
        'method' => 'replace',
      ],
      '#submit' => [[get_called_class(), 'rebuildSubmit']],
      '#executes_submit_callback' => TRUE,
    ];

    $form['label'] = [
      '#type' => 'select',
      '#title' => $this->t('Label'),
      '#options' => [
        'above' => $this->t('Above'),
        'inline' => $this->t('Inline'),
        'hidden' => '- ' . $this->t('Hidden') . ' -',
        'visually_hidden' => '- ' . $this->t('Visually Hidden') . ' -',
      ],
      '#default_value' => $this->getSettingFromFormState($form_state, 'label'),
    ];

    if ($formatted_field_name && isset($formatter_options)) {
      $formatter_type = $this->getSettingFromFormState($form_state, 'type');
      $settings = $this->getSettingFromFormState($form_state, 'settings');
      if (!isset($formatter_options[$formatter_type])) {
        $formatter_type = key($formatter_options);
        $settings = [];
      }

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Formatter'),
        '#options' => $formatter_options,
        '#default_value' => $formatter_type,
        // Note: We cannot use ::foo syntax, because the form is the entity form
        // display.
        '#ajax' => [
          'callback' => [get_called_class(), 'onFormatterTypeChange'],
          'wrapper' => 'field-formatter-settings-ajax',
          'method' => 'replace',
        ],
        '#submit' => [[get_called_class(), 'rebuildSubmit']],
        '#executes_submit_callback' => TRUE,
      ];

      $options = [
        'field_definition' => $this->getFieldDefinition($field_storage),
        'configuration' => [
          'type' => $formatter_type,
          'settings' => $settings,
          'label' => '',
          'weight' => 0,
        ],
        'view_mode' => '_custom',
      ];

      // Get the formatter settings form.
      $settings_form = ['#value' => []];
      if ($formatter = $this->formatterPluginManager->getInstance($options)) {
        $settings_form = $formatter->settingsForm([], $form_state);
      }
      $form['settings'] = $settings_form;
      $form['settings']['#prefix'] = '<div id="field-formatter-settings-ajax">';
      $form['settings']['#suffix'] = '</div>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewDisplay($bundle_id) {
    if (!isset($this->viewDisplay[$bundle_id])) {

      $display = EntityViewDisplay::create([
        'targetEntityType' => $this->fieldDefinition->getSetting('target_type'),
        'bundle' => $bundle_id,
        'status' => TRUE,
      ]);
      $display->setComponent($this->getSetting('field_name'), [
        'type' => $this->getSetting('type'),
        'settings' => $this->getSetting('settings'),
        'label' => $this->getSetting('label'),
      ]);
      $this->viewDisplay[$bundle_id] = $display;
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

    if ($type = $this->getSetting('type')) {
      $summary[] = $this->t('Formatter %type used.', ['%type' => $type]);
    }
    else {
      $summary[] = $this->t('Formatter not configured.');
    }

    return $summary;
  }

  protected function getSettingFromFormState(FormStateInterface $form_state, $setting) {
    $field_name = $this->fieldDefinition->getName();
    if ($form_state->hasValue(['fields', $field_name, 'settings_edit_form', 'settings', $setting])) {
      return $form_state->getValue(['fields', $field_name, 'settings_edit_form', 'settings', $setting]);
    }
    return $this->getSetting($setting);
  }

}
