<?php

namespace Drupal\pagerer\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Pagerer presets' panes.
 */
class PagererPresetPaneEditForm extends PagererPresetFormBase {

  /**
   * Pagerer pane label literals.
   *
   * @var array
   */
  protected $paneLabels;

  /**
   * Pagerer pane being edited.
   *
   * @var string
   */
  protected $pane;

  /**
   * This pane's style.
   *
   * @var string
   */
  protected $style;

  /**
   * This pane's config.
   *
   * @var array
   */
  protected $config;

  /**
   * This pane's plugin.
   *
   * @var \Drupal\pagerer\Plugin\PagererStyleInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pane = NULL) {
    $this->paneLabels = [
      'left' => $this->t('left'),
      'center' => $this->t('center'),
      'right' => $this->t('right'),
    ];
    $this->pane = $pane;
    $this->style = $this->entity->getPaneData($pane, 'style');
    $this->config = $this->entity->getPaneData($pane, 'config') ?: [];
    $this->plugin = $this->styleManager->createInstance($this->style, $this->config);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Pane settings form.
    $form['#title'] = $this->t(
      "%preset_name - @pane pane settings",
      [
        '%preset_name' => $this->entity->label(),
        '@pane' => $this->paneLabels[$this->pane],
      ]
    );

    // In pane edit, do not show the preset name.
    $form['id']['#type'] = 'hidden';
    $form['label']['#type'] = 'hidden';

    // Pane style name.
    $plugin_definition = $this->styleManager->getDefinition($this->style);
    $form['style_label'] = [
      '#type' => 'item',
      '#title' => $this->t("Pane style"),
      '#markup' => !empty($plugin_definition) ? $plugin_definition['short_title'] : NULL,
      '#description' => $this->t("To change the pane style, go back to the 'Edit pager' form."),
    ];

    // Get the config piece from the plugin.
    $form['config'] = $this->plugin->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t("Cancel"),
      '#attributes' => ['class' => ['button']],
    ] + $this->entity->toUrl('edit-form')->toRenderArray();

    // Drop standard delete action.
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->validateConfigurationForm($form, $form_state);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->setConfigurationContext($this->entity, $this->pane);
    $this->plugin->submitConfigurationForm($form, $form_state);
    parent::submitForm($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->messenger->addMessage(
      $this->t(
        'The @pane pane configuration has been saved.',
        [
          '@pane' => $this->paneLabels[$this->pane],
        ]
      ),
      'status'
    );
  }

}
