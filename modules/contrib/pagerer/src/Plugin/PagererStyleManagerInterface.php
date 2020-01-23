<?php

namespace Drupal\pagerer\Plugin;

/**
 * Provides an interface for the Pagerer style plugins manager.
 */
interface PagererStyleManagerInterface {

  /**
   * Returns a list of Pagerer style plugins.
   *
   * @param string $style_type
   *   The style type for which to build the list {base|composite}.
   *
   * @return array
   *   An associative array of plugins id => short_title
   */
  public function getPluginOptions($style_type);

}
