<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;

/**
 * Implements the Projekktor Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "projekktor_audio_player",
 *   title = @Translation("Projekktor audio player"),
 *   description = @Translation("Free Web Video Player (converted for audio)"),
 *   fileTypes = {
 *     "mp3", "mp4", "ogg", "oga", "wav",
 *   },
 *   libraryName = "projekktor",
 *   website = "http://www.projekktor.com/",
 * )
 */
class ProjekktorAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript projekktor builder.
    $player_settings = [
      // Projekktor expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'swfpath' => $this->getPluginLibraryPath() . '/swf/Jarisplayer/jarisplayer.swf',
      'files' => [],
      'autoplay' => $settings['audio_player_autoplay'],
    ];

    // Format files for output.
    $template_files = $this->getItemRenderList($items);
    foreach ($template_files as $renderInfo) {
      // Add this file to the render settings.
      $player_settings['files'][] = $renderInfo->id;
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'projekktor',
        '#settings' => $settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the projekktor library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldprojekktor' => [
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
