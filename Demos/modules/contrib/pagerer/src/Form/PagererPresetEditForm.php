<?php

namespace Drupal\pagerer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form handler for Pagerer Presets.
 */
class PagererPresetEditForm extends PagererPresetFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    // Fake pager for preview.
    $this->pagererFactory->get(0)->init(47884, 50);

    // Add admin UI library.
    $form['#attached']['library'][] = 'pagerer/admin.ui';

    // Deal with AJAX call.
    if ($form_state->getValues()) {
      // Set style selection per pane.
      foreach (['left', 'center', 'right'] as $pane) {
        $this->entity->setPaneData($pane, 'style', $form_state->getUserInput()['panes_container'][$pane]['style']);
      }
    }

    $form['#title'] = $this->t("Edit pager %preset_label", ['%preset_label' => $this->entity->label()]);

    // AJAX messages.
    $form['ajax_messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'pagerer-ajax-messages',
      ],
    ];

    // List of the styles available for the panes.
    $options = ['none' => $this->t('- None -')] + $this->styleManager->getPluginOptions('base');

    // Panes configuration.
    $form['panes_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Panes configuration"),
      '#description' => $this->t("Click 'Configure' to change style settings. Click 'Reset' to reset a pane configuration to its style's default."),
      '#tree'          => TRUE,
    ];
    foreach (['left', 'center', 'right'] as $pane) {
      switch ($pane) {
        case 'left':
          $title = $this->t("Left pane");
          break;

        case 'center':
          $title = $this->t("Center pane");
          break;

        case 'right':
          $title = $this->t("Right pane");
          break;

      }
      $form['panes_container'][$pane] = [
        '#type' => 'fieldset',
        '#title' => $title,
        '#attributes' => ['class' => ['pagerer-panes-container-pane']],
        'style' => [
          '#type' => 'select',
          '#title' => $this->t("Style"),
          '#options' => $options,
          '#default_value' => $this->entity->getPaneData($pane, 'style'),
          '#ajax' => [
            'callback' => '::processStyleChange',
          ],
        ],
        'actions' => [
          '#type' => 'actions',
          'configure' => [
            '#type' => 'submit',
            '#name' => 'config_' . $pane,
            '#value' => $this->t("Configure"),
            '#submit' => ['::submitForm', '::save'],
          ],
          'reset' => [
            '#type' => 'submit',
            '#name' => 'reset_' . $pane,
            '#value' => $this->t("Reset"),
            '#submit' => ['::submitForm', '::save'],
          ],
        ],
      ];
    }

    // Pagerer's preview.
    $form['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Pager preview"),
      '#id' => 'pagerer-pager-preview',
    ];
    $form['preview']['pagerer'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#config' => [
        'panes' => [
          'left' => $this->entity->getPaneData('left'),
          'center' => $this->entity->getPaneData('center'),
          'right' => $this->entity->getPaneData('right'),
        ],
      ],
    ];

    return $form;
  }

  /**
   * Refreshes the form after a change of a style.
   */
  public function processStyleChange($form, FormStateInterface $form_state) {
    $this->messenger->addMessage($this->t('Click on the <em>Save</em> button to confirm the selection.'), 'warning');
    $response = new AjaxResponse();
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new HtmlCommand('#pagerer-ajax-messages', $status_messages));
    $response->addCommand(new ReplaceCommand('#pagerer-pager-preview', $form['preview']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check 'Config' was invoked without selecting a style.
    if (strpos($form_state->getTriggeringElement()['#name'], 'config', 0) === 0) {
      $e = explode('_', $form_state->getTriggeringElement()['#name']);
      $pane = $e[1];
      if ($form_state->getValue(['panes_container', $pane, 'style']) == 'none') {
        $form_state->setErrorByName('panes_container][' . $pane . '][style', $this->t("Select a style before clicking 'Configure'."));
      }
    }

    // Check 'Reset' was invoked without selecting a style.
    if (strpos($form_state->getTriggeringElement()['#name'], 'reset', 0) === 0) {
      $e = explode('_', $form_state->getTriggeringElement()['#name']);
      $pane = $e[1];
      if ($form_state->getValue(['panes_container', $pane, 'style']) == 'none') {
        $form_state->setErrorByName('panes_container][' . $pane . '][style', $this->t("Select a style before clicking 'Reset'."));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set data.
    foreach (['left', 'center', 'right'] as $pane) {
      $this->entity->setPaneData($pane, 'style', $form_state->getValue([
        'panes_container', $pane, 'style',
      ]));
    }
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.pagerer_preset.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $trigger = $form_state->getTriggeringElement()['#name'];
    if (strpos($trigger, 'config', 0) === 0) {
      $e = explode('_', $trigger);
      $pane = $e[1];
      $form_state->setRedirect('entity.pagerer_preset.pane_edit_form', ['pagerer_preset' => $this->entity->id(), 'pane' => $pane]);
    }
    elseif (strpos($trigger, 'reset', 0) === 0) {
      $e = explode('_', $trigger);
      $pane = $e[1];
      $form_state->setRedirect('entity.pagerer_preset.pane_reset_form', ['pagerer_preset' => $this->entity->id(), 'pane' => $pane]);
    }
    else {
      $this->messenger->addMessage($this->t('Changes to the pager %label have been saved.', ['%label' => $this->entity->label()]));
    }
  }

}
