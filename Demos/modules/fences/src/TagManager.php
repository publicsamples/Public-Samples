<?php

namespace Drupal\fences;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Gathers and provides the tags that can be used to wrap fields.
 */
class TagManager extends DefaultPluginManager implements TagManagerInterface, PluginManagerInterface, CachedDiscoveryInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'label' => '',
    'group' => '',
    'descripton' => '',
  ];

  /**
   * Constructs a new TagManager instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->setCacheBackend($cache_backend, 'fences', ['fences']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('fences', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getTagOptions() {
    $options = [
      TagManagerInterface::NO_MARKUP_VALUE => $this->t('None (No wrapping HTML)'),
    ];
    foreach ($this->getDefinitions() as $id => $definition) {
      $options[$definition['group']][$id] = t('@label (@tag)', ['@label' => $definition['label'], '@tag' => $id]);
    }
    return $options;
  }

}
