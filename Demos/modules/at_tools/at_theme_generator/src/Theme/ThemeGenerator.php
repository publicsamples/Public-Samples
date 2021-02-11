<?php

namespace Drupal\at_theme_generator\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Yaml;
use Drupal\at_theme_generator\File\FileOperations;
use Drupal\at_theme_generator\File\DirectoryOperations;

/**
 * Generator form.
 */
class ThemeGenerator {

  /**
   * Protected variables.
   */
  protected $machine_name;
  protected $friendly_name;
  protected $sub_theme_type;
  protected $clone_source;
  protected $skin_base;
  protected $templates;
  protected $block_config;
  protected $scss;
  protected $color;
  protected $theme_settings_file;
  protected $dir_option;
  protected $description;
  protected $version;
  protected $datetime;
  protected $generic_description;
  protected $path;
  protected $at_generator_path;
  protected $source;
  protected $target;
  protected $config;

  /**
   * Generator constructor.
   * @param $values
   */
  public function __construct($values) {
    // Form state values.
    $this->machine_name        = $values['generate']['generate_machine_name'];
    $this->friendly_name       = Html::escape(trim($values['generate']['generate_friendly_name']));
    $this->sub_theme_type      = $values['generate']['generate_type'];
    $this->clone_source        = $values['generate']['generate_clone_source'] ?: '';
    $this->skin_base           = $values['generate']['generate_skin_base'] ?: '';
    $this->templates           = $values['generate']['options']['generate_templates'];
    $this->block_config        = $values['generate']['options']['generate_block_config'];
    $this->layout              = $values['generate']['options']['generate_layout'];
    $this->scss                = $values['generate']['options']['generate_scss'];
    $this->color               = $values['generate']['options']['generate_color'];
    $this->theme_settings_file = $values['generate']['options']['generate_themesettingsfile'];
    $this->dir_option          = $values['generate']['options']['generate_directory'];
    $this->dir_option_custom   = $values['generate']['options']['generate_directory_custom'] ?: '';
    $this->description         = preg_replace('/[^\p{Latin}\d\s\p{P}]/u', '', Html::escape(trim($values['generate']['options']['generate_description'])));

    // Handle version strings. I don't bother validating this, just spit it out, users want quick results not a headache.
    if (!empty($values['generate']['options']['generate_version'])) {
      $this->version = Html::escape(str_replace(' ', '', trim($values['generate']['options']['generate_version'])));
    }
    else {
      $this->version = '8.x-1.0';
    }

    // Datetime, description.
    $this->datetime            = \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'D jS, M, Y - G:i');
    $this->generic_description = 'Adaptivetheme sub-theme';

    // File operations.
    $this->fileOperations      = new FileOperations();
    $this->directoryOperations = new DirectoryOperations();

    // Base variables.
    $this->source              = $this->sourceTheme();
    $this->target              = $this->targetDirectory();
    $this->info                = $this->getInfoYml();
    $this->base_theme_info     = $this->getBaseThemeInfoYml();
    $this->layout_library      = $this->getLayout();
    $this->config              = $this->getConfig();
    $this->clone_source_config = $this->getCloneSourceConfigSettings();

    // Paths.
    $this->at_core_path        = drupal_get_path('theme', 'at_core');
    $this->at_generator_path   = drupal_get_path('module', 'at_theme_generator');
  }

  /**
   * Path to where we will save the theme and perform generator operations.
   * @return string
   */
  public function targetDirectory() {
    if ($this->dir_option === 'custom') {
      $target_dir = $this->dir_option_custom;
    }
    elseif ($this->dir_option === 'public://') {
      $target_path = 'public://generated_themes';
      $target_dir = $this->directoryOperations->directoryPrepare([$target_path]);
    }
    else {
      $target_dir = 'themes';
    }

    return $target_dir . '/' . $this->machine_name;
  }

  /**
   * Return the source theme name to use in string operations and the path to
   * the source theme.
   * @return array
   */
  public function sourceTheme() {
    $source = [];
    if ($this->sub_theme_type === 'clone') {
      $source['name'] = $this->clone_source;
      $source['path'] = drupal_get_path('theme', $this->clone_source);
    }
    else {
      $source['name'] = strtoupper($this->sub_theme_type);
      $source['path'] = drupal_get_path('module', 'at_theme_generator') . '/starterkits/' . $this->sub_theme_type;
    }

    return $source;
  }

  /**
   * Copy the source theme to the target location.
   */
  public function copySource() {
    if (is_dir($this->source['path'])) {
      $this->directoryOperations->directoryRecursiveCopy($this->source['path'], $this->target);
    }
  }

  /**
   * Rename .yml files.
   * @param $yml_files array
   */
  public function renameYmlFiles($yml_files) {
    foreach ($yml_files as $file) {
      $this->fileOperations->fileRename(
        $this->target . '/' . $this->source['name'] . '.' . $file . '.yml',
        $this->target . '/' . $this->machine_name . '.' . $file . '.yml'
      );
    }
  }

  /**
   * Rewrite library versions in [theme_name].libraries.yml.
   * @param $needle
   */
  public function rewriteLibrariesYml($needle) {
    $this->fileOperations->fileStrReplace(
      $this->target . '/' . $this->machine_name . '.libraries.yml',
      $needle,
      $this->version
    );
  }

  /**
   * Rename the [theme_name].theme file.
   */
  public function renameThemeFile() {
    $this->fileOperations->fileRename(
      $this->target . '/' . $this->source['name'] . '.theme',
      $this->target . '/' . $this->machine_name . '.theme'
    );
  }

  /**
   * Rewrite the [theme_name].theme file to replace stings.
   * @param $needle
   */
  public function rewriteThemeFile($needle) {
    $this->fileOperations->fileStrReplace(
      $this->target . '/' . $this->machine_name . '.theme',
      $needle,
      $this->machine_name
    );
  }

  /**
   * Rewrite the theme-settings.php file to replace stings.
   * @param $needle
   */
  public function rewriteThemeSettingsFile($needle) {
    $this->fileOperations->fileStrReplace(
      $this->target . '/theme-settings.php',
      $needle,
      $this->machine_name
    );
  }

  /**
   * Remove the theme-settings.php file.
   */
  public function removeThemeSettingsFile() {
    $this->directoryOperations->directoryRemove(
      $this->target . '/theme-settings.php'
    );
  }

  /**
   * Return this themes source configuration files. We need to get the source
   * themes config because the target copy may not yet exist.
   * @return array
   */
  public function getConfig() {
    if (file_exists($this->source['path'] . '/config')) {
      return $this->directoryOperations->directoryScanRecursive($this->source['path'] . '/config');
    }
    else {
      return [];
    }
  }

  /**
   * Rename config files.
   */
  public function renameConfigFiles() {
    if (!empty($this->config)) {
      foreach ($this->config as $config_path => $config_files) {
        $dir = $this->target . '/config/' . $config_path;
        if (is_dir($dir)) {
          foreach ($config_files as $config_file) {
            $new_config_file = str_replace($this->source['name'], $this->machine_name, $config_file) ?: '';
            $target_config_path = $this->target . '/config/' . $config_path;
            $this->fileOperations->fileRename(
              $target_config_path . '/' . $config_file,
              $target_config_path . '/' . $new_config_file
            );
          }
        }
      }
    }

  }

  /**
   * Rewrite config files.
   */
  public function rewriteConfigFiles() {
    if (!empty($this->config)) {
      foreach ($this->config as $config_path => $config_files) {
        $dir = $this->target . '/config/' . $config_path;
        if (is_dir($dir)) {
          foreach ($config_files as $config_file) {
            $new_config_file = str_replace($this->source['name'], $this->machine_name, $config_file) ?: '';
            $target_config_path = $this->target . '/config/' . $config_path;
            $this->fileOperations->fileStrReplace(
              $target_config_path . '/' . $new_config_file,
              'TARGET',
              $this->target
            );
            $this->fileOperations->fileStrReplace(
              $target_config_path . '/' . $new_config_file,
              $this->source['name'],
              $this->machine_name
            );
          }
        }
      }
    }
  }

  /**
   * Get the clone source themes config from the active configuration.
   * @return array
   */
  public function getCloneSourceConfigSettings() {
    return \Drupal::config($this->clone_source . '.settings')->get();
  }

  /**
   * Replace the installation config with the clone sources active config.
   */
  public function replaceCloneConfigSettings() {
    // Empty if the source theme has never been installed, in which case it
    // should be safe to assume there is no new configuration worth saving.
    if (!empty($this->clone_source_config)) {

      // Remove the default config hash.
      if (array_key_exists('_core', $this->clone_source_config)) {
        unset($this->clone_source_config['_core']);
      }

      $old_config = "$this->target/config/install/$this->machine_name.settings.yml";
      $new_config = Yaml::encode($this->clone_source_config);

      $find_generated_files = "themes/$this->clone_source/styles/css/generated";
      $replace_generated_files = "themes/$this->machine_name/styles/css/generated";
      $new_config = str_replace($find_generated_files, $replace_generated_files, $new_config);

      $this->fileOperations->fileReplace($new_config, $old_config);
      $this->fileOperations->fileStrReplace($old_config, $this->clone_source, $this->machine_name);
    }
  }

  /**
   * Remove config files.
   */
  public function removeConfigFiles() {
    $dir = $this->target . '/config/optional';
    if (is_dir($dir)) {
      $this->directoryOperations->directoryRemove($this->target . '/config/optional');
    }
  }

  /**
   * Return this themes generated CSS files.
   * @return array
   */
  public function getGeneratedCssFiles() {
    return $this->directoryOperations->directoryScan($this->target . '/styles/css/generated');
  }

  /**
   * Rewrite the generated layout CSS if it's a float based theme, and remove
   * the float layout.
   */
  public function rewritePageLayoutCSS() {
    if (file_exists($this->target . '/styles/css/generated/FLOAT.layout.page.css')) {
      $file_path = $this->target . '/styles/css/generated/STARTERKIT.layout.page.css';
      $data = file_get_contents($this->target . '/styles/css/generated/FLOAT.layout.page.css', NULL, NULL, 0, 5000);
      if ($this->layout !== 'flex') {
        $this->fileOperations->fileReplace($data, $file_path);
      }
      unlink($this->target . '/styles/css/generated/FLOAT.layout.page.css');
    }
  }

  /**
   * Rename this themes generated CSS files.
   */
  public function renameGeneratedCssFiles() {
    $generated_css_files = $this->getGeneratedCssFiles();
    $generated_css_files_path = $this->target . '/styles/css/generated/';
    foreach ($generated_css_files as $old_css_file) {
      $new_css_file = str_replace($this->source['name'], $this->machine_name, $old_css_file);
      $this->fileOperations->fileRename(
        $generated_css_files_path . '/' . $old_css_file,
        $generated_css_files_path . '/' . $new_css_file
      );
    }
  }

  /**
   * Remove unused layout directory and files.
   */
  public function removeUnusedLayout() {
    if ($this->layout === 'flex') {
      $remove[] = 'page-layout-float';
      $remove[] = 'plugin-layout-float';
    }
    else {
      $remove[] = 'page-layout-flex';
      $remove[] = 'plugin-layout-flex';
    }
    foreach ($remove as $key => $value) {
      $this->directoryOperations->directoryRemove(
        $this->target . '/layout/' . $value
      );
    }
  }

  /**
   * Rename layouts.
   */
  public function renameLayouts() {
    if ($this->layout === 'flex') {
      $rename_dir['page-layout'] = 'page-layout-flex';
      $rename_dir['plugin-layout'] = 'plugin-layout-flex';
    }
    else {
      $rename_dir['page-layout'] = 'page-layout-float';
      $rename_dir['plugin-layout'] = 'plugin-layout-float';
    }
    foreach ($rename_dir as $key => $value) {
      $this->fileOperations->fileRename(
        $this->target . '/layout/' . $value, $this->target . '/layout/' . $key
      );
    }
  }

  /**
   * Rewrite Page Template Library.
   */
  public function rewritePageTemplateLibrary() {
    $this->fileOperations->fileStrReplace(
      $this->target . '/templates/generated/page.html.twig',
      $this->source['name'],
      $this->machine_name
    );
  }

  /**
   * Copy base theme templates.
   */
  public function copyTemplates() {
    $this->directoryOperations->directoryRecursiveCopy(
      $this->at_core_path . '/templates',
      $this->target . '/templates'
    );
  }

  /**
   * Remove the color directory.
   */
  public function removeColorDirectory() {
    $this->directoryOperations->directoryRemove(
      $this->target . '/color'
    );
  }

  /**
   * Return this themes component CSS files.
   * @return array
   */
  public function getComponentCssFiles() {
    return $this->directoryOperations->directoryScan($this->target . '/styles/css/components');
  }

  /**
   * Remove CSS source map files.
   */
  public function removeCssSourceMaps() {
    $this->fileOperations->fileDeleteByExtension(
      $this->target . '/styles/css/components',
      'map'
    );

    // BC. Old themes may have maps in a folder.
    $dir = $this->target . '/styles/css/components/maps';
    if (is_dir($dir)) {
      $this->directoryOperations->directoryRemove(
        $dir
      );
    }
  }

  /**
   * Rename Skin styles & library declarations.
   * @param $type
   */
  public function processSkinStyles($type) {
    $this->fileOperations->fileRename(
      $this->target . '/styles/css/' . $type . '.css',
      $this->target . '/styles/css/' . $this->machine_name . '.css'
    );
    $this->fileOperations->fileRename(
      $this->target . '/styles/scss/' . $type . '.scss',
      $this->target . '/styles/scss/' . $this->machine_name . '.scss'
    );
    $this->fileOperations->fileStrReplace(
      $this->target . '/' . $this->machine_name . '.libraries.yml',
      $type,
      $this->machine_name
    );
  }

  /**
   * Copy logos from the skin source/base to the skin theme.
   */
  public function replaceSkinLogos() {
    $skin_base_path = drupal_get_path('theme', $this->skin_base);
    foreach (['svg', 'png'] as $ext) {
      $logo = $skin_base_path . '/logo.' . $ext;
      if (file_exists($logo)) {
        file_unmanaged_copy($logo, $this->target, FILE_EXISTS_REPLACE);
      }
    }
  }

  /**
   * Remove CSS source mapping URLs in CSS files.
   */
  public function removeCssSourceMappingURL() {
    $component_css_files = $this->getComponentCssFiles();
    $component_css_files_path = $this->target . '/styles/css/components/';
    foreach ($component_css_files as $component_file_key => $component_file) {
      $map_string = '/*# sourceMappingURL=' . str_replace('.css', '.css.map', $component_file) . ' */';
      if (file_exists($component_css_files_path . '/' . $component_file)) {
        $this->fileOperations->fileStrReplace(
          $component_css_files_path . '/' . $component_file,
          $map_string,
          ''
        );
      }
    }
  }

  /**
   * Remove SCSS/SASS related directories and files.
   */
  public function removeScss() {
    $dirs = [
      '/styles/scss',
      '/styles/uikit',
      '/layout/page-layout/sass',
      '/layout/plugin-layout/sass',
      '/bower_components',
      '/node_modules',
    ];
    foreach ($dirs as $dir) {
      if (is_dir($this->target . '/' . $dir)) {
        $this->directoryOperations->directoryRemove(
          $this->target . '/' . $dir
        );
      }
    }
  }

  /**
   * Remove SCSS tools and related files.
   */
  public function removeScssTools() {
    $scss_tools = [
      'bower.json',
      'package.json',
      '.csslintrc',
      'Gruntfile.js',
      'Gemfile',
      'Gemfile.lock',
      '.gitignore',
    ];
    foreach ($scss_tools as $tool) {
      if (file_exists($this->target . '/' . $tool)) {
        unlink($this->target . '/' . $tool);
      }
    }
  }

  /**
   * Get layout.
   * @return string
   */
  public function getLayout() {
    return 'page-layout';
  }

  /**
   * Return the source themes info yml. We need to parse the source themes info
   * yml because the target copy may not yet exist.
   * @return array
   */
  public function getInfoYml() {
    return \Drupal::service('info_parser')->parse($this->source['path'] . '/' . $this->source['name'] . '.info.yml');
  }

  /**
   * Return the base themes info yml.
   * @return array
   */
  public function getBaseThemeInfoYml() {
    $base_theme = $this->info['base theme'];
    if ($this->sub_theme_type === 'skin') {
      $base_theme = $this->skin_base;
    }
    return \Drupal::service('info_parser')->parse(drupal_get_path('theme', $base_theme) . '/' . $base_theme . '.info.yml');
  }

  /**
   * Format theme description.
   * @param $desc
   * @return string
   */
  public function infoYmlDescription($desc) {
    $text   = $desc['text']         ? $desc['text'] . ' <br>' : '';
    $clone  = isset($desc['clone']) ? 'Clone of: '  . $desc['clone'] . ' <br>' : '';
    $skin   = isset($desc['skin'])  ? 'Skin of: '   . $desc['skin']  . ' <br>' : '';
    $base   = $desc['base']         ? 'Base: '      . $desc['base']  . ' <br>' : '';
    $time   = $desc['time']         ? 'Generated: ' . $desc['time']  : '';

    return $text . $clone . $skin . $base . $time;
  }

  /**
   * @param $info
   */
  public function infoYml($info) {
    $rebuilt_info = $this->fileOperations->fileBuildInfoYml($info);
    $this->fileOperations->fileReplace($rebuilt_info, $this->target . '/' . $this->machine_name . '.info.yml');
  }
}
