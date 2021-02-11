<?php

namespace Drupal\static_generator;

use Drupal\static_generator\Traits\StaticGeneratorBlockCreationTrait as BaseBlockCreationTrait;

/**
 * Provides methods to create and place block with default settings.
 *
 * This trait is meant to be used only by test classes.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
 *   \Drupal\Tests\block\Traits\BlockCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait StaticGeneratorBlockCreationTrait {

  use BaseBlockCreationTrait;

}
