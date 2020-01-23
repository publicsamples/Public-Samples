<?php

namespace Drupal\automatic_taxonomy_terms\Event;

use Drupal\automatic_taxonomy_terms\Config\EntityBundleConfiguration;
use Drupal\automatic_taxonomy_terms\Config\VocabularyConfig;
use Drupal\automatic_taxonomy_terms\Storage\TaxonomyTermSyncStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherEvents;
use Drupal\token\Token;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subcriber that subscribes to changes of entities.
 */
class EventSubscriber implements EventSubscriberInterface {
  /**
   * Configuration of the vocabulary.
   *
   * @var \Drupal\automatic_taxonomy_terms\Config\VocabularyConfig
   */
  private $vocabularyConfig;

  /**
   * Service to retrieve token information.
   *
   * @var \Drupal\token\TokenInterface
   */
  private $token;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherEvents::ENTITY_INSERT => ['onEntityCreate', 20],
      HookEventDispatcherEvents::ENTITY_UPDATE => ['onEntityUpdate', 20],
      HookEventDispatcherEvents::ENTITY_DELETE => ['onEntityDelete', 20],
    ];
  }

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\automatic_taxonomy_terms\Config\VocabularyConfig $vocabularyConfig
   *   Configuration of the vocabulary.
   * @param \Drupal\token\Token $token
   *   Service to retrieve token information.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   */
  public function __construct(VocabularyConfig $vocabularyConfig, Token $token, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->vocabularyConfig = $vocabularyConfig;
    $this->token = $token;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Callback when an entity has been created.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent $entityEvent
   *   The entity event.
   */
  public function onEntityCreate(EntityInsertEvent $entityEvent) {
    $entity = $entityEvent->getEntity();

    foreach ($this->getBundleConfigurationsForEntity($entity) as $bundleConfiguration) {
      $taxonomyTermSyncEntity = new TaxonomyTermSyncStorage($entity, $bundleConfiguration, $this->entityTypeManager, $this->entityFieldManager);
      $taxonomyTermSyncEntity->create();
    }
  }

  /**
   * Get configured bundles for this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return \Drupal\automatic_taxonomy_terms\Config\EntityBundleConfiguration[]
   *   A collection of configured entity bundles.
   */
  private function getBundleConfigurationsForEntity(EntityInterface $entity) {
    $bundleConfigurations = [];
    foreach ($this->vocabularyConfig->getAllConfigurations() as $vocabularyName => $configuration) {
      $entityBundleConfigurationValues = $configuration->get("bundles.{$entity->getEntityTypeId()}:{$entity->bundle()}");
      if (is_array($entityBundleConfigurationValues)) {
        $bundleConfigurations[] = new EntityBundleConfiguration($configuration, $this->token, $entity, $entityBundleConfigurationValues + ['vocabulary' => $vocabularyName]);
      }
    }

    return $bundleConfigurations;
  }

  /**
   * Callback when an entity has been updated.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent $entityEvent
   *   The entity event.
   */
  public function onEntityUpdate(EntityUpdateEvent $entityEvent) {
    $entity = $entityEvent->getEntity();

    foreach ($this->getBundleConfigurationsForEntity($entity) as $bundleConfiguration) {
      if ($bundleConfiguration->keepInSync()) {
        $taxonomyTermSyncEntity = new TaxonomyTermSyncStorage($entity, $bundleConfiguration, $this->entityTypeManager, $this->entityFieldManager);
        $taxonomyTermSyncEntity->update();
      }
    }
  }

  /**
   * Callback when an entity has been deleted.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent $entityEvent
   *   The entity event.
   */
  public function onEntityDelete(EntityDeleteEvent $entityEvent) {
    $entity = $entityEvent->getEntity();

    foreach ($this->getBundleConfigurationsForEntity($entity) as $bundleConfiguration) {
      if ($bundleConfiguration->keepInSync()) {
        $taxonomyTermSyncEntity = new TaxonomyTermSyncStorage($entity, $bundleConfiguration, $this->entityTypeManager, $this->entityFieldManager);
        $taxonomyTermSyncEntity->delete();
      }
    }
  }

}
