<?php

namespace Drupal\pagerer\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Pagerer style plugins.
 */
class PagererStyleManager extends DefaultPluginManager implements PagererStyleManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/pagerer', $namespaces, $module_handler, 'Drupal\pagerer\Plugin\PagererStyleInterface', 'Drupal\pagerer\Plugin\Annotation\PagererStyle');
    $this->alterInfo('pagerer_style_plugin_info');
    $this->setCacheBackend($cache_backend, 'pagerer_style_plugins');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $default_configuration = $this->configFactory->get('pagerer.style.' . $plugin_id)->get('default_config');
    $configuration = NestedArray::mergeDeep($default_configuration, $configuration);
    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginOptions($style_type) {
    $options = [];
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['style_type'] == $style_type) {
        $options[$plugin['id']] = $plugin['short_title'];
      }
    }
    asort($options);
    return $options;
  }

}
