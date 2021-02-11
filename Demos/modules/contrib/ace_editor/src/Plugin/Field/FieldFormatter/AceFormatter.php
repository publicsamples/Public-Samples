<?php

namespace Drupal\ace_editor\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'ace_editor' formatter.
 *
 * @FieldFormatter (
 *   id = "ace_formatter",
 *   label = @Translation("Ace Format"),
 *   field_types = {
 *     "text_with_summary",
 *     "text_long",
 *   }
 * )
 */
class AceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  
  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an AceFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendered service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->renderer = $renderer;
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
			$container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Get default ace_editor configuration.
    $config = \Drupal::config('ace_editor.settings')->get();
    return $config + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = t('Theme: ') . $settings['theme'];
    $summary[] = t('Syntax: ') . $settings['syntax'];
    $summary[] = t('Height: ') . $settings['height'];
    $summary[] = t('Width: ') . $settings['width'];
    $summary[] = t('Font size: ') . $settings['font_size'];
    $summary[] = t('Show line numbers: ') . ($settings['line_numbers'] ? t('On') : t('Off'));
    $summary[] = t('Show print margin: ') . ($settings['print_margins'] ? t('On') : t('Off'));
    $summary[] = t('Show invisible characters: ') . ($settings['show_invisibles'] ? t('On') : t('Off'));
    $summary[] = t('Toggle word wrapping: ') . ($settings['use_wrap_mode'] ? t('On') : t('Off'));

    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {

    $settings = $this->getSettings();

    // $this->getSettings() will return values from defaultSettings() on first use.
    // afterwards it will return the forms saved configuration.
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
        '#description' => t('The the font size of the editor.'),
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
     ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Renders front-end of our formatter.
    $elements = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      $elements[$delta] = [
        '#type' => 'textarea',
        '#value' => $item->value,
        // Attach libraries as per the setting.
        '#attached' => [
          'library' => [
            'ace_editor/formatter'
          ],
          'drupalSettings' => [
             // Pass settings variable ace_formatter to javascript.
            'ace_formatter' => $settings
          ],
        ],
        '#attributes' => [
          'class' => [ 'content' ],
          'readonly' => 'readonly',
        ],
        '#prefix' => '<div class="ace_formatter">',
        '#suffix' => '<div>',
      ];
    }
    return $elements;
  }
}
