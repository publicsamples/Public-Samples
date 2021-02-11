<?php

namespace Drupal\audiofield\Plugin\Field\FieldFormatter;

use Drupal\audiofield\AudioFieldPlayerManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of audio player file formatter.
 *
 * @FieldFormatter(
 *   id = "audiofield_audioplayer",
 *   label = @Translation("Audiofield Audio Player"),
 *   field_types = {
 *     "file", "link"
 *   }
 * )
 */
class AudioFieldFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $audioPlayerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AudioFieldPlayerManager $audio_player_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->audioPlayerManager = $audio_player_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.audiofield')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get the fieldname in a format that works for all forms.
    $fieldname = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();

    // Loop over each plugin type and create an entry for it.
    $plugin_definitions = $this->audioPlayerManager->getDefinitions();
    $plugins = [
      'available' => [],
      'unavailable' => [],
    ];
    foreach ($plugin_definitions as $plugin_id => $plugin) {
      // Create an instance of the player.
      $player = $this->audioPlayerManager->createInstance($plugin_id);
      if ($player->checkInstalled()) {
        $plugins['available'][$plugin_id] = $plugin['title'];
      }
      else {
        $plugins['unavailable'][$plugin_id] = $plugin['title'];
      }
    }
    ksort($plugins['available']);

    // Build settings form for display on the structure page.
    $elements = parent::settingsForm($form, $form_state);
    $default_player = $this->getSetting('audio_player');
    if (isset($plugins['unavailable'][$default_player])) {
      $default_player = 'default_mp3_player';
    }
    // Let user select the audio player.
    $elements['audio_player'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Player'),
      '#default_value' => $default_player,
      '#options' => $plugins['available'],
    ];
    if (count($plugins['unavailable']) > 0) {
      ksort($plugins['unavailable']);
      $elements['unavailable'] = [
        '#type' => 'details',
        '#title' => $this->t('Disabled Players:'),
        '#open' => TRUE,
        '#disabled' => TRUE,
        [
          '#type' => '#container',
          '#attributes' => [],
          '#children' => implode('<br/>', $plugins['unavailable']),
        ],
      ];
    }
    // Settings for jPlayer.
    // Only show when jPlayer is the selected audio player.
    $jplayer_options = [
      'none' => 'None (for styling manually with CSS)',
      // Add the circle skin in (special non-standard custom skin for jPlayer).
      'audiofield.jplayer.theme_jplayer_circle' => 'jPlayer circle player',
    ];
    // Build the list of jPlayer available skins.
    foreach (_audiofield_list_skins('jplayer_audio_player') as $skin) {
      $jplayer_options[$skin['library_name']] = $skin['name'];
    }
    $elements['audio_player_jplayer_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select jPlayer Skin'),
      '#description' => $this->t('jPlayer comes bundled with multiple skins by default. You can install additional skins by placing them in /libraries/jplayer/dist/skin/'),
      '#default_value' => $this->getSetting('audio_player_jplayer_theme'),
      '#options' => $jplayer_options,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player'],
        ],
      ],
    ];
    // Settings for WaveSurfer.
    // Only show when WaveSurfer is the selected audio player.
    $elements['audio_player_wavesurfer_combine_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine audio files into a single audio player'),
      '#description' => $this->t('By default Wavesurfer displays files individually. This option combines the files into a playlist so only one file shows at a time.'),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_combine_files'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player'],
        ],
      ],
    ];
    // Settings for WaveSurfer.
    $elements['audio_player_wavesurfer_audiorate'] = [
      '#type' => 'number',
      '#title' => $this->t('Audio Rate'),
      '#description' => $this->t("Speed at which to play audio. Lower number is slower."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_audiorate'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_autocenter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Center'),
      '#description' => $this->t("If a scrollbar is present, center the waveform around the progress."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_autocenter'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_bargap'] = [
      '#type' => 'number',
      '#title' => $this->t('Bar Gap'),
      '#description' => $this->t("The optional spacing between bars of the wave."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_bargap'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_barheight'] = [
      '#type' => 'number',
      '#title' => $this->t('Bar Height'),
      '#description' => $this->t("Height of the waveform bars. Higher number than 1 will increase the waveform bar heights."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_barheight'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_barwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Bar Width'),
      '#description' => $this->t("If specified, the waveform will be drawn like this: ▁ ▂ ▇ ▃ ▅ ▂"),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_barwidth'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_cursorcolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Cursor Color'),
      '#description' => $this->t("The fill color of the cursor indicating the playhead position."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_cursorcolor'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_cursorwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Cursor Width'),
      '#description' => $this->t("Width of the cursor indicating the playhead position. Measured in pixels."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_cursorwidth'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_forcedecode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force Decode'),
      '#description' => $this->t("Force decoding of audio using web audio when zooming to get a more detailed waveform."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_forcedecode'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_normalize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Normalize'),
      '#description' => $this->t("If checked, normalize by the maximum peak instead of 1.0."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_normalize'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_progresscolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Progress Color'),
      '#description' => $this->t("The fill color of the part of the waveform behind the cursor."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_progresscolor'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#description' => $this->t("If checked, resize the waveform, when the window is resized. This is debounced with a 100ms timeout by default."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_responsive'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];
    $elements['audio_player_wavesurfer_wavecolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Wave Color'),
      '#description' => $this->t("The fill color of the waveform after the cursor."),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_wavecolor'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
        ],
      ],
    ];

    // Settings for WordPress.
    // Only show when WordPress is the selected audio player.
    $elements['audio_player_wordpress_combine_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine audio files into a single audio player'),
      '#description' => $this->t('This can be more difficult to see for the WordPress plugin. Multiple files are represented only by small "next" and "previous" arrows. Unchecking this box causes each file to be rendered as its own player.'),
      '#default_value' => $this->getSetting('audio_player_wordpress_combine_files'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player'],
        ],
      ],
    ];
    $elements['audio_player_wordpress_animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animate player?'),
      '#description' => $this->t('If unchecked, the player will always remain open with the title visible.'),
      '#default_value' => $this->getSetting('audio_player_wordpress_animation'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player'],
        ],
      ],
    ];
    // Settings for SoundManager.
    // Only show when SoundManager is the selected audio player.
    $elements['audio_player_soundmanager_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select SoundManager Skin'),
      '#default_value' => $this->getSetting('audio_player_soundmanager_theme'),
      '#options' => [
        'default' => 'Default theme',
        'player360' => '360 degree player',
        'barui' => 'Bar UI',
        'inlineplayer' => 'Inline Player',
      ],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'soundmanager_audio_player'],
        ],
      ],
    ];
    // Settings for multiple players.
    $elements['audio_player_initial_volume'] = [
      '#type' => 'range',
      '#title' => $this->t('Set Initial Volume'),
      '#default_value' => $this->getSetting('audio_player_initial_volume'),
      '#min' => 0,
      '#max' => 10,
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'mediaelement_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'projekktor_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'soundmanager_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player']],
        ],
      ],
    ];
    // Settings for autoplay.
    $elements['audio_player_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay on page load'),
      '#default_value' => $this->getSetting('audio_player_autoplay'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'audiojs_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'default_mp3_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'mediaelement_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'projekktor_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player']],
        ],
      ],
    ];
    // Settings for autoplay.
    $elements['audio_player_lazyload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy Load audio'),
      '#description' => $this->t("This setting causes audio not to be loaded until it is played."),
      '#default_value' => $this->getSetting('audio_player_lazyload'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'audiojs_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'default_mp3_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'mediaelement_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'projekktor_audio_player']],
        ],
      ],
    ];
    // Settings for download button.
    $elements['download_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display download button on player'),
      '#default_value' => $this->getSetting('download_button'),
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'default_mp3_player']],
        ],
      ],
    ];
    // Setting for optional download link.
    $elements['download_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display download link below player'),
      '#default_value' => $this->getSetting('download_link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $plugin_definitions = $this->audioPlayerManager->getDefinitions();

    $settings = $this->getSettings();

    // Show which player we are currently using for the field.
    $summary = [
      $this->t('Selected player: <strong>@player</strong>', [
        '@player' => $plugin_definitions[$settings['audio_player']]['title'],
      ]),
    ];
    // If this is jPlayer, add those settings.
    if ($settings['audio_player'] == 'jplayer_audio_player') {
      // Display theme.
      $theme = 'None (for styling manually with CSS)';
      // If this is the custom jplayer circle theme.
      if ($settings['audio_player_jplayer_theme'] == 'audiofield.jplayer.theme_jplayer_circle') {
        $theme = 'jPlayer circle player';
      }
      // Search for the theme we're using.
      else {
        foreach (_audiofield_list_skins('jplayer_audio_player') as $skin) {
          if ($skin['library_name'] == $settings['audio_player_jplayer_theme']) {
            $theme = $skin['name'];
          }
        }
      }
      $summary[] = $this->t('Skin: <strong>@theme</strong>', [
        '@theme' => $theme,
      ]);
    }
    // If this is wavesurfer, add those settings.
    elseif ($settings['audio_player'] == 'wavesurfer_audio_player') {
      $summary[] = $this->t('Combine files into single player? <strong>@combine</strong>', [
        '@combine' => ($settings['audio_player_wavesurfer_combine_files'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Audio Rate: <strong>@value</strong>', [
        '@value' => $settings['audio_player_wavesurfer_audiorate'],
      ]);
      $summary[] = $this->t('Auto Center? <strong>@value</strong>', [
        '@value' => ($settings['audio_player_wavesurfer_autocenter'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Bar Gap: <strong>@value</strong>', [
        '@value' => $settings['audio_player_wavesurfer_bargap'],
      ]);
      $summary[] = $this->t('Bar Height: <strong>@value</strong>', [
        '@value' => $settings['audio_player_wavesurfer_barheight'],
      ]);
      $summary[] = $this->t('Bar Width: <strong>@value</strong>', [
        '@value' => $settings['audio_player_wavesurfer_barwidth'],
      ]);
      $summary[] = $this->t('Cursor Color: <span style="border:1px solid black;height:10px;width:10px;display:inline-block;background:@value;"></span>', [
        '@value' => $settings['audio_player_wavesurfer_cursorcolor'],
      ]);
      $summary[] = $this->t('Cursor Width: <strong>@value</strong>', [
        '@value' => $settings['audio_player_wavesurfer_cursorwidth'],
      ]);
      $summary[] = $this->t('Force Decode? <strong>@value</strong>', [
        '@value' => ($settings['audio_player_wavesurfer_forcedecode'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Normalize? <strong>@value</strong>', [
        '@value' => ($settings['audio_player_wavesurfer_normalize'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Progress Color: <span style="border:1px solid black;height:10px;width:10px;display:inline-block;background:@value;"></span>', [
        '@value' => $settings['audio_player_wavesurfer_progresscolor'],
      ]);
      $summary[] = $this->t('Responsive? <strong>@value</strong>', [
        '@value' => ($settings['audio_player_wavesurfer_responsive'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Wave Color: <span style="border:1px solid black;height:10px;width:10px;display:inline-block;background:@value;"></span>', [
        '@value' => $settings['audio_player_wavesurfer_wavecolor'],
      ]);
    }
    // If this is wordpress, add those settings.
    elseif ($settings['audio_player'] == 'wordpress_audio_player') {
      $summary[] = $this->t('Combine files into single player? <strong>@combine</strong>', [
        '@combine' => ($settings['audio_player_wordpress_combine_files'] ? 'Yes' : 'No'),
      ]);
      $summary[] = $this->t('Animate player? <strong>@animate</strong>', [
        '@animate' => ($settings['audio_player_wordpress_animation'] ? 'Yes' : 'No'),
      ]);
    }
    // If this is soundmanager, add those settings.
    elseif ($settings['audio_player'] == 'soundmanager_audio_player') {
      $skins = [
        'default' => 'Default theme',
        'player360' => '360 degree player',
        'barui' => 'Bar UI',
        'inlineplayer' => 'Inline Player',
      ];
      $summary[] = $this->t('Skin: <strong>@skin</strong>', [
        '@skin' => $skins[$settings['audio_player_soundmanager_theme']],
      ]);
    }

    // Show combined settings for multiple players.
    if (in_array($settings['audio_player'], [
      'jplayer_audio_player',
      'mediaelement_audio_player',
      'projekktor_audio_player',
      'soundmanager_audio_player',
      'wavesurfer_audio_player',
      'wordpress_audio_player',
    ])) {
      // Display volume.
      $summary[] = $this->t('Initial volume: <strong>@volume out of 10</strong>', [
        '@volume' => $settings['audio_player_initial_volume'],
      ]);
    }

    // Display autoplay.
    if (in_array($settings['audio_player'], [
      'audiojs_audio_player',
      'default_mp3_player',
      'jplayer_audio_player',
      'mediaelement_audio_player',
      'projekktor_audio_player',
      'wavesurfer_audio_player',
      'wordpress_audio_player',
    ])) {
      $summary[] = $this->t('Autoplay: <strong>@autoplay</strong>', [
        '@autoplay' => ($settings['audio_player_autoplay'] ? 'Yes' : 'No'),
      ]);
    }

    // Display lazy load.
    if (in_array($settings['audio_player'], [
      'audiojs_audio_player',
      'default_mp3_player',
      'jplayer_audio_player',
      'mediaelement_audio_player',
      'projekktor_audio_player',
    ])) {
      $summary[] = $this->t('Lazy Load: <strong>@lazyload</strong>', [
        '@lazyload' => ($settings['audio_player_lazyload'] ? 'Yes' : 'No'),
      ]);
    }

    // Check to make sure the library is installed.
    $player = $this->audioPlayerManager->createInstance($settings['audio_player']);
    if (!$player->checkInstalled()) {
      $summary[] = $this->t('<strong style="color:red;">Error: this player library is currently not installed. Please select another player or reinstall the library.</strong>');
    }

    // Show whether or not we are displaying download buttons.
    $summary[] = $this->t('Display download button: <strong>@display_link</strong>', [
      '@display_link' => ($settings['download_button'] ? 'Yes' : 'No'),
    ]);

    // Show whether or not we are displaying direct downloads.
    $summary[] = $this->t('Display download link: <strong>@display_link</strong>', [
      '@display_link' => ($settings['download_link'] ? 'Yes' : 'No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'audio_player' => 'default_mp3_player',
      'audio_player_jplayer_theme' => 'none',
      'audio_player_wavesurfer_combine_files' => FALSE,
      'audio_player_wavesurfer_audiorate' => 1,
      'audio_player_wavesurfer_autocenter' => TRUE,
      'audio_player_wavesurfer_bargap' => 0,
      'audio_player_wavesurfer_barheight' => 1,
      'audio_player_wavesurfer_barwidth' => NULL,
      'audio_player_wavesurfer_cursorcolor' => '#333',
      'audio_player_wavesurfer_cursorwidth' => 1,
      'audio_player_wavesurfer_forcedecode' => FALSE,
      'audio_player_wavesurfer_normalize' => FALSE,
      'audio_player_wavesurfer_progresscolor' => '#555',
      'audio_player_wavesurfer_responsive' => FALSE,
      'audio_player_wavesurfer_wavecolor' => '#999',
      'audio_player_wordpress_combine_files' => FALSE,
      'audio_player_wordpress_animation' => TRUE,
      'audio_player_soundmanager_theme' => 'default',
      'audio_player_initial_volume' => 8,
      'audio_player_autoplay' => FALSE,
      'audio_player_lazyload' => FALSE,
      'download_button' => FALSE,
      'download_link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return $elements;
    }

    $plugin_id = $this->getSetting('audio_player');
    $player = $this->audioPlayerManager->createInstance($plugin_id);

    $elements[] = $player->renderPlayer($items, $langcode, $this->getSettings());
    return $elements;
  }

}
