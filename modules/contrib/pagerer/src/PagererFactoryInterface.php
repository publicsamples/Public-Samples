<?php

namespace Drupal\pagerer;

/**
 * Provides an interface for the Pagerer factory.
 */
interface PagererFactoryInterface {

  /**
   * Initialises the pagers.
   */
  public function initPagers();

  /**
   * Returns the pager object for the specified pager element.
   *
   * @param int $element
   *   The pager element.
   *
   * @return \Drupal\pagerer\Pagerer
   *   The pager object.
   */
  public function get($element);

  /**
   * Returns the array of pager objects.
   *
   * @return \Drupal\pagerer\Pagerer[]
   *   The array of pager objects.
   */
  public function all();

}
