<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Basic pager style similar to Views' 'mini' pager.
 *
 * By default presents current page out of total, plus links to previous/next
 * page.
 *
 * @PagererStyle(
 *   id = "basic",
 *   title = @Translation("Basic pager similar to Views mini pager"),
 *   short_title = @Translation("Basic"),
 *   help = @Translation("Presents current page out of total, plus links to previous/next page."),
 *   style_type = "base"
 * )
 */
class Basic extends PagererStyleBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = parent::buildConfigurationForm($form, $form_state);
    unset($config['separators_container']);
    return $config;
  }

  /**
   * Return an empty pager render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildPagerItems() {
    return [];
  }

}
