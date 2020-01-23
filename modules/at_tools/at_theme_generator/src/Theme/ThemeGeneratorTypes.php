<?php

namespace Drupal\at_theme_generator\Theme;

/**
 * Generate themes by type.
 */
class ThemeGeneratorTypes extends ThemeGenerator {

  /**
   * Generate starter kit type theme.
   */
  public function starterkitGenerator() {
    // Copy source.
    $this->copySource();

    // Yml files.
    $yml_files = ['shortcodes', 'libraries', 'info'];
    $this->renameYmlFiles($yml_files);

    // Replace the version string in libraries.yml.
    $this->rewriteLibrariesYml('VERSION_STRING');

    // [theme_name].theme.
    $this->renameThemeFile();
    $this->rewriteThemeFile('HOOK');

    // theme-settings.php
    if ($this->theme_settings_file === 0) {
      $this->removeThemeSettingsFile();
    }
    else {
      $this->rewriteThemeSettingsFile('HOOK');
    }

    // Config.
    $this->renameConfigFiles();
    $this->rewriteConfigFiles();

    if ($this->block_config === 0) {
      $this->removeConfigFiles();
    }

    // Generated CSS files.
    $this->rewritePageLayoutCSS();
    $this->renameGeneratedCssFiles();

    // Templates.
    $this->rewritePageTemplateLibrary();
    if ($this->templates === 1) {
      $this->copyTemplates();
    }

    // Color.
    if ($this->color === 0) {
      $this->removeColorDirectory();
    }

    // Remove/rename page layouts & layout_plugin.
    $this->removeUnusedLayout();
    $this->renameLayouts();

    // Remove SCSS.
    if ($this->scss === 0) {
      $this->removeCssSourceMaps();
      $this->removeCssSourceMappingURL();
      $this->removeScss();
      $this->removeScssTools();
    }

    // Base theme original - version.
    $base_theme_original = isset($this->base_theme_info['version']) ? $this->base_theme_info['version'] : 'git-dev';

    // Description
    $desc['text'] = $this->description ?: '';
    $desc['base'] = 'at_core (' . $base_theme_original . ')';
    $desc['time']  = $this->datetime;

    // Info
    $info['name']                = "$this->friendly_name";
    $info['version']             = $this->version;
    $info['type']                = "theme";
    $info['base theme']          = 'at_core';
    $info['base theme original'] = $base_theme_original;
    $info['subtheme type']       = 'adaptive_subtheme';
    $info['layout']              = $this->layout_library;
    $info['description']         = $this->infoYmlDescription($desc);
    $info['core']                = '8.x';
    $info['regions']             = $this->info['regions'];
    $info['tags']                = 'adaptivetheme sub-theme';
    $info['libraries-extend']    = ['quickedit/quickedit' => [$this->machine_name . '/quickedit']];
    $info['stylesheets-remove'] = [
      '@stable/css/node/node.preview.css',
      '@stable/css/node/node.module.css',
      '@stable/css/views/views.module.css',
      '@stable/css/system/components/tablesort.module.css',
      '@classy/css/components/file.css',
    ];
    $info['features'] = [
      'logo',
      'favicon',
      'node_user_picture',
      'comment_user_picture',
      'comment_user_verification'
    ];
    $this->infoYml($info);
  }

  /**
   * Generate clone type theme.
   */
  public function cloneGenerator() {
    // Copy source.
    $this->copySource();

    // Yml files.
    $yml_files = ['shortcodes', 'libraries', 'info'];
    $this->renameYmlFiles($yml_files);

    if ($this->info['subtheme type'] === 'adaptive_subtheme') {
      // [theme_name].theme.
      $this->renameThemeFile();
      $this->rewriteThemeFile($this->source['name']);

      // theme-settings.php
      $this->rewriteThemeSettingsFile($this->source['name']);

      // Config.
      $this->renameConfigFiles();
      $this->rewriteConfigFiles();
      $this->replaceCloneConfigSettings();

      // Generated CSS files.
      $this->renameGeneratedCssFiles();

      // Templates.
      $this->rewritePageTemplateLibrary();
    }
    else {
      // Stylesheets and library.
      $this->processSkinStyles($this->source['name']);
    }

    $clone_quotes = [
      'The shroud of the Dark Side has fallen. Begun, this clone war has.',
      'Blind we are, if creation of this clone army we could not see.',
      'The first step to correcting a mistake is patience.',
      'A single chance is a galaxy of hope.',
      'A very wise jedi once said nothing happens by accident.',
      'Smaller in number we are but larger in mind.',
    ];
    $cq = array_rand($clone_quotes);
    $clone_quote = $clone_quotes[$cq];

    // Description
    if (!empty($this->description)) {
      $desc_text = $this->description;
    }
    else{
      $desc_text = '<em>' . $clone_quote . '</em>';
    }
    $desc['text']  = $desc_text;
    $desc['base']  = $this->info['base theme'] . ' (' . $this->info['base theme original'] . ')';
    $desc['time']  = $this->datetime;
    $desc['clone'] = $this->source['name'];

    // Info
    $info['name']                = "$this->friendly_name";
    $info['version']             = $this->version;
    $info['type']                = "theme";
    $info['base theme']          = $this->info['base theme'];
    $info['base theme original'] = $this->info['base theme original'];
    $info['subtheme type']       = $this->info['subtheme type'];
    $info['layout']              = $this->info['layout'];
    $info['description']         = $this->infoYmlDescription($desc);
    $info['core']                = '8.x';
    $info['regions']             = $this->info['regions'];

    // Tags.
    if (isset($this->info['tags']) && !empty($this->info['tags'])) {
      $info['tags'] = $this->info['tags'];
    }
    // Libraries.
    if (isset($this->info['libraries']) && !empty($this->info['libraries'])) {
      $info['libraries'] = $this->info['libraries'];
    }
    // Libraries extend.
    if (isset($this->info['libraries-extend']) && !empty($this->info['libraries-extend'])) {
      $info['libraries-extend'] = $this->info['libraries-extend'];
      if (isset($info['libraries-extend']['quickedit/quickedit'])) {
        $info['libraries-extend']['quickedit/quickedit'] = [$this->machine_name . '/quickedit'];
      }
    }
    // Stylesheets remove.
    if (isset($this->info['stylesheets-remove']) && !empty($this->info['stylesheets-remove'])) {
      $info['stylesheets-remove'] = $this->info['stylesheets-remove'];
    }
    // Features.
    if (isset($this->info['features']) && !empty($this->info['features'])) {
      $info['features'] = $this->info['features'];
    }
    $this->infoYml($info);
  }

  /**
   * Generate skin type theme.
   */
  public function skinGenerator() {
    // Copy source.
    $this->copySource();

    // Yml files.
    $yml_files = ['libraries', 'info'];
    $this->renameYmlFiles($yml_files);

    // Stylesheets and library.
    $this->processSkinStyles('SKIN');

    // Remove SCSS if base theme does not support it.
    $skin_base_path = drupal_get_path('theme', $this->skin_base);
    if (!file_exists($skin_base_path . '/Gruntfile.js')) {
      $this->removeScss();
      $this->removeScssTools();
    }

    // Logos
    $this->replaceSkinLogos();

    // Description
    $desc['text'] = $this->description;
    $desc['base'] = $this->skin_base . ' (' . $this->base_theme_info['base theme original'] . ')';
    $desc['time'] = $this->datetime;
    $desc['skin'] = $this->skin_base;

    // Info
    $info['name']                = "$this->friendly_name";
    $info['version']             = $this->version;
    $info['type']                = "theme";
    $info['base theme']          = $this->skin_base;
    $info['base theme original'] = $this->base_theme_info['base theme original'];
    $info['subtheme type']       = 'adaptive_skin';
    $info['layout']              = $this->base_theme_info['layout'];
    $info['description']         = $this->infoYmlDescription($desc);
    $info['core']                = '8.x';
    $info['regions']             = $this->base_theme_info['regions'];
    $info['tags']                = 'adaptivetheme sub-theme';
    $info['features']            = $this->info['features'];
    $info['libraries']           = [$this->machine_name . '/skin'];
    $this->infoYml($info);
  }
}
