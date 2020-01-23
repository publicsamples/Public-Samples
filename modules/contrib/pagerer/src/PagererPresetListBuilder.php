<?php

namespace Drupal\pagerer;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;

/**
 * Provides a listing of Pagerer presets.
 */
class PagererPresetListBuilder extends ConfigEntityListBuilder implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Pager name');
    $header['preview'] = $this->t('Preview');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    $row['preview']['class'] = ['pagerer-admin-preset-preview'];
    $row['preview']['data'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#element' => 5,
      '#config' => [
        'preset' => $entity->id(),
      ],
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['url'] = $entity->toUrl('edit-form');
    }

    return $operations;
  }

  /**
   * Create a list of presets suitable for selection.
   */
  public function listOptions() {
    $entities = $this->load();
    $list = [];
    if ($entities) {
      foreach ($entities as $preset) {
        $list[$preset->id()] = $preset->label();
      }
    }
    return $list;
  }

}
