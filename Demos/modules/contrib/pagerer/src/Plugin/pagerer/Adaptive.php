<?php

namespace Drupal\pagerer\Plugin\pagerer;

/**
 * Pager style with links to pages following an adaptive logic.
 *
 * Besides links to the 'neigborhood' of current page, creates page links
 * which are adaptively getting closer to a target page, through subsequent
 * calls to the links themselves. More or less, the principle is the same
 * as of the binary search in an ordered list.
 *
 * On a first call, the theme creates links to a list of pages in the
 * neighborood of first page, plus a link to last page, plus links to 2
 * pages in the space between first and last page:
 * - one to the middle,
 * - one to the middle of the space between the first page and the one
 *   above
 *
 * Example - current page in square brackets:
 *
 * page 1 out of 252:
 * -------------------------------------------------------------------
 * [1]  2  3  4  5 . +62 . +125 . 252
 * -------------------------------------------------------------------
 *
 * On subsequent calls, if a link outside of the neighborhood (nicknamed
 * 'adaptive link') is called, then we will assume that the 'target' page
 * looked for is comprised within the interval between the pages to
 * the left and to the right of the link invoked.
 * So, the theme will restrict the range of the pages to be presented
 * in the pager by setting these pages as the min and max boundaries
 * (plus first and last page, which are always displayed to 'release'
 * the restriction), and recalculating the middle and middle-of-the-middle
 * to present the new links.
 *
 * Example (following on from above):
 *
 * click on +62, go to page 63 and lock page 5 (represented as -58 from
 * 63) and 126 (represented as +63 from 63) as new boundaries:
 * -------------------------------------------------------------------
 * 1 . -58 . -31 . -15 . 61  62  [63]  64  65 . +15 . +31 . +63 . 252
 * -------------------------------------------------------------------
 * note how also the space on the left is filled in with links having same
 * absolute offset as the ones to the right.
 *
 * and so on, click on -15, go to page 48 and lock page 32 (represented as
 * -16 from 48) and 61 (represented as +13 from 48):
 * -------------------------------------------------------------------
 * 1 . -16 . -8 . -4 . 46  47  [48]  49  50 . +4 . +8 . +13 . 252
 * -------------------------------------------------------------------
 *
 * Like for the 'pagerer_progressive' theme, links are displayed either as a
 * page number or as an offset from current page. This is controlled via the
 * 'progr_links' theme variable, which can take a value either 'absolute'
 * or 'relative'.
 *
 * @PagererStyle(
 *   id = "adaptive",
 *   title = @Translation("Pager style with links to pages following an adaptive logic"),
 *   short_title = @Translation("Adaptive"),
 *   help = @Translation("Besides links to the 'neigborhood' of current page, creates page links which are adaptively getting closer to a target page, through subsequent calls to the links themselves."),
 *   style_type = "base"
 * )
 */
class Adaptive extends Standard {

  /**
   * {@inheritdoc}
   */
  protected function buildPageList() {
    $current = $this->pager->getCurrentPage();
    $last = $this->pager->getLastPage();

    // Determine adaptive keys coming from query parameters.
    list($pl, $pr, $px) = [0, $last, NULL];
    if ($tmp = $this->pager->getAdaptiveKeys()) {
      // Adaptive keys for the specific element exist.
      $tmp = explode('.', $tmp);
      $pl = isset($tmp[0]) ? ($tmp[0] ? $tmp[0] : 0) : 0;
      $pr = isset($tmp[1]) ? $tmp[1] : $last;
      $px = isset($tmp[2]) ? $tmp[2] : NULL;
    }

    // First.
    $pages[0] = $this->getPageItem(-$current, 'absolute', FALSE, ($current == 0 ? 'page_current' : 'page'), FALSE);
    $pages[0]['href'] = $this->pager->getHref($this->parameters, 0, "0.$last");
    // Last.
    $pages[$last] = $this->getPageItem($last - $current, 'absolute', FALSE, ($current == $last ? 'page_current' : 'page'), FALSE);
    $pages[$last]['href'] = $this->pager->getHref($this->parameters, $last, "0.$last");
    // Neighborhood.
    $pages = $this->buildNeighborhoodPageList($pages);
    // Adaptive keys left pointed page.
    if (($pl > 0) and !isset($pages[$pl])) {
      $pages[$pl] = $this->getPageItem($pl - $current, $this->getOption('progr_links'), TRUE);
      $pages[$pl]['outer_page'] = TRUE;
    }
    // Adaptive keys right pointed page.
    if (($pr < $last) and !isset($pages[$pr])) {
      $pages[$pr] = $this->getPageItem($pr - $current, $this->getOption('progr_links'), TRUE);
      $pages[$pr]['outer_page'] = TRUE;
    }
    // Adaptive pages.
    $pages += $this->buildAdaptivePageList($pages, $pl, $pr, $px);
    ksort($pages);

    // Enrich pages with adaptive markers.
    if ($pages) {
      $kpages = array_keys($pages);
      // Determines first adaptive pages left and right of the neighborhood,
      // if existing.
      $la = $ra = NULL;
      for ($x = 1; $x < count($kpages) - 1; $x++) {
        if (isset($pages[$kpages[$x]]['outer_page'])) {
          if ($kpages[$x] < $current) {
            $la = $kpages[$x];
          }
          if ($kpages[$x] > $current) {
            $ra = $kpages[$x];
            break;
          }
        }
      }
      // Set adaptive markers.
      for ($x = 1; $x < count($kpages) - 1; $x++) {
        $d = &$pages[$kpages[$x]];
        // Adaptive page.
        if (isset($d['outer_page'])) {
          $d['href'] = $this->pager->getHref($this->parameters, $kpages[$x], $kpages[$x - 1] . '.' . $kpages[$x + 1]);
          continue;

        }
        // Else, neighborhood page.
        // Set left page and right page pointers.
        if ($px) {
          $xpl = $pl;
          $xpr = $pr;
        }
        else {
          $xpl = $pl ? $pl : 0;
          $xpr = $pr ? $pr : $last;
        }
        // Set holding marker - determine left and right offset
        // of the page vs current page.
        $off = NULL;
        $xpx = NULL;
        if ($kpages[$x] < $current) {
          $off = $la ? $kpages[$x] - $la : NULL;
        }
        elseif ($kpages[$x] > $current) {
          $off = $ra ? $ra - $kpages[$x] : NULL;
        }
        // If an offset exists, and is larger than half neighborhood,
        // then an holding marker is set. If offset is null, then
        // there are no left (or right) adaptive pointers, so we will
        // reset adaptive keys.
        if ($off) {
          $pager_middle = ceil($this->getOption('quantity') / 2);
          if ($off > $pager_middle) {
            $xpx = !is_null($px) ? $px : $current;
          }
        }
        else {
          if ($kpages[$x] < $current) {
            $xpl = 0;
            $xpr = $current;
          }
          elseif ($kpages[$x] > $current) {
            $xpl = $current;
            $xpr = $last;
          }
        }
        if ($xpx) {
          $page_ak_curr = implode('.', [$xpl, $xpr, $xpx]);
        }
        else {
          $page_ak_curr = implode('.', [$xpl, $xpr]);
        }
        $d['href'] = $this->pager->getHref($this->parameters, $kpages[$x], $page_ak_curr);
      }
    }
    return $pages;
  }

  /**
   * Returns an array of pages using an adaptive logic.
   *
   * @param array $pages
   *   Array of pages already enlisted, to prevent override.
   * @param int $l
   *   Adaptive lock to left page.
   * @param int $r
   *   Adaptive lock to right page.
   * @param int $x
   *   Adaptive center lock for neighborhood.
   *
   * @return array
   *   Render array of pages items, with a 'outer_page' key set to TRUE.
   */
  protected function buildAdaptivePageList(array &$pages, $l, $r, $x) {
    $current = $this->pager->getCurrentPage();

    $x = is_null($x) ? $current : $x;

    // Space on the left of the holding marker.
    $sl = $x - $l;
    // Space on the right of the holding marker.
    $sr = $r - $x;
    // Half of the maximum space either side to calculate from.
    $m = max($sl, $sr) / 2;
    for ($i = 0; $i < 2; $i++) {
      $off = intval($m * pow(0.5, $i));
      // Pages on the left.
      $p = $x - $off;
      if ($p > $l and $p < $current and !isset($pages[$p]) and !(isset($pages[$p - 1]) or isset($pages[$p + 1]))) {
        $pages[$p] = $this->getPageItem($p - $current, $this->getOption('progr_links'), TRUE);
        $pages[$p]['outer_page'] = TRUE;
      }
      // Pages on the right.
      $p = $x + $off;
      if ($p < $r and $p > $current and !isset($pages[$p]) and !(isset($pages[$p - 1]) or isset($pages[$p + 1]))) {
        $pages[$p] = $this->getPageItem($p - $current, $this->getOption('progr_links'), TRUE);
        $pages[$p]['outer_page'] = TRUE;
      }
    }
    return $pages;
  }

}
