<?php

namespace Drupal\static_generator_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;


/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "static_generator_block",
 *   admin_label = @Translation("Static Generator Test Block"),
 *   category = @Translation("Static Generator"),
 * )
 */
class StaticGeneratorTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Static Generator Test Block says: Hello World!'),
      '#cache' => ['max-age' => 0],
    ];
  }

}