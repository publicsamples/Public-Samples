<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the WordPress Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "wordpress_audio_player",
 *   title = @Translation("WordPress audio player"),
 *   description = @Translation("Standalone audio player originally built for WordPress"),
 *   fileTypes = {
 *     "mp3",
 *   },
 *   libraryName = "wordpress",
 *   website = "http://wpaudioplayer.com",
 * )
 */
class WordPressAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript WordPress builder.
    $player_settings = [
      // WordPress expects this as a 0 - 100 range.
      'volume' => ($settings['audio_player_initial_volume'] * 10),
      'animate' => ($settings['audio_player_wordpress_animation'] ? 'yes' : 'no'),
      'files' => [],
      'autoplay' => $settings['audio_player_autoplay'],
    ];

    // Format files for output.
    $template_files = $this->getItemRenderList($items, ($settings['audio_player_wordpress_combine_files'] ? 1 : 0));
    foreach ($template_files as $renderInfo) {
      // Pass settings for the file.
      $player_settings['files'][] = [
        'file' => $renderInfo->url->toString(),
        'title' => $renderInfo->description,
        'unique_id' => $renderInfo->id,
      ];
    }

    // If we are combining into a single player, make some modifications.
    if ($settings['audio_player_wordpress_combine_files']) {
      // Wordpress expects comma-deliminated lists
      // when using multiple files in a single player.
      $wp_files = [];
      $wp_titles = [];
      foreach ($player_settings['files'] as $wp_file) {
        $wp_files[] = $wp_file['file'];
        $wp_titles[] = $wp_file['title'];
      }

      // Redeclare settings with only a single (combined) file.
      $player_settings['files'] = [
        [
          'file' => implode(',', $wp_files),
          'title' => implode(',', $wp_titles),
          'unique_id' => $player_settings['files'][0]['unique_id'],
        ],
      ];
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'wordpress',
        '#settings' => $settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the WordPress library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldwordpress' => [
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
    // Parse the audio-player.js file for version info.
    $library_path = $this->fileSystem->realpath(DRUPAL_ROOT . $this->getPluginLibraryPath() . '/audio-player.js');
    $library_data = file_get_contents($library_path);
    $matches = [];
    preg_match('%SWFObject v([0-9\.]+).*%', $library_data, $matches);
    return $matches[1];
  }

}
