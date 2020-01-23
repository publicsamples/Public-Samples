<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;

/**
 * Implements the Wavesurfer Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "wavesurfer_audio_player",
 *   title = @Translation("Wavesurfer audio player"),
 *   description = @Translation("A customizable audio waveform visualization, built on top of Web Audio API and HTML5 Canvas."),
 *   fileTypes = {
 *     "mp3", "ogg", "oga", "wav",
 *   },
 *   libraryName = "wavesurfer",
 *   website = "https://github.com/katspaugh/wavesurfer.js",
 * )
 */
class WavesurferAudioPlayer extends AudioFieldPluginBase {

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
    $template_files = [];

    // Start building settings to pass to the javascript wavesurfer builder.
    $player_settings = [
      // Wavesurfer expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'playertype' => ($settings['audio_player_wavesurfer_combine_files'] ? 'playlist' : 'default'),
      'files' => [],
      'audioRate' => $settings['audio_player_wavesurfer_audiorate'],
      'autoCenter' => $settings['audio_player_wavesurfer_autocenter'],
      'barGap' => $settings['audio_player_wavesurfer_bargap'],
      'barHeight' => $settings['audio_player_wavesurfer_barheight'],
      'barWidth' => $settings['audio_player_wavesurfer_barwidth'],
      'cursorColor' => $settings['audio_player_wavesurfer_cursorcolor'],
      'cursorWidth' => $settings['audio_player_wavesurfer_cursorwidth'],
      'forceDecode' => $settings['audio_player_wavesurfer_forcedecode'],
      'normalize' => $settings['audio_player_wavesurfer_normalize'],
      'progressColor' => $settings['audio_player_wavesurfer_progresscolor'],
      'responsive' => $settings['audio_player_wavesurfer_responsive'],
      'waveColor' => $settings['audio_player_wavesurfer_wavecolor'],
      'autoplay' => $settings['audio_player_autoplay'],
    ];

    // Format files for output.
    $template_files = $this->getItemRenderList($items);
    foreach ($template_files as $renderInfo) {
      // Add this file to the render settings.
      $player_settings['files'][] = [
        'id' => 'wavesurfer_' . $renderInfo->id,
        'path' => $renderInfo->url->toString(),
      ];
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'wavesurfer',
        '#plugin_theme' => $player_settings['playertype'],
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the wavesurfer library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldwavesurfer' => [
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
