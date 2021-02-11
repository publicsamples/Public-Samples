<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;

/**
 * Implements the jPlayer Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "jplayer_audio_player",
 *   title = @Translation("jPlayer audio player"),
 *   description = @Translation("Free and open source media library."),
 *   fileTypes = {
 *     "mp3", "mp4", "wav", "ogg", "oga", "webm",
 *   },
 *   libraryName = "jplayer",
 *   website = "http://jplayer.org/",
 * )
 */
class JPlayerAudioPlayer extends AudioFieldPluginBase {

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
    $template_theme = str_replace('audiofield.jplayer.theme_', '', $settings['audio_player_jplayer_theme']);

    // JPlayer circle has to render differently - no playlist support, etc.
    if ($settings['audio_player_jplayer_theme'] == 'audiofield.jplayer.theme_jplayer_circle') {
      // @todo circle player broken for some reason.
      // Only require the default library.
      $library = 'audiofield/audiofield.' . $this->getPluginLibraryName();

      // Start building settings to pass to the javascript jplayer builder.
      $player_settings = [
        'playertype' => 'circle',
        // JPlayer expects this as a 0 - 1 value.
        'volume' => ($settings['audio_player_initial_volume'] / 10),
        'files' => [],
      ];

      // Format files for output.
      $template_files = $this->getItemRenderList($items);
      foreach ($template_files as $renderInfo) {
        // Add entry to player settings for this file.
        $player_settings['files'][] = [
          'file' => $renderInfo->url->toString(),
          'description' => $renderInfo->description,
          'filetype' => $renderInfo->filetype,
          'fid' => $renderInfo->id,
          'autoplay' => $settings['audio_player_autoplay'],
          'lazyload' => $settings['audio_player_lazyload'],
        ];
      }
    }
    // This is a normal jPlayer skin, so we render normally.
    else {
      // Need to derermine quantity of valid items.
      $template_settings['item_count'] = 0;
      foreach ($items as $item) {
        if ($this->validateEntityAgainstPlayer($item)) {
          $template_settings['item_count']++;
        }
      }

      // If there is only a single file, we render as a standard player.
      if ($template_settings['item_count'] == 1) {
        // Only require the default library.
        $library = 'audiofield/audiofield.' . $this->getPluginLibraryName();

        // Set the template theme name.
        $template_theme = 'default_single';

        // Format files for output.
        $template_files = $this->getItemRenderList($items, 1);
        foreach ($template_files as $renderInfo) {
          // Start building settings to pass to the javascript jplayer builder.
          $player_settings = [
            'playertype' => 'default',
            'file' => $renderInfo->url->toString(),
            'description' => $renderInfo->description,
            'unique_id' => $renderInfo->id,
            'filetype' => $renderInfo->filetype,
            // JPlayer expects this as a 0 - 1 value.
            'volume' => ($settings['audio_player_initial_volume'] / 10),
            'autoplay' => $settings['audio_player_autoplay'],
            'lazyload' => $settings['audio_player_lazyload'],
          ];

          // Store the unique id for the template.
          $template_settings['id'] = $renderInfo->id;
        }
      }
      // If we have multiple files, we need to render this as a playlist.
      else {
        // Requires the playlist library.
        $library = 'audiofield/audiofield.' . $this->getPluginLibraryName() . '.playlist';

        // Set the template theme name.
        $template_theme = 'default_multiple';

        // Start building settings to pass to the javascript jplayer builder.
        $player_settings = [
          'playertype' => 'playlist',
          // JPlayer expects this as a 0 - 1 value.
          'volume' => ($settings['audio_player_initial_volume'] / 10),
          'files' => [],
          'filetypes' => [],
          'autoplay' => $settings['audio_player_autoplay'],
          'lazyload' => $settings['audio_player_lazyload'],
        ];

        // Format files for output.
        $template_files = $this->getItemRenderList($items);
        foreach ($template_files as $renderInfo) {
          // Add entry to player settings for this file.
          $player_settings['files'][] = [
            'file' => $renderInfo->url->toString(),
            'description' => $renderInfo->description,
            'filetype' => $renderInfo->filetype,
          ];
          $player_settings['filetypes'][] = $renderInfo->filetype;

          // Used to generate unique container.
          $player_settings['unique_id'] = $template_settings['id'] = $renderInfo->id;
        }

        // Use only unique values in the filetypes.
        $player_settings['filetypes'] = array_unique($player_settings['filetypes']);
      }
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'jplayer',
        '#plugin_theme' => $template_theme,
        '#settings' => $template_settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the jPlayer library.
          $library,
          // Attach the jPlayer theme.
          'audiofield/' . $settings['audio_player_jplayer_theme'],
        ],
        'drupalSettings' => [
          'audiofieldjplayer' => [
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
