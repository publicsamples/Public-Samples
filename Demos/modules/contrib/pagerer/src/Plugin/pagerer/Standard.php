<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Core\Template\Attribute;

/**
 * Pager style alike standard Drupal pager theme.
 *
 * Provides links to the 'neigborhood' of current page, plus first/previous/
 * next/last page. Extended control on the pager is available through
 * pagerer's specific variables.
 *
 * @PagererStyle(
 *   id = "standard",
 *   title = @Translation("Style like standard Drupal pager"),
 *   short_title = @Translation("Standard"),
 *   help = @Translation("Provides links to the 'neigborhood' of current page, plus first/previous/next/last page; allows extended control on the elements."),
 *   style_type = "base"
 * )
 */
class Standard extends PagererStyleBase {

  /**
   * Return an array of pages in the neighborhood of the current one.
   *
   * This is in fact generating the same list of pages as standard Drupal
   * pager. The neighborhood is centered on the current page, with
   * ($this->getOption('quantity') / 2) pages falling aside left and right
   * of the current, provided there are enough pages.
   *
   * @return array
   *   render array of pages items.
   */
  protected function buildNeighborhoodPageList(array $pages = []) {

    $quantity = $this->getOption('quantity');
    // Middle is used to "center" pages around the current page.
    $pager_middle = ceil($quantity / 2);
    // Current is the page we are currently paged to.
    $pager_current = $this->pager->getCurrentPage() + 1;
    // First is the first page listed by this pager piece (re quantity).
    $pager_first = $pager_current - $pager_middle + 1;
    // Last is the last page listed by this pager piece (re quantity).
    $pager_last = $pager_current + $quantity - $pager_middle;

    // Prepare for generation loop.
    $i = $pager_first;
    // Adjust "center" if at end of query.
    if ($pager_last > $this->pager->getTotalPages()) {
      $i = $i + ($this->pager->getTotalPages() - $pager_last);
      $pager_last = $this->pager->getTotalPages();
    }
    // Adjust "center" if at start of query.
    if ($i <= 0) {
      $pager_last = $pager_last + (1 - $i);
      $i = 1;
    }

    for (; $i <= $pager_last && $i <= $this->pager->getTotalPages(); $i++) {
      $offset = $i - $pager_current;
      if (!isset($pages[$i - 1])) {
        $pages[$i - 1] = $this->getPageItem($offset, 'absolute', FALSE, ($offset ? 'page' : 'page_current'));
      }
    }

    return $pages;
  }

  /**
   * Return an array of pages.
   *
   * @return array
   *   render array of pages items.
   */
  protected function buildPageList() {
    return $this->buildNeighborhoodPageList();
  }

  /**
   * Return the pager render array.
   *
   * @return array
   *   render array.
   */
  protected function buildPagerItems() {
    $pages = $this->buildPageList();

    $items = [];

    $previous_page = NULL;
    foreach ($pages as $page => $page_data) {
      // If not on first page, then introduce a separator or a breaker between
      // the pages as configured.
      if (isset($previous_page)) {
        if ($page == $previous_page + 1) {
          // Neighbor page.
          if ($this->getOption('separator_display')) {
            $items[] = [
              'text' => $this->getTag('page_separator'),
              'is_separator' => TRUE,
              'attributes' => new Attribute(),
            ];
          }
        }
        else {
          // Outer page.
          if ($this->getOption('breaker_display')) {
            $items[] = [
              'text' => $this->getTag('page_breaker'),
              'is_breaker' => TRUE,
              'attributes' => new Attribute(),
            ];
          }
          elseif ($this->getOption('separator_display')) {
            $items[] = [
              'text' => $this->getTag('page_separator'),
              'is_separator' => TRUE,
              'attributes' => new Attribute(),
            ];
          }
        }
      }
      elseif ($page <> 0 && $this->getOption('fl_breakers') && $this->getOption('breaker_display')) {
        // If on first link, but current page is not first, introduce a
        // breaker before the new link.
        $items[] = [
          'text' => $this->getTag('page_breaker'),
          'is_breaker' => TRUE,
          'attributes' => new Attribute(),
        ];
      }
      // Sets previous page.
      $previous_page = $page;
      $items[] = $page_data;
    }

    // Introduce a breaker after last page, if needed.
    if (($page <> $this->pager->getLastPage()) && $this->getOption('fl_breakers') && $this->getOption('breaker_display')) {
      $items[] = [
        'text' => $this->getTag('page_breaker'),
        'is_breaker' => TRUE,
        'attributes' => new Attribute(),
      ];
    }

    return $items;
  }

}
