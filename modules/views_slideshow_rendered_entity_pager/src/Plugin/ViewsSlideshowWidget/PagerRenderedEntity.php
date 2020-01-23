<?php

/**
 * @file
 * Contains Drupal\views_slideshow_rendered_entity_pager\ViewsSlideshowWidget\PagerRenderedEntity.
 */

namespace Drupal\views_slideshow_rendered_entity_pager\Plugin\ViewsSlideshowWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_slideshow\ViewsSlideshowWidgetBase;

/**
 * Provides a pager using rendered entity.
 *
 * @ViewsSlideshowWidget(
 *   id = "views_slideshow_rendered_entity_pager",
 *   type = "views_slideshow_pager",
 *   label = @Translation("Rendered entity"),
 * )
 */
class PagerRenderedEntity extends ViewsSlideshowWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'views_slideshow_rendered_entity_pager_view_mode' => array('default' => ''),
      'views_slideshow_rendered_entity_pager_hover' => array('default' => 0),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $baseTable = $this->getConfiguration()['view']->storage->get('base_table');
    $entityType = \Drupal::service('views.views_data')->get($baseTable)['table']['entity type'];
    $viewModes = \Drupal::entityManager()->getViewModes($entityType);

    $options = array();
    if (!empty($viewModes)) {
      foreach ($viewModes as $mode => $settings) {
        $options[$mode] = $settings['label'];
      }
    }

    // Display a list only if user has selected "Rendered entity" as pager style.
    $form['views_slideshow_rendered_entity_pager_view_mode'] = array(
      '#type' => 'select',
      '#title' => t('Pager view mode'),
      '#options' => $options,
      '#default_value' => $this->getConfiguration()['views_slideshow_rendered_entity_pager_view_mode'],
      '#description' => t('Choose the view mode that will appear in the pager.'),
      '#states' => array(
        'visible' => array(
          ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => array('checked' => TRUE),
          ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' => array('value' => 'views_slideshow_rendered_entity_pager'),
        ),
      ),
    );

    // Add field to see if they would like to activate slide and pause on pager
    // hover.
    $form['views_slideshow_rendered_entity_pager_hover'] = array(
      '#type' => 'checkbox',
      '#title' => t('Activate Slide and Pause on Pager Hover'),
      '#default_value' => $this->getConfiguration()['views_slideshow_rendered_entity_pager_hover'],
      '#description' => t('Should the slide be activated and paused when hovering over a pager item.'),
      '#states' => array(
        'visible' => array(
          ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => array('checked' => TRUE),
          ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' => array('value' => 'views_slideshow_rendered_entity_pager'),
        ),
      ),
    );

    return $form;
  }
}