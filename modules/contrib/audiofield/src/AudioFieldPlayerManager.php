<?php

namespace Drupal\audiofield;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manages audio player plugins.
 */
class AudioFieldPlayerManager extends DefaultPluginManager {

  /**
   * Constructs a new AudioFieldPlayerManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AudioPlayer', $namespaces, $module_handler, 'Drupal\audiofield\AudioFieldPluginBase', 'Drupal\audiofield\Annotation\AudioPlayer');

    $this->alterInfo('audiofield');
    $this->setCacheBackend($cache_backend, 'audiofield_audioplayer');
  }

}
