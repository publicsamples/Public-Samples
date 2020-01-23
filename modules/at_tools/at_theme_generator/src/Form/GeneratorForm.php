<?php

namespace Drupal\at_theme_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\at_theme_generator\Theme\ThemeInfo;
use Drupal\at_theme_generator\Theme\ThemeGenerator;
use Drupal\at_theme_generator\Theme\ThemeGeneratorTypes;

/**
 * Generator form.
 */
class GeneratorForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'at_generator_form';
  }

  /**
   */
  private $themeInfoData;

  /**
   */
  private $themeSettingsInfo;

  /**
   */
  private $typeSubtheme;

  /**
   */
  private $typeSkintheme;

  /**
   */
  private $listInfo;

  /**
   */
  public function __construct() {
    $this->themeInfoData = \Drupal::service('theme_handler')->rebuildThemeData();
    $this->listInfo = \Drupal::service('theme_handler')->listInfo();
    $this->themeSettingsInfo = new ThemeInfo('at_core');
    $this->typeSubtheme = $this->themeSettingsInfo->themeOptions('adaptive_subtheme');
    $this->typeSkintheme = $this->themeSettingsInfo->themeOptions('adaptive_skin');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_themes = $this->typeSubtheme;
    $skin_themes = $this->typeSkintheme;
    $cloneable_themes = array_merge($base_themes, $skin_themes);

    $at_core = array_key_exists('at_core', $this->themeInfoData) ? TRUE : FALSE;

    // Attach library.
    $form['#attached']['library'][] = 'at_theme_generator/theme_generator';

    // Set directory options.
    $themes_dir = 'themes';
    if (is_writable(dirname($themes_dir))) {
      $dir_options[$themes_dir] = t('Themes directory');
    }
    $dir_options['public://'] = t('Public files');
    $dir_options['custom'] = t('Custom');

    $form['generate'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['generate']['docs'] = [
      '#type' => 'container',
      '#markup' => t('<a class="at-docs" href="//docs.adaptivethemes.com/theme-generator/" target="_blank" title="External link: docs.adaptivethemes.com/theme-generator">View generator documentation <svg class="docs-ext-link-icon" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1408 928v320q0 119-84.5 203.5t-203.5 84.5h-832q-119 0-203.5-84.5t-84.5-203.5v-832q0-119 84.5-203.5t203.5-84.5h704q14 0 23 9t9 23v64q0 14-9 23t-23 9h-704q-66 0-113 47t-47 113v832q0 66 47 113t113 47h832q66 0 113-47t47-113v-320q0-14 9-23t23-9h64q14 0 23 9t9 23zm384-864v512q0 26-19 45t-45 19-45-19l-176-176-652 652q-10 10-23 10t-23-10l-114-114q-10-10-10-23t10-23l652-652-176-176q-19-19-19-45t19-45 45-19h512q26 0 45 19t19 45z"/></svg></a>'),
      '#weight' => -1000,
    ];

    if ($at_core == FALSE) {
      $form['generate']['#disabled'] = TRUE;
      drupal_set_message(t('<a href="//www.drupal.org/project/adaptivetheme" target="_blank">Adaptivetheme</a> is a required base theme for all generated themes, <a href="//www.drupal.org/project/adaptivetheme" target="_blank">download the latest version for Drupal 8</a> and place in the themes directory.'), 'error');
    }

    // Friendly name.
    $form['generate']['generate_friendly_name'] = [
      '#type' => 'textfield',
      '#title' => t('Theme name'),
      '#maxlength' => DRUPAL_EXTENSION_NAME_MAX_LENGTH, // the maximum allowable length of a module or theme name.
      '#size' => 45,
      '#required' => TRUE,
      '#default_value' => '',
      '#description' => t('Enter a unique theme name. Letters, spaces and underscores only. Max length @length chars.', ['@length' => DRUPAL_EXTENSION_NAME_MAX_LENGTH]),
    ];

    // Machine name.
    $form['generate']['generate_machine_name'] = [
      '#type' => 'machine_name',
      '#maxlength' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
      '#size' => 45,
      '#title' => t('Machine name'),
      '#required' => TRUE,
      '#field_prefix' => '',
      '#default_value' => '',
      '#machine_name' => [
        'exists' => [$this->themeSettingsInfo, 'themeNameExists'], // class method for call_user_func()
        'source' => ['generate', 'generate_friendly_name'],
        'label' => t('Machine name'),
        'replace_pattern' => '[^a-z_]+',
        'replace' => '_',
      ],
    ];

    $generate_type_options = [
      'starterkit' => t('Standard kit'),
    ];

    if (!empty($base_themes)) {
      $generate_type_options = [
        'starterkit' => t('Starter kit'),
        'clone' => t('Clone'),
        'skin' => t('Skin'),
      ];
    }

    $form['generate']['generate_type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#required' => TRUE,
      '#options' => $generate_type_options,
      //'#default_value' => 'starterkit',
      '#default_value' => '',
    ];

    $form['generate']['generate_type_description_standard_kit'] = [
      '#type' => 'container',
      '#markup' => t('The Starter kit includes an advanced layout and is designed to fully support the UIKit and Color module (both optional).'),
      '#attributes' => ['class' => ['generate-type__description']],
      '#states' => [
        'visible' => ['select[name="generate[generate_type]"]' => ['value' => 'starterkit']],
      ],
    ];

    $form['generate']['generate_clone_source'] = [
      '#type' => 'select',
      '#title' => t('Clone source'),
      '#options' => $cloneable_themes,
      '#default_value' => '',
      '#description' => t('Clones are copies of existing themes.'),
      '#states' => [
        'visible' => ['select[name="generate[generate_type]"]' => ['value' => 'clone']],
      ],
    ];

    $form['generate']['generate_skin_base'] = [
      '#type' => 'select',
      '#title' => t('Skin base theme'),
      '#options' => $base_themes,
      '#default_value' => '',
      '#description' => t('Skins are sub-sub themes that inherit extension, layout and color settings from their base theme. Select an existing theme as the skins base theme.'),
      '#states' => [
        'visible' => ['select[name="generate[generate_type]"]' => ['value' => 'skin']],
      ],
    ];

    // Options
    $form['generate']['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Options'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
            ['value' => 'clone'],
            ['value' => 'skin'],
          ],
        ],
      ],
    ];

    // UI Kit
    $form['generate']['options']['generate_scss'] = [
      '#type' => 'checkbox',
      '#title' => t('SCSS (SASS/Grunt)'),
      '#default_value' => 1,
      '#description' => t('Includes all SCSS partials for the UIKit (user facing styles), Layouts and Layout Plugin partials, Grunt tasks, and Bower/NPM package management. If you have no use for SCSS un-check this option.'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];

    // Color module
    $color_module = \Drupal::moduleHandler()->moduleExists('color');
    $form['generate']['options']['generate_color'] = [
      '#type' => 'checkbox',
      '#title' => t('Color Module'),
      '#default_value' => 1,
      '#description' => t('Provides basic Color module support so you can modify the color of your theme in theme settings.'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];
    if ($color_module == FALSE) {
      $form['generate']['options']['generate_color']['#default_value'] = 0;
      $form['generate']['options']['generate_color']['#description'] = t('Provides basic Color module support so you can modify the color of your theme in theme settings. NOTE: The Color module is not currently installed. You can still select this option, however no color settings will appear until you install the Color module.');
    }

    // Block config
    $form['generate']['options']['generate_block_config'] = [
      '#type' => 'checkbox',
      '#title' => t('Block Config'),
      '#default_value' => 1,
      '#description' => t('Include configuration for blocks. Un-check this setting if you want your theme to inherit the default themes block configuration.'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];

    // Templates
    $form['generate']['options']['generate_templates'] = [
      '#type' => 'checkbox',
      '#title' => t('Templates'),
      '#default_value' => 0,
      '#description' => t('Include copies of Drupals front end twig templates (page.html.twig is always included regardless of this setting).'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];

    // theme-settings.php file
    $form['generate']['options']['generate_themesettingsfile'] = [
      '#type' => 'checkbox',
      '#title' => t('theme-settings.php'),
      '#default_value' => 0,
      '#description' => t('Include a theme-settings.php file. Includes skeleton code for the form alter, custom validation and submit functions.'),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];

    // Layout
    $form['generate']['options']['generate_layout'] = [
      '#type' => 'radios',
      '#title' => t('Layout type'),
      '#options' => [
        'flex' => t('Flexbox'),
        'float' => t('Floats'),
      ],
      '#default_value' => 'flex',
      '#description' => t('All modern browsers including IE11 are compatible with Adaptivetheme\'s Flexbox layouts. If you need to support legacy browsers such as IE8/9 select Floats. See <a href="@caniuse" target="_blank">caniuse.com</a> for more info.', ['@caniuse' => 'http://caniuse.com/#search=flexbox']),
      '#states' => [
        'visible' => [
          'select[name="generate[generate_type]"]' => [
            ['value' => 'starterkit'],
          ],
        ],
      ],
    ];

    // Float warning
    $form['generate']['options']['generate_layout']['float_warning'] = [
      '#type' => 'container',
      '#markup' => '<div class="messages messages--warning">'. t('Drag and drop row ordering (weights) is not supported in the Float layout.') . '</div>',
      '#states' => [
        'visible' => [
          'input[name="generate[options][generate_layout]"]' => [
            ['value' => 'float'],
          ],
        ],
      ],
    ];

    // Description.
    $form['generate']['options']['generate_description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => '',
      '#description' => t('Descriptions are used on the Appearance list page.'),
    ];

    // Version.
    $form['generate']['options']['generate_version'] = [
      '#type' => 'textfield',
      '#title' => t('Version string'),
      '#default_value' => '',
      '#description' => t('Numbers, hyphens and periods only. E.g. 8.x-1.0'),
    ];

    // Directory to save to.
    $form['generate']['options']['generate_directory'] = [
      '#type' => 'select',
      '#title' => t('Save to'),
      '#options' => $dir_options,
      '#description' => t('Select the directory to save the new theme in. If you select "Themes" your new theme will be saved to the same directory as adaptivetheme.'),
    ];

    $form['generate']['options']['generate_directory_files'] = [
      '#type' => 'container',
      '#markup' => '<em>' . t('After saving the theme to the public files directory move it to your themes directory.') . '</em>',
      '#attributes' => ['class' => ['warning', 'messages', 'messages--warning']],
      '#states' => [
        'visible' => ['select[name="generate[options][generate_directory]"]' => ['value' => 'public://']],
      ],
    ];

    $form['generate']['options']['generate_directory_custom'] = [
      '#type' => 'textfield',
      '#title' => t('Custom'),
      '#default_value' => '',
      '#description' => t('Enter the path to save the new theme to, e.g. <code>themes/custom</code>'),
      '#states' => [
        'visible' => ['select[name="generate[options][generate_directory]"]' => ['value' => 'custom']],
      ],
    ];

    $form['generate']['actions']['#type'] = 'actions';
    $form['generate']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate Theme Generator.
    if (!empty($values['generate']['generate_machine_name'])) {
      $machine_name = $values['generate']['generate_machine_name'];
      $theme_data = $this->themeInfoData;

      if (array_key_exists($machine_name, $theme_data) == FALSE) {
        // Targets.
        $target = $values['generate']['options']['generate_directory'];
        $target_error = 'generate][options][generate_directory';
        if ($target == 'custom') {
          $target = $values['generate']['options']['generate_directory_custom'];
          $target_error = 'generate][options][generate_directory_custom';
          if (empty($target)) {
            $form_state->setErrorByName($target_error, t('Custom directory field is empty.'));
          }
        }
        if (!is_dir($target) || !is_writable($target)) {
          $form_state->setErrorByName($target_error, t('The target directory does not exist or is not writable (check permissions and target directory name): <code>:target</code>.', [':target' => $target]));
        }

        $subtheme_type = $values['generate']['generate_type'];
        $source = '';
        $source_error = '';

        if ($subtheme_type == 'starterkit') {
          $source = drupal_get_path('module', 'at_theme_generator') . '/starterkits/starterkit';
        }
        else if ($subtheme_type == 'clone') {
          $source = drupal_get_path('theme', $values['generate']['generate_clone_source']);
          $source_error = 'generate][generate_clone_source';
        }
        else if ($subtheme_type == 'skin') {
          $source = drupal_get_path('module', 'at_theme_generator') . '/starterkits/skin';
          $source_error = 'generate][generate_skin_base';
        }

        // Check if directories and files exist and are readable/writable etc.
        if (!file_exists($source) && !is_readable($source)) {
          $form_state->setErrorByName($source_error, t('The source theme (starter kit, skin base or clone source) can not be found or is not readable:<br /><code>@source</code>', ['@source' => $source]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Don't let this timeout easily.
    set_time_limit(60);

    if (!empty($values['generate']['generate_machine_name'])) {

      // Generate theme.
      if (isset($values['generate']['generate_type']) && !empty($values['generate']['generate_type'])) {
        $type = $values['generate']['generate_type'] . 'Generator';
        $tgt = new ThemeGeneratorTypes($values);
        $tgt->$type();
      }
      else {
        drupal_set_message(t('Bummer, something went wrong. Please try again or contact support.'), 'error');
      }

      // Set messages.
      $tg = new ThemeGenerator($values);
      $target = $tg->targetDirectory();

      $friendly_name = Html::escape($values['generate']['generate_friendly_name']);
      $machine_name = $values['generate']['generate_machine_name'];

      $logger_message = t('A new theme <b>@theme_name</b>, with then machine name: <code><b>@machine_name</b></code>, has been generated.', [
        '@theme_name'   => $friendly_name,
        '@machine_name' => $machine_name
      ]);
      \Drupal::logger('at_generator')->notice($logger_message);

      // Message for the user.
      $final_instructions = 'Click the List tab to view the themes list to enable your new theme.';
      if ($values['generate']['options']['generate_directory'] === 'public://') {
        //$target = PublicStream::basePath() . '/generated_themes/' . $machine_name;
        $final_instructions = 'You will need to move the theme to your sites /themes directory before you can enable it.';
      }
      drupal_set_message(
        t("<p>A new theme <b>@theme_name</b>, with then machine name: <code><b>@machine_name</b></code>, has been generated.</p><p>You can find your theme here: <code>@target</code></p><p>@final_instructions</p>", [
          '@theme_name'         => $friendly_name,
          '@machine_name'       => $machine_name,
          '@target'             => $target,
          '@final_instructions' => $final_instructions,
        ]),
          'status'
      );

      // Refresh data.
      system_list_reset();
      \Drupal::service('theme_handler')->rebuildThemeData();
    }
    else {
      drupal_set_message(t('Bummer, something went wrong with the machine name, please try again or contact support.'), 'error');
    }
  }
}
