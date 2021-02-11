<?php

namespace Drupal\pagerer;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Cache related helpers.
 */
class PagererCache {

  /**
   * A #pre_render callback for type #pager.
   *
   * Used to associate the config:pagerer.settings cache tag to the #pager
   * type, since Pagerer makes the rendered pager dependent on its
   * configuration.
   *
   * @param array $pager
   *   A renderable array of #type => pager.
   *
   * @return array
   *   The altered renderable array.
   */
  public static function preRenderPager(array $pager) {
    CacheableMetadata::createFromRenderArray($pager)
      ->merge(CacheableMetadata::createFromObject(\Drupal::config('pagerer.settings')))
      ->applyTo($pager);
    return $pager;
  }

}
