<?php

namespace Drupal\audiofield\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an AudioPlayer annotation object..
 *
 * @Annotation
 */
class AudioPlayer extends Plugin {

  /**
   * The ID for the audio player.
   *
   * @var string
   */
  public $id;

  /**
   * The audio player's title.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title = "";

  /**
   * The filetypes which function with this audio player.
   *
   * @var array
   */
  public $fileTypes = [];

  /**
   * The description of this audio player.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = "";

  /**
   * The main library name for this audio player.
   *
   * @var string
   */
  public $libraryName = "";

  /**
   * The website of this plugin's audio player library.
   *
   * @var string
   */
  public $website = "";

}
