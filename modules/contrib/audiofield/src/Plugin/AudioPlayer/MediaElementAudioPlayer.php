<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;

/**
 * Implements the MediaElement Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "mediaelement_audio_player",
 *   title = @Translation("MediaElement audio player"),
 *   description = @Translation("A dependable HTML media framework."),
 *   fileTypes = {
 *     "mp3", "oga", "ogg", "wav",
 *   },
 *   libraryName = "mediaelement",
 *   website = "http://mediaelementjs.com/",
 * )
 */
class MediaElementAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript MediaElement builder.
    $player_settings = [
      // MediaElement expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'elements' => [],
    ];

    // Format files for output.
    $template_files = $this->getItemRenderList($items);
    foreach ($template_files as $renderInfo) {
      // Pass the element name for the player so we know what to render.
      $player_settings['elements'][] = '#mediaelement_player_' . $renderInfo->id;
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'mediaelement',
        '#settings' => $settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the MediaElement library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldmediaelement' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLibraryVersion() {
    // Parse the JSON file for version info.
    $library_path = $this->fileSystem->realpath(DRUPAL_ROOT . $this->getPluginLibraryPath() . '/package.json');
    $library_data = Json::decode(file_get_contents($library_path));
    return $library_data['version'];
  }

}
