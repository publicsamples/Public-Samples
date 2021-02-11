<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;

/**
 * Implements the SoundManager Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "soundmanager_audio_player",
 *   title = @Translation("SoundManager audio player"),
 *   description = @Translation("Simple, reliable cross-platform audio."),
 *   fileTypes = {
 *     "mp3", "mp4", "ogg", "oga", "wav", "flac",
 *   },
 *   libraryName = "soundmanager",
 *   website = "http://www.schillmania.com/projects/soundmanager2",
 * )
 */
class SoundManagerAudioPlayer extends AudioFieldPluginBase {

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

    // Create arrays to pass to the twig template.
    $template_settings = $settings;
    $template_settings['count'] = count($items);

    // Start building settings to pass to the javascript SoundManager builder.
    $player_settings = [
      // SoundManager expects this as a 0 - 100 range.
      'volume' => ($settings['audio_player_initial_volume'] * 10),
      'swfpath' => $this->getPluginLibraryPath() . '/swf/',
    ];

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'soundmanager',
        '#plugin_theme' => $settings['audio_player_soundmanager_theme'],
        '#settings' => $template_settings,
        '#files' => $this->getItemRenderList($items),
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the SoundManager library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
          // Attach the skin library.
          'audiofield/audiofield.' . $this->getPluginLibraryName() . '.' . $settings['audio_player_soundmanager_theme'],
        ],
        'drupalSettings' => [
          'audiofieldsoundmanager' => $player_settings,
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
