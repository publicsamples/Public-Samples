<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Pager style using a jQuery slider.
 *
 * Page navigation is managed via javascript.
 *
 * @PagererStyle(
 *   id = "slider",
 *   title = @Translation("Slider pager"),
 *   short_title = @Translation("Slider"),
 *   help = @Translation("Pager style using a jQuery slider to select the page."),
 *   style_type = "base"
 * )
 */
class Slider extends PagererStyleBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = parent::buildConfigurationForm($form, $form_state);

    unset($config['separators_container']);

    $config['plugin'] = [
      '#type' => 'details',
      '#title' => $this->t("Slider"),
      '#description' => $this->t("Slider options."),
    ];
    $config['plugin']['slider_width'] = [
      '#type' => 'number',
      '#title' => $this->t("Width"),
      '#field_suffix' => $this->t("em"),
      '#default_value' => $this->configuration['slider_width'],
      '#description' => $this->t("The width of the slider bar. Expressed in 'em' for CSS styling. Leave blank to default to CSS settings."),
      '#required' => FALSE,
      '#size' => 3,
      '#maxlength' => 3,
      '#min' => 1,
    ];
    $options = ['tickmark', 'timeout', 'auto'];
    $options = array_combine($options, $options);
    $config['plugin']['slider_action'] = [
      '#type' => 'select',
      '#title' => $this->t("Action confirmation method"),
      '#options' => $options,
      '#default_value' => $this->configuration['slider_action'],
      '#description' => $this->t("Select how the page relocation should be triggered after it has been selected through the slider. Options are: 'tickmark' (page relocation only occurs after user clicks a tickmark on the slider handle), 'timeout' (page relocation occurs after a grace time has elapsed), 'auto' (the timeout method is automatically selected based on the accuracy of the slider)."),
      '#required' => TRUE,
    ];
    $config['plugin']['slider_action_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t("Confirmation timeout"),
      '#default_value' => $this->configuration['slider_action_timeout'],
      '#description' => $this->t("The grace time (in milliseconds) to wait before the page is relocated, in case 'timeout' confirmation method is selected. '0' will trigger relocation immediately."),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
      '#min' => 0,
    ];
    $options = ['yes', 'no', 'auto'];
    $options = array_combine($options, $options);
    $config['plugin']['slider_navigation_icons'] = [
      '#type' => 'select',
      '#title' => $this->t("Display navigation icons"),
      '#options' => $options,
      '#default_value' => $this->configuration['slider_navigation_icons'],
      '#description' => $this->t("Select whether to display +/- navigation icons on the sides of the slider. Options are 'yes', 'no', 'auto' (the icons are automatically displayed based on the accuracy of the slider)."),
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
    // Prepares state.
    $state_settings = [
      'action' => $this->getOption('slider_action'),
      'timeout' => $this->getOption('slider_action_timeout'),
      'icons' => $this->getOption('slider_navigation_icons'),
      'tickmarkTitle' => $this->getDisplayTag('slider_tickmark_title'),
    ];
    if ($slider_width = $this->getOption('slider_width')) {
      $state_settings['sliderWidth'] = $slider_width;
    }
    $pagerer_widget_id = $this->prepareJsState($state_settings);

    // Add pager items.
    $items = [];
    if ($this->getOption('slider_navigation_icons') <> 'no') {
      $items[] = [
        'widget' => [
          '#theme' => 'pagerer_slider_icon',
          '#icon' => 'circle-minus',
        ],
      ];
    }
    $items[] = [
      'widget' => [
        '#theme' => 'pagerer_slider',
        '#id' => $pagerer_widget_id,
        '#title' => $this->getDisplayTag('slider_title'),
        '#attached' => [
          'drupalSettings' => [
            'pagerer' => ['state' => [$pagerer_widget_id => $state_settings]],
          ],
        ],
      ],
    ];
    if ($this->getOption('slider_navigation_icons') <> 'no') {
      $items[] = [
        'widget' => [
          '#theme' => 'pagerer_slider_icon',
          '#icon' => 'circle-plus',
        ],
      ];
    }
    return $items;
  }

}
