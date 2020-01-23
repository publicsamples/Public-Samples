<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Base class for field formatters.
 */
abstract class FieldFormatterBase extends EntityReferenceFormatterBase {

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * Gets entity view display for a bundle.
   *
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   Entity view display.
   */
  abstract protected function getViewDisplay($bundle_id);

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entities = $this->getEntitiesToView($items, $langcode);

    $build = [];
    foreach ($entities as $delta => $entity) {
      $build[$delta] = $this->getViewDisplay($entity->bundle())->build($entity);
    }
    return $build;
  }

  /**
   * Gets list of supported fields.
   *
   * @return array
   *   List of fields that are supported keyed by field machine name.
   */
  protected function getAvailableFieldNames() {
    $field_names = [];
    $entity_type_id = $this->fieldDefinition->getSetting('target_type');

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $target_bundles = $this->fieldDefinition->getSetting('handler_settings')['target_bundles'] === NULL ? array_keys($bundle_info->getBundleInfo($entity_type_id)) : $this->fieldDefinition->getSetting('handler_settings')['target_bundles'];
    foreach ($target_bundles as $value) {
      $bundle_field_names = array_map(
        function (FieldDefinitionInterface $field_definition) {
          return $field_definition->getLabel();
        },
        \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $value)
      );
      $field_names = array_merge($field_names, $bundle_field_names);
    }
    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($field_definition->getTargetEntityTypeId());
    return $entity_type->isSubclassOf(FieldableEntityInterface::class);
  }

}
