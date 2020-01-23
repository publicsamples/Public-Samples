<?php

namespace Drupal\pagerer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a Pagerer preset configuration entity.
 *
 * @todo implement auto-route generation? see #2350509
 *
 * @ConfigEntityType(
 *   id = "pagerer_preset",
 *   label = @Translation("Pagerer pager"),
 *   label_singular = @Translation("pager"),
 *   label_plural = @Translation("pagers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pager",
 *     plural = "@count pagers",
 *   ),
 *   admin_permission = "administer site configuration",
 *   config_prefix = "preset",
 *   handlers = {
 *     "list_builder" = "Drupal\pagerer\PagererPresetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pagerer\Form\PagererPresetAddForm",
 *       "edit" = "Drupal\pagerer\Form\PagererPresetEditForm",
 *       "delete" = "Drupal\pagerer\Form\PagererPresetDeleteForm",
 *       "pane_edit" = "Drupal\pagerer\Form\PagererPresetPaneEditForm",
 *       "pane_reset" = "Drupal\pagerer\Form\PagererPresetPaneResetForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/user-interface/pagerer/preset/manage/{pagerer_preset}",
 *     "delete-form" = "/admin/config/user-interface/pagerer/preset/manage/{pagerer_preset}/delete",
 *     "collection" = "/admin/config/user-interface/pagerer",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "panes",
 *   }
 * )
 */
class PagererPreset extends ConfigEntityBase {

  use SchemaCheckTrait;

  /**
   * The preset id (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The preset label.
   *
   * @var string
   */
  protected $label;

  /**
   * Panes metadata.
   *
   * @var array
   */
  protected $panes;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // Set default preset data if not set.
    if (!isset($values['panes'])) {
      $values['panes'] = \Drupal::config('pagerer.style.multipane')->get('default_config.panes');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Remove configuration keys not relevant for the current setup.
    /** @var \Drupal\Core\Config\TypedConfigManager */
    $typed_config_manager = \Drupal::service('config.typed');
    foreach (['left', 'center', 'right'] as $pane) {
      $errors = $this->checkConfigSchema($typed_config_manager, 'pagerer.style_config.' . $this->getPaneData($pane, 'style'), $this->getPaneData($pane, 'config'));
      if (is_array($errors)) {
        foreach ($errors as $key => $error) {
          if ($error === 'missing schema') {
            list(, $element) = explode(':', $key);
            $this->unsetPaneData($pane, "config.$element");
          }
        }
      }
    }
  }

  /**
   * Gets a pane configuration element.
   *
   * @param string $pane
   *   The pane (left|center|right).
   * @param string $key
   *   The configuration element as a string where '.' identifies array nesting.
   *
   * @return string|array|null
   *   The configuration element.
   */
  public function getPaneData($pane, $key = NULL) {
    if (isset($this->panes[$pane])) {
      if ($key) {
        return isset($this->panes[$pane][$key]) ? $this->panes[$pane][$key] : NULL;
      }
      else {
        return $this->panes[$pane];
      }
    }
    return NULL;
  }

  /**
   * Sets a pane configuration element.
   *
   * @param string $pane
   *   The pane (left|center|right).
   * @param string $key
   *   The configuration element as a string where '.' identifies array nesting.
   * @param mixed $value
   *   The value to be set.
   *
   * @return $this
   */
  public function setPaneData($pane, $key, $value) {
    $keys = explode('.', $key);
    $n = &$this->panes[$pane];
    foreach ($keys as $k) {
      if (!empty($n[$k])) {
        $n = &$n[$k];
      }
      else {
        $n[$k] = [];
        $n = &$n[$k];
      }
    }
    $n = $value;
    return $this;
  }

  /**
   * Unsets a pane configuration element.
   *
   * @param string $pane
   *   The pane (left|center|right).
   * @param string $key
   *   The configuration element as a string where '.' identifies array nesting.
   *
   * @return $this
   */
  public function unsetPaneData($pane, $key) {
    $e = explode('.', $key);
    $x = &$this->panes[$pane];
    for ($i = 0; $i < (count($e) - 1); $i++) {
      if (!isset($x[$e[$i]])) {
        return $this;
      }
      $x = &$x[$e[$i]];
    }
    unset($x[$e[$i]]);
    return $this;
  }

}
