<?php

/**
 * @file
 * Contains \Drupal\at_theme_generator\Theme\ThemeInfo.
 */

namespace Drupal\at_theme_generator\Theme;

/**
 * ThemeSettingsInfo declares methods used to return theme info for use in
 * theme-settings.php. Note the constructor calls system_rebuild_theme_data()
 * which is not statically cached therefor only used in the backend, however
 * it always returns fresh data.
 */
class ThemeInfo {

  /**
   * Constructs a theme info object.
   */
  public function __construct() {
    $this->data = \Drupal::service('theme_handler')->rebuildThemeData();
  }

  /**
   * Return list of base theme options.
   * Looks for all themes with a base theme value of 'at_core' and returns
   * the list. This means you cannot sub-theme a "skin" type sub-theme.
   *
   * @param $subtheme_type
   * @return array
   */
  public function themeOptions($subtheme_type) {
    $base_themes = [];
    foreach ($this->data as $machine_name => $theme) {
      foreach ($theme as $theme_key => $theme_values) {
        if ($theme_key === 'info') {
          if (isset($theme_values['subtheme type']) && $theme_values['subtheme type'] === $subtheme_type) {
            $base_themes[$machine_name] = $machine_name;
          }
        }
      }
    }

    // These are just generator "templates, not to be used directly. BC.
    unset($base_themes['at_standard']);
    unset($base_themes['at_minimal']);
    unset($base_themes['at_skin']);
    unset($base_themes['at_starterkit']);
    unset($base_themes['at_generator']);
    unset($base_themes['THEMENAME']);
    unset($base_themes['STARTERKIT']);
    unset($base_themes['SKIN']);

    return $base_themes;
  }


  /**
   * Check if a theme name already exists.
   * Looks in the list of themes to see if a theme name already exists, if so
   * returns TRUE. This is the callback method for the form field machine_name
   * as used in theme-settings.php for the theme Generator.
   *
   * @param $machine_name
   * @return boolean
   */
  public function themeNameExists($machine_name) {
    $result = FALSE;
    if (array_key_exists($machine_name, $this->data)) {
      $result = TRUE;
    }
    
    return $result;
  }
}
