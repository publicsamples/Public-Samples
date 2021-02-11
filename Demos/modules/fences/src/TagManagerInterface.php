<?php

namespace Drupal\fences;

/**
 * Gathers and provides the tags that can be used to wrap fields.
 */
interface TagManagerInterface {

  /**
   * The stored value representing "no markup".
   */
  const NO_MARKUP_VALUE = 'none';

  /**
   * Get the tags that can wrap fields.
   *
   * @return array
   *   An array of tags.
   */
  public function getTagOptions();

}
