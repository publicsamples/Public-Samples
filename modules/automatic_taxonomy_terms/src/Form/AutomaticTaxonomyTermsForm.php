<?php

namespace Drupal\automatic_taxonomy_terms\Form;

use Drupal\automatic_taxonomy_terms\Config\VocabularyConfig;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form of automatic taxonomy terms.
 */
class AutomaticTaxonomyTermsForm extends ConfigFormBase {
  /**
   * Configuration of the vocabulary.
   *
   * @var \Drupal\automatic_taxonomy_terms\Config\VocabularyConfig
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('automatic_taxonomy_terms.vocabulary_config'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'));
  }

  /**
   * AutomaticTaxonomyTermsForm constructor.
   *
   * @param \Drupal\automatic_taxonomy_terms\Config\VocabularyConfig $vocabularyConfig
   *   Configuration of the vocabulary.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity type bundle information.
   */
  public function __construct(VocabularyConfig $vocabularyConfig, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->vocabularyConfig = $vocabularyConfig;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'automatic_taxonomy_terms';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_types_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity types'),
      '#open' => TRUE,
    ];
    $configuredContentEntityTypes = $this->vocabularyConfig->getEntityTypes();
    $form['entity_types_group']['entity_types'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getContentEntityTypeOptions(),
      '#default_value' => $configuredContentEntityTypes,
    ];

    foreach ($configuredContentEntityTypes as $entityTypeId) {
      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
      $form["entity_type_group"][$entityTypeId] = [
        '#type' => 'details',
        '#title' => $entityTypeDefinition->getLabel(),
      ];
      $form["entity_type_group"][$entityTypeId]['bundles'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Bundle'),
          $this->t('Taxonomy term parent item'),
          $this->t('Taxonomy term pattern'),
          $this->t('Tokens'),
          $this->t('Keep in sync'),
        ],
      ];
      foreach ($this->getConfigurableEntityTypeBundles($entityTypeId) as $entityTypeBundleId => $entityTypeBundleName) {
        $form["entity_type_group"][$entityTypeId]['bundles']["{$entityTypeId}:{$entityTypeBundleId}"]['bundle'] = [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $entityTypeBundleName,
        ];
        $form["entity_type_group"][$entityTypeId]['bundles']["{$entityTypeId}:{$entityTypeBundleId}"]['parent'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => 'taxonomy_term',
          '#selection_settings' => [
            'target_bundles' => [$this->vocabularyConfig->getTaxonomyVocabularyFromCurrentRoute()],
          ],
          '#title' => $this->t('Taxonomy term parent item'),
          '#description' => $this->t('Leave empty to place the taxonomy term on the top level.'),
          '#title_display' => 'invisible',
          '#default_value' => $this->vocabularyConfig->getBundleConfiguredParentItem($entityTypeId, $entityTypeBundleId),
        ];
        $form["entity_type_group"][$entityTypeId]['bundles']["{$entityTypeId}:{$entityTypeBundleId}"]['label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Taxonomy term pattern'),
          '#title_display' => 'invisible',
          '#default_value' => $this->vocabularyConfig->getBundleConfiguredTermPattern($entityTypeId, $entityTypeBundleId),
        ];
        $form["entity_type_group"][$entityTypeId]['bundles']["{$entityTypeId}:{$entityTypeBundleId}"]['token_help'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => [$entityTypeId],
        ];
        $form["entity_type_group"][$entityTypeId]['bundles']["{$entityTypeId}:{$entityTypeBundleId}"]['sync'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Keep in sync'),
          '#title_display' => 'invisible',
          '#default_value' => $this->vocabularyConfig->getBundleConfiguredTermSync($entityTypeId, $entityTypeBundleId),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get configurable entity type bundles for this taxonomy vocabulary.
   *
   * @param string $entityType
   *   The id of the entity type.
   *
   * @return string[]
   *   The labels of configurable entity type bundles.
   */
  private function getConfigurableEntityTypeBundles($entityType) {
    $entityTypeBundles = $this->getEntityTypeBundles($entityType);

    if ($entityType === 'taxonomy_term') {
      // Exclude the current taxonomy vocabulary to prevent an infinite loop
      // when saving entities of this vocabulary.
      unset($entityTypeBundles[$this->vocabularyConfig->getTaxonomyVocabularyFromCurrentRoute()]);
    }
    return $entityTypeBundles;
  }

  /**
   * Get entity type bundles for an entity type.
   *
   * @param string $entityType
   *   The id of the entity type.
   *
   * @return string[]
   *   The labels of the entity type bundles.
   */
  private function getEntityTypeBundles($entityType) {
    return array_map(function ($bundle) {
      return $bundle['label'];
    }, $this->entityTypeBundleInfo->getBundleInfo($entityType));
  }

  /**
   * Get all options for content entity types.
   *
   * @return string[]
   *   Labels of the options for content entity types.
   */
  private function getContentEntityTypeOptions() {
    $entityTypeDefinitions = array_filter($this->entityTypeManager->getDefinitions(), function ($entityTypeDefinition) {
      return $entityTypeDefinition instanceof ContentEntityTypeInterface;
    });

    return array_map(function (ContentEntityTypeInterface $entityType) {
      return $entityType->getLabel();
    }, $entityTypeDefinitions);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->vocabularyConfig->getStorage()
      ->set('entity_types', $form_state->getValue('entity_types'));

    $formStateValues = $form_state->getValues();
    if (isset($formStateValues['bundles'])) {
      $bundles = array_filter($formStateValues['bundles'], function ($bundle) {
        return $bundle['label'] !== '';
      });
      $bundles = array_map(function ($bundle) {
        $bundle['parent'] = isset($bundle['parent']) ? $bundle['parent'] : 0;
        return $bundle;
      }, $bundles);

      $config->set('bundles', $bundles);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $taxonomyVocabularies = $this->getEntityTypeBundles('taxonomy_term');
    return $this->vocabularyConfig->getEditableConfigNames($taxonomyVocabularies);
  }

}
