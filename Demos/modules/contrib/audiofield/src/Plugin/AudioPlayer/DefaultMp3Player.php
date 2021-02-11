<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the Default HTML5 Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "default_mp3_player",
 *   title = @Translation("default HTML5 audio player"),
 *   description = @Translation("Default html5 player - built into HTML specification."),
 *   fileTypes = {
 *     "mp3", "mp4", "m4a", "3gp", "aac", "wav", "ogg", "oga", "flac", "webm",
 *   },
 *   libraryName = "default",
 * )
 */
class DefaultMp3Player extends AudioFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FieldItemListInterface $items, $langcode, array $settings) {
    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'default',
        '#settings' => $settings,
        '#files' => $this->getItemRenderList($items),
      ],
      'downloads' => $this->createDownloadList($items, $settings),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkInstalled($log_error = FALSE) {
    // This is built in to HTML5, so it is always "installed".
    return TRUE;
  }

}
