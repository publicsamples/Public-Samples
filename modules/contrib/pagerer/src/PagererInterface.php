<?php

namespace Drupal\pagerer;

/**
 * Provides an interface for the Pagerer pager management class.
 */
interface PagererInterface {

  /**
   * Gets the route name for this pager.
   *
   * @return string
   *   The route name.
   */
  public function getRouteName();

  /**
   * Sets the route name for this pager.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return $this
   */
  public function setRouteName($route_name);

  /**
   * Gets the route parameters for this pager.
   *
   * @return string[]
   *   The route parameters.
   */
  public function getRouteParameters();

  /**
   * Sets the route parameters for this pager.
   *
   * @param string[] $route_parameters
   *   The route parameters.
   *
   * @return $this
   */
  public function setRouteParameters(array $route_parameters);

  /**
   * Gets the pager element.
   *
   * @return int
   *   The pager element.
   */
  public function getElement();

  /**
   * Initializes the pager.
   *
   * @param int $total
   *   The total number of items to be paged.
   * @param int $limit
   *   The number of items the calling code will display per page.
   *
   * @return \Drupal\pagerer\Pagerer
   *   The Pagerer pager object.
   */
  public function init($total, $limit);

  /**
   * Gets the current page.
   *
   * @return int
   *   The page to which the pager is currently positioned to.
   */
  public function getCurrentPage();

  /**
   * Gets total pages in the pager.
   *
   * @return int
   *   The total number of pages managed by the pager.
   */
  public function getTotalPages();

  /**
   * Gets last page in the pager (zero-index).
   *
   * @return int
   *   The index of the last page in the pager.
   */
  public function getLastPage();

  /**
   * Gets total items in the pager.
   *
   * @return int
   *   The total number of items (records) managed by the pager.
   */
  public function getTotalItems();

  /**
   * Gets the items per page.
   *
   * @return int
   *   The number of items (records) in each page.
   */
  public function getLimit();

  /**
   * Gets the adaptive keys of this pager.
   *
   * Used by the Adaptive pager style.
   *
   * @return string
   *   The adaptive keys string, in the format 'L.R.X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   */
  public function getAdaptiveKeys();

  /**
   * Gets the query parameters array of a pager link.
   *
   * @param array $parameters
   *   An associative array of query string parameters to append to the pager
   *   links.
   * @param int $page
   *   The target page.
   * @param string $adaptive_keys
   *   The adaptive keys string, in the format 'L.R.X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   *
   * @return array
   *   The updated array of query parameters.
   */
  public function getQueryParameters(array $parameters, $page, $adaptive_keys = NULL);

  /**
   * Gets a pager link.
   *
   * @param array $parameters
   *   An associative array of query string parameters to append to the pager
   *   links.
   * @param int $page
   *   The target page.
   * @param string $adaptive_keys
   *   The adaptive keys string, in the format 'L.R.X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   * @param bool $set_query
   *   Whether the link should contain the query parameters.
   *
   * @return \Drupal\Core\Url
   *   The Url object for the link.
   */
  public function getHref(array $parameters, $page, $adaptive_keys = NULL, $set_query = TRUE);

}
