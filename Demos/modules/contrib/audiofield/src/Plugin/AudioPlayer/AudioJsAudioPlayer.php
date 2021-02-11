<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the audio.js Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "audiojs_audio_player",
 *   title = @Translation("audio.js audio player"),
 *   description = @Translation("Drop-in javascript library using native <audio> tag."),
 *   fileTypes = {
 *     "mp3",
 *   },
 *   libraryName = "audiojs",
 *   website = "http://kolber.github.io/audiojs/",
 * )
 */
class AudioJsAudioPlayer extends AudioFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FieldItemListInterface $items, $langcode, array $settings) {
    // Check to make sure we're installed.
    if (!$this->checkInstalled()) {
      // Show the error.
      $this->showInstallError();

      // Simply return the default rendering so the files are still displayed.
      $default_player = new DefaultMp3Player();
      return $default_player->renderPlayer($items, $langcode, $settings);
    }

    // Start building settings to pass to the javascript audio.js builder.
    $player_settings = [
      // Audio.js expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'element' => '',
    ];

    // Create arrays to pass to the twig theme..
    $template_settings = $settings;
    // Format files for output.
    $template_files = $this->getItemRenderList($items);
    foreach ($template_files as $renderInfo) {
      // Used to generate unique container.
      $player_settings['element'] = $template_settings['id'] = 'audiofield_audiojs_' . $renderInfo->id;
    }

    // If we have at least one audio file, we render.
    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'audiojs',
        '#settings' => $template_settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the audio.js library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldaudiojs' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}
