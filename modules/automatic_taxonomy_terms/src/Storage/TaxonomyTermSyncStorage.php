<?php

namespace Drupal\automatic_taxonomy_terms\Storage;

use Drupal\automatic_taxonomy_terms\Config\EntityBundleConfiguration;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Storage of the synced taxonomy terms.
 */
class TaxonomyTermSyncStorage {
  use StringTranslationTrait;

  const ENTITY_SYNC_FIELD = 'automatic_entity_creator';

  /**
   * The taxonomy term's creator entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * A value object that stores data of the configured entity bundle.
   *
   * @var \Drupal\automatic_taxonomy_terms\Config\EntityBundleConfiguration
   */
  private $bundleConfiguration;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The synced taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface|null
   */
  private $syncedTaxonomyTerm = NULL;

  /**
   * Storage of taxonomy terms.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * TaxonomyTermSyncStorage constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The taxonomy term's creator entity.
   * @param \Drupal\automatic_taxonomy_terms\Config\EntityBundleConfiguration $bundleConfiguration
   *   A value object that stores data of the configured entity bundle.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   */
  public function __construct(EntityInterface $entity, EntityBundleConfiguration $bundleConfiguration, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entity = $entity;
    $this->bundleConfiguration = $bundleConfiguration;
    $this->entityTypeManager = $entityTypeManager;
    $this->syncedTaxonomyTerm = $this->loadSyncedTaxonomyTerm();
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Load the synced taxonomy term.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The synced taxonomy term when available, otherwise null.
   */
  private function loadSyncedTaxonomyTerm() {
    $taxonomyTerms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => $this->bundleConfiguration->getVocabularyName(),
        self::ENTITY_SYNC_FIELD => $this->entity->uuid(),
      ]);

    return is_array($taxonomyTerms) ? reset($taxonomyTerms) : NULL;
  }

  /**
   * Create a taxonomy term.
   */
  public function create() {
    $this->ensureTaxonomyTermSyncField();

    $taxonomyTerm = $this->termStorage->create([
      'name' => $this->bundleConfiguration->label(),
      'vid' => $this->bundleConfiguration->getVocabularyName(),
      'parent' => $this->bundleConfiguration->getTaxonomyTermParentId(),
      'langcode' => $this->entity->language()->getId(),
      self::ENTITY_SYNC_FIELD => $this->entity->uuid(),
    ]);
    $taxonomyTerm->save();
  }

  /**
   * Ensure that the taxonomy term's sync field exists.
   */
  private function ensureTaxonomyTermSyncField() {
    if (!$this->taxonomyFieldStorageExists(self::ENTITY_SYNC_FIELD)) {
      FieldStorageConfig::create([
        'field_name' => self::ENTITY_SYNC_FIELD,
        'entity_type' => 'taxonomy_term',
        'type' => 'text',
      ])->save();
    }

    if (!$this->taxonomyFieldExists(self::ENTITY_SYNC_FIELD)) {
      FieldConfig::create([
        'field_name' => self::ENTITY_SYNC_FIELD,
        'entity_type' => 'taxonomy_term',
        'bundle' => $this->bundleConfiguration->getVocabularyName(),
        'label' => $this->t('Automatic taxonomy term creation entity'),
      ])->save();
    }
  }

  /**
   * Check if a taxonomy field storage exists.
   *
   * @param string $fieldName
   *   Name of the field.
   *
   * @return bool
   *   Whether the taxonomy field storage exists.
   */
  private function taxonomyFieldStorageExists($fieldName) {
    return (bool) $this->entityTypeManager->getStorage('field_storage_config')
      ->load("taxonomy_term.{$fieldName}");
  }

  /**
   * Check if a taxonomy field exists.
   *
   * @param string $fieldName
   *   Name of the field.
   *
   * @return bool
   *   Whether the taxonomy field exists.
   */
  private function taxonomyFieldExists($fieldName) {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $this->bundleConfiguration->getVocabularyName());
    return array_key_exists($fieldName, $fieldDefinitions);
  }

  /**
   * Update the synced taxonomy term.
   */
  public function update() {
    if ($this->syncedTaxonomyTerm) {
      $this->syncedTaxonomyTerm->set('name', $this->bundleConfiguration->label());
      $this->syncedTaxonomyTerm->save();
    }
  }

  /**
   * Delete the synced taxonomy term.
   */
  public function delete() {
    if ($this->syncedTaxonomyTerm) {
      $this->syncedTaxonomyTerm->delete();
    }
  }

}
