<?php

namespace Drupal\pagerer\Plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the Pagerer style plugin.
 *
 * @Annotation
 *
 * @see \Drupal\pagerer\Plugin\PagererStyleManager
 */
class PagererStyle extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the Pagerer style.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The short title of the Pagerer style.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $short_title;

  /**
   * A informative description of the Pagerer style.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $help;

  /**
   * The style type.
   *
   * Can be 'base' for a base pager style, or 'composite' for special
   * style combinations like e.g. the Pagerer multi-pane pager.
   *
   * @var string
   */
  public $style_type;

}
