<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Pager style with links to pages progressively more distant from current.
 *
 * Besides links to the 'neigborhood' of current page, creates a list of links
 * which are progressively more distant from current page, displaying either a
 * page number or an offset from current page.
 *
 * This is controlled via the 'progr_links' theme variable, which can take a
 * value either 'absolute' or 'relative'.
 *
 * Examples:
 *
 * page 9 out of 212, progr_links 'absolute', display 'pages':
 * -------------------------------------------------------------------
 * 1  . 4  .  7  8  [9]  10  11  . 14  .  19  .  59  . 109  . 212
 * -------------------------------------------------------------------
 *
 * page 9 out of 212, progr_links 'relative', display 'pages':
 * -------------------------------------------------------------------
 * 1  . -5  .  7  8  [9]  10  11  .  +5  .  +10  . +50  . +100  . 212
 * -------------------------------------------------------------------
 *
 * The 'factors' theme variable controls the quantity of progressive links
 * generated. Each value in the comma delimited string will be used as a
 * scale factor for a progressive series of pow(10, n).
 *
 * Examples:
 * 'factors' => '10'    will generate links for page offsets
 *
 *   ..., -1000, -100, -10, 10, 100, 1000, ....
 *
 * 'factors' => '5,10'  will generate links for page offsets
 *
 *   ..., -1000, -500, -100, -50, -10, -5, 5, 10, 50, 100, 500, 1000, ....
 *
 * etc.
 *
 * @PagererStyle(
 *   id = "progressive",
 *   title = @Translation("Pager style with links to pages progressively more distant"),
 *   short_title = @Translation("Progressive"),
 *   help = @Translation("Besides links to the 'neigborhood' of current page, creates a list of links which are progressively more distant from current page, displaying either a page number or an offset from current page."),
 *   style_type = "base"
 * )
 */
class Progressive extends Standard {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = parent::buildConfigurationForm($form, $form_state);
    $config['display_container']['factors'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Scale factors"),
      '#default_value' => $this->configuration['factors'],
      '#description' => $this->t("Comma delimited string of factors to use to determine progressive links."),
      '#required' => TRUE,
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPageList() {
    $current = $this->pager->getCurrentPage();
    $last = $this->pager->getLastPage();

    // First.
    $pages[0] = $this->getPageItem(-$current, 'absolute', FALSE, ($current == 0 ? 'page_current' : 'page'));
    // Last.
    $pages[$last] = $this->getPageItem($last - $current, 'absolute', FALSE, ($current == $last ? 'page_current' : 'page'));
    // Neighborhood.
    $pages = $this->buildNeighborhoodPageList($pages);

    // Progressive.
    if ($this->getOption('factors')) {
      $factors = explode(',', $this->getOption('factors'));
      foreach ($factors as $scale_factor) {
        $pages = $this->buildProgressivePageList($pages, $scale_factor, 10);
      }
    }
    ksort($pages);
    return $pages;
  }

  /**
   * Return an array of pages progressively more distant from current.
   *
   * @param array $pages
   *   Array of pages already enlisted, to prevent override.
   * @param int $scale_factor
   *   Scale factor to be used in the progressive series.
   * @param int $ratio
   *   Ratio to be used in the progressive series.
   * @param int $limit
   *   (Optional) limit the quantity of pages enlisted.
   *
   * @return array
   *   render array of pages items.
   */
  protected function buildProgressivePageList(array $pages, $scale_factor, $ratio, $limit = NULL) {
    $current = $this->pager->getCurrentPage();
    $total = $this->pager->getTotalPages();
    $last = $this->pager->getLastPage();

    // Avoid endless loop in converging series.
    if ($ratio < 1) {
      $ratio = 1;
    }
    $offset = 0;

    for ($i = 0; TRUE; $i++) {
      // Breaks if limit reached.
      if ($limit and $i > $limit - 1) {
        break;
      }
      // Offset for this cycle.
      $offset = intval($scale_factor * pow($ratio, $i));
      // Breaks if offset > than total pages.
      if ($offset > $total) {
        break;
      }
      // Negative offset.
      $target = $current - $offset;
      if ($target > 0 && !isset($pages[$target])) {
        $pages[$target] = $this->getPageItem(-$offset, $this->getOption('progr_links'), TRUE);
      }
      // Positive offset.
      $target = $current + $offset;
      if ($target < $last && !isset($pages[$target])) {
        $pages[$target] = $this->getPageItem($offset, $this->getOption('progr_links'), TRUE);
      }
    }
    return $pages;
  }

}
