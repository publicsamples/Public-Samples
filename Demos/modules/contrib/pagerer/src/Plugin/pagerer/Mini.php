<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Pager style to display current page/item in a direct entry textbox.
 *
 * Examples:
 *
 * page 9 out of 955, display 'pages':
 * -----------------------------------------------------------
 * <<  <  Page 9 of 955  >  >>
 * -----------------------------------------------------------
 *
 * page 9 out of 955, total items = 47731, limit = 50, display = 'items':
 * -----------------------------------------------------------
 * <<  <  Item 401 of 47731  >  >>
 * -----------------------------------------------------------
 *
 * @PagererStyle(
 *   id = "mini",
 *   title = @Translation("Display current page/item"),
 *   short_title = @Translation("Mini"),
 *   help = @Translation("Pager style to display current page/item in a direct entry textbox."),
 *   style_type = "base"
 * )
 */
class Mini extends PagererStyleBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = parent::buildConfigurationForm($form, $form_state);

    // Reset pager display mode options specifically for mini.
    unset($config['display_container']['display_mode']['#options']['normal']);
    $config['display_container']['display_mode']['#options']['none'] = $this->t('Not displayed.');
    $config['display_container']['display_mode']['#options']['widget'] = $this->t('Input box. Users can enter directly a page/item number to go to.');

    // Remove 'item_ranges' display option.
    unset($config['display']['#options']['item_ranges']);
    $config['display']['#description'] = $this->t("Select whether to display pages or items.");

    // No need for separators in mini.
    unset($config['separators_container']);

    // Add widget resizing option specific for mini.
    $config['plugin'] = [
      '#type' => 'details',
      '#title' => $this->t("Input box"),
      '#description' => $this->t("Input box options."),
    ];
    $config['plugin']['widget_resize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Automatic width adjustment"),
      '#default_value' => $this->configuration['widget_resize'],
      '#description' => $this->t("If set, the input box width will be adjusted dynamically based on the total number of pages/items. When unset, CSS styling will prevail."),
    ];
    $options = ['no', 'yes', 'auto'];
    $options = array_combine($options, $options);
    $config['plugin']['widget_button'] = [
      '#type' => 'select',
      '#title' => $this->t("Trigger button"),
      '#options' => $options,
      '#default_value' => $this->configuration['widget_button'],
      '#description' => $this->t("Select whether to add a clickable navigation button to the input box. Options are 'no' (page relocation will only occur by pressing the 'return' key on the keyboard), 'yes' (button is shown, and styled via CSS), 'auto' (button height is automatically resized to match the input box height)."),
      '#required' => TRUE,
    ];

    return $config;
  }

  /**
   * Return the pager render array.
   *
   * @return array
   *   render array.
   */
  protected function buildPagerItems() {
    // Return immediately if configuration is not set to display the page
    // widget.
    if ($this->getOption('display_mode') != 'widget') {
      return [];
    }

    // Prepares state.
    $state_settings = [
      'widgetResize' => $this->getOption('widget_resize'),
      'widgetButton' => $this->getOption('widget_button'),
    ];
    $pagerer_widget_id = $this->prepareJsState($state_settings);

    // Entry textbox.
    return [
      [
        'widget' => [
          '#theme' => 'pagerer_mini',
          '#id'    => $pagerer_widget_id,
          '#title' => $this->getDisplayTag('widget_title'),
          '#value' => $state_settings['value'],
          '#min'   => 1,
          '#max'   => ($this->getOption('display') == 'pages') ? $state_settings['total'] : $state_settings['totalItems'],
          '#step'  => $state_settings['interval'],
          '#button' => ($this->getOption('widget_button') === 'no') ? FALSE : TRUE,
          '#attached' => [
            'drupalSettings' => [
              'pagerer' => ['state' => [$pagerer_widget_id => $state_settings]],
            ],
          ],
        ],
      ],
    ];
  }

}
