<?php

namespace Drupal\automatic_taxonomy_terms\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\token\Token;
use InvalidArgumentException;

/**
 * A value object that stores data of configured entity bundles.
 */
class EntityBundleConfiguration {
  use StringTranslationTrait;

  /**
   * Immutable vocabulary configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Service to retrieve token information.
   *
   * @var \Drupal\token\Token
   */
  private $token;

  /**
   * The entity of which to retrieve data.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The label of the entity bundle.
   *
   * @var string
   */
  private $label;

  /**
   * The parent taxonomy term ID.
   *
   * @var int
   */
  private $parent;

  /**
   * Whether to keep this entity in sync with changes of it's creator.
   *
   * @var bool
   */
  private $sync;

  /**
   * The name of the vocabulary.
   *
   * @var string
   */
  private $vocabularyName;

  /**
   * EntityBundleConfiguration constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Immutable vocabulary configuration.
   * @param \Drupal\token\Token $token
   *   Service to retrieve token information.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which to retrieve data.
   * @param array $properties
   *   Additional properties of the entity bundle's configuration.
   */
  public function __construct(ImmutableConfig $config, Token $token, EntityInterface $entity, array $properties) {
    if (!isset($properties['label'])) {
      throw new InvalidArgumentException($this->t('Entity bundle configurations should at least have a "label" property.'));
    }

    $this->config = $config;
    $this->token = $token;
    $this->entity = $entity;
    $this->label = $properties['label'];
    $this->parent = isset($properties['parent']) ? $properties['parent'] : 0;
    $this->sync = (bool) $properties['sync'];
    $this->vocabularyName = isset($properties['vocabulary']) ? $properties['vocabulary'] : NULL;
  }

  /**
   * Immutable vocabulary configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Immutable vocabulary configuration.
   */
  public function config() {
    return $this->config;
  }

  /**
   * The label of the taxonomy term.
   *
   * The label of the taxonomy term is based on the label of the entity bundle's
   * configuration, which has been replaced with tokens.
   *
   * @return string
   *   The label of the taxonomy term.
   */
  public function label() {
    $variables = [
      $this->entity->getEntityTypeId() => $this->entity,
    ];
    return Html::decodeEntities($this->token->replace($this->label, $variables, ['clear' => TRUE]));
  }

  /**
   * The parent taxonomy term ID.
   *
   * @return int
   *   The parent taxonomy term ID.
   */
  public function getTaxonomyTermParentId() {
    return $this->parent;
  }

  /**
   * Whether to keep this entity in sync with changes of it's creator.
   *
   * @return bool
   *   Whether to keep this entity in sync with changes of it's creator.
   */
  public function keepInSync() {
    return $this->sync;
  }

  /**
   * The name of the vocabulary.
   *
   * @return string
   *   The name of the vocabulary.
   */
  public function getVocabularyName() {
    return $this->vocabularyName;
  }

}
