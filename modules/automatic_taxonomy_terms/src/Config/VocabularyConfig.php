<?php

namespace Drupal\automatic_taxonomy_terms\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Configuration of the vocabulary.
 */
class VocabularyConfig {
  const AUTOMATIC_TAXONOMY_TERMS_CONFIG_PREFIX = 'automatic_taxonomy_terms.';

  /**
   * Service to retrieve immutable configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Service for matching routes.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  private $vocabularyConfigName;

  /**
   * Immutable configuration of the vocabulary.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $vocabularyConfig;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Entity type bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * VocabularyConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Service to retrieve immutable configuration.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Service for matching routes.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle information.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->configFactory = $configFactory;
    $this->routeMatch = $routeMatch;
    $this->vocabularyConfigName = self::AUTOMATIC_TAXONOMY_TERMS_CONFIG_PREFIX . $this->getTaxonomyVocabularyFromCurrentRoute();
    $this->vocabularyConfig = $configFactory->get($this->vocabularyConfigName);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Get the taxonomy vocabulary name from the current route.
   *
   * @return string
   *   The taxonomy vocabulary name
   */
  public function getTaxonomyVocabularyFromCurrentRoute() {
    return $this->routeMatch->getParameter('taxonomy_vocabulary');
  }

  /**
   * Get the bundle configured parent item.
   *
   * @param string $entityTypeId
   *   The id of an entity type.
   * @param string $entityTypeBundleId
   *   The id of an entity type bundle.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The taxonomy term that has been configured as parent in the entity bundle
   *   configuration. When no parent is available, null is returned.
   */
  public function getBundleConfiguredParentItem($entityTypeId, $entityTypeBundleId) {
    $configuredBundles = $this->vocabularyConfig->get('bundles');

    if (isset($configuredBundles["{$entityTypeId}:{$entityTypeBundleId}"])) {
      $taxonomyTermId = $configuredBundles["{$entityTypeId}:{$entityTypeBundleId}"]['parent'];
      return $this->entityTypeManager->getStorage('taxonomy_term')
        ->load($taxonomyTermId);
    }
  }

  /**
   * Get the taxonomy term label's pattern value.
   *
   * @param string $entityTypeId
   *   The id of an entity type.
   * @param string $entityTypeBundleId
   *   The id of an entity type bundle.
   *
   * @return string
   *   The taxonomy term label's pattern value.
   */
  public function getBundleConfiguredTermPattern($entityTypeId, $entityTypeBundleId) {
    return $this->getBundleConfiguredValue($entityTypeId, $entityTypeBundleId, 'label');
  }

  /**
   * Get the value of a configured option.
   *
   * @param string $entityTypeId
   *   The id of an entity type.
   * @param string $entityTypeBundleId
   *   The id of an entity type bundle.
   * @param string $option
   *   The name of an option.
   *
   * @return mixed
   *   The value of the option if available, otherwise null.
   */
  private function getBundleConfiguredValue($entityTypeId, $entityTypeBundleId, $option) {
    $configuredBundles = $this->vocabularyConfig->get('bundles');

    if ($configuredBundles && isset($configuredBundles["{$entityTypeId}:{$entityTypeBundleId}"][$option])) {
      return $configuredBundles["{$entityTypeId}:{$entityTypeBundleId}"][$option];
    }
  }

  /**
   * Get the configured value of whether the bundle is being synced.
   *
   * @param string $entityTypeId
   *   The id of an entity type.
   * @param string $entityTypeBundleId
   *   The id of an entity type bundle.
   *
   * @return string
   *   The configured value of whether the bundle is being synced.
   */
  public function getBundleConfiguredTermSync($entityTypeId, $entityTypeBundleId) {
    return $this->getBundleConfiguredValue($entityTypeId, $entityTypeBundleId, 'sync');
  }

  /**
   * Get editable configuration names of vocabularies.
   *
   * @param string[] $taxonomyVocabularies
   *   An array of taxonomy vocabularies keyed by their id.
   *
   * @return array
   *   Editable configuration names of vocabularies.
   */
  public function getEditableConfigNames(array $taxonomyVocabularies) {
    $configNames = [];
    foreach (array_keys($taxonomyVocabularies) as $vocabularyId) {
      $configNames[] = self::AUTOMATIC_TAXONOMY_TERMS_CONFIG_PREFIX . $vocabularyId;
    }
    return $configNames;
  }

  /**
   * Get configured entity types for the vocabulary.
   *
   * @return string[]
   *   The configured entity types for the vocabulary.
   */
  public function getEntityTypes() {
    $configuredEntityTypes = $this->vocabularyConfig->get('entity_types');
    return $configuredEntityTypes ? array_filter($configuredEntityTypes) : [];
  }

  /**
   * Get the editable configuration storage of the vocabulary.
   *
   * @return \Drupal\Core\Config\Config
   *   The editable configuration storage of the vocabulary.
   */
  public function getStorage() {
    return $this->configFactory->getEditable($this->vocabularyConfigName);
  }

  /**
   * Get all immutable taxonomy vocabulary configurations.
   *
   * @return \Drupal\Core\Config\ImmutableConfig[]
   *   All immutable taxonomy vocabulary configurations.
   */
  public function getAllConfigurations() {
    $vocabularyConfigurationNames = [];
    foreach ($this->getTaxonomyVocabularyIds() as $vocabularyName) {
      $vocabularyConfigurationNames[$vocabularyName] = $this->configFactory->get(self::AUTOMATIC_TAXONOMY_TERMS_CONFIG_PREFIX . $vocabularyName);
    }
    return $vocabularyConfigurationNames;
  }

  /**
   * Get all taxonomy vocabulary ids.
   *
   * @return string[]
   *   All taxonomy vocabulary ids.
   */
  private function getTaxonomyVocabularyIds() {
    return array_keys($this->entityTypeBundleInfo->getBundleInfo('taxonomy_term'));
  }

}
