<?php

/**
 * @file
 * Drush integration for audiofield.
 */

/**
 * Implements hook_drush_command().
 *
 * Defining a drush command to install the Audiofield external libraries.
 */
function audiofield_drush_command() {
  $items = [];

  $items['audiofield-download'] = [
    'description' => dt('Downloads the suggested Audiofield libraries from their remote repos.'),
    'callback' => 'audiofield_drush_lib_download',
    'arguments' => [
      'updateLibrary' => dt('The name of the library to install. If omitted, all libraries will be installed.'),
    ],
  ];
  $items['audiofield-update'] = [
    'description' => dt('Updates the Audiofield libraries from their remote repos if they are out of date.'),
    'callback' => 'audiofield_drush_lib_update',
    'arguments' => [
      'installLibrary' => dt('The name of the library to update. If omitted, all libraries will be updated.'),
    ],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * Help description for the audiofield drush command.
 */
function audiofield_drush_help($section) {
  switch ($section) {
    case 'drush:audiofield-download':
      return dt("Downloads the AudioField external libraries.");

    case 'drush:audiofield-update':
      return dt("Updates the AudioField external libraries.");
  }
}

/**
 * Drush command callback.
 *
 * Updates the external libraries required for Audiofield plugins.
 *
 * @param string $updateLibrary
 *   The name of the library to be updated, or blank for all libraries.
 */
function audiofield_drush_lib_update($updateLibrary = '') {
  // Get a list of the audiofield plugins.
  $pluginList = \Drupal::service('plugin.manager.audiofield')->getDefinitions();

  // If there is an argument, check to make sure its valid.
  if (!empty($updateLibrary)) {
    if (!isset($pluginList[$updateLibrary . '_audio_player'])) {
      drush_log(dt('Error: @library is not a valid Audiofield library.', [
        '@library' => $updateLibrary,
      ], 'error'), 'error');
      return;
    }
    // If the argument is valid, we only want to install that plugin.
    $pluginList = [$updateLibrary . '_audio_player' => $pluginList[$updateLibrary . '_audio_player']];
  }

  // Loop over each plugin and make sure it's library is installed.
  foreach ($pluginList as $pluginName => $plugin) {
    // Create an instance of this plugin.
    $pluginInstance = \Drupal::service('plugin.manager.audiofield')->createInstance($pluginName);

    // Only check install if there is a library for the plugin.
    if ($pluginInstance->getPluginLibrary()) {
      // Get the library install path.
      $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();
      // Only update if the library is already installed.
      if ($pluginInstance->checkInstalled(FALSE)) {
        // Only updating the library if its out of date.
        if (!$pluginInstance->checkVersion(FALSE)) {
          // Move the current installation to the temp directory.
          drush_move_dir($path, file_directory_temp() . '/temp_audiofield', TRUE);
          // If the directory failed to move, just delete it.
          if (is_dir($path)) {
            drush_delete_dir($path);
          }

          // Run the install command now to get the latest version.
          audiofield_drush_lib_download($updateLibrary, FALSE);

          // Check if library has been properly installed.
          if ($pluginInstance->checkInstalled()) {
            // Remove the temporary directory.
            drush_delete_dir(file_directory_temp() . '/temp_audiofield');
            drush_log(dt('Audiofield library for @library has been successfully updated at @location', [
              '@library' => $pluginInstance->getPluginTitle(),
              '@location' => $pluginInstance->getPluginLibraryPath(),
            ], 'success'), 'success');
          }
          else {
            // Remove the directory where we tried to install.
            drush_delete_dir($path);
            drush_log(dt('Error: unable to update Audiofield library @library', [
              '@library' => $pluginInstance->getPluginTitle(),
            ], 'error'), 'error');
            // Restore the original install since we failed to update.
            drush_move_dir(file_directory_temp() . '/temp_audiofield', $path, TRUE);
          }
        }
        else {
          drush_log(dt('Audiofield library for @library is already up to date', [
            '@library' => $pluginInstance->getPluginTitle(),
          ], 'success'), 'ok');
        }
      }
      else {
        // The library isn't installed at all, so we just run the install.
        audiofield_drush_lib_download($pluginInstance->getPluginLibraryName());
      }
    }

  }
}

/**
 * Drush command callback.
 *
 * Installs the external libraries required for Audiofield plugins.
 *
 * @param string $installLibrary
 *   The name of the library to be installed, or blank for all libraries.
 * @param bool $print_messages
 *   Flag to indicate if messages should be printed.
 */
function audiofield_drush_lib_download($installLibrary = '', $print_messages = TRUE) {
  // Get a list of the audiofield plugins.
  $pluginList = \Drupal::service('plugin.manager.audiofield')->getDefinitions();

  // If there is an argument, check to make sure its valid.
  if (!empty($installLibrary)) {
    if (!isset($pluginList[$installLibrary . '_audio_player'])) {
      drush_log(dt('Error: @library is not a valid Audiofield library.', [
        '@library' => $installLibrary,
      ], 'error'), 'error');
      return;
    }
    // If the argument is valid, we only want to install that plugin.
    $pluginList = [$installLibrary . '_audio_player' => $pluginList[$installLibrary . '_audio_player']];
  }

  // Loop over each plugin and make sure it's library is installed.
  foreach ($pluginList as $pluginName => $plugin) {
    // Create an instance of this plugin.
    $pluginInstance = \Drupal::service('plugin.manager.audiofield')->createInstance($pluginName);

    // Only check install if there is a library for the plugin.
    if ($pluginInstance->getPluginLibrary()) {
      // Check if the plugin is installed and that there is an available source.
      if (!$pluginInstance->checkInstalled()) {
        // Get the library install path.
        $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();
        // Create the install directory if it does not exist.
        if (!is_dir($path)) {
          drush_mkdir($path);
        }
        // Attempt to download and unzip.
        if (drush_op('chdir', $path) &&
          drush_shell_exec('wget ' . $pluginInstance->getPluginRemoteSource() . ' -O audiofield-dl.zip') &&
          drush_shell_exec('unzip audiofield-dl.zip') &&
          drush_shell_exec('rm -rf audiofield-dl.zip')
          ) {
          // If the library still is not installed, we need to move files.
          if (!$pluginInstance->checkInstalled()) {
            // Find all folders in this directory and move their
            // subdirectories up to the parent directory.
            foreach (drush_scan_directory($path, '%.*%', ['.', '..', 'CVS'], 0, FALSE) as $dir) {
              if (is_dir($dir->basename)) {
                drush_move_dir($path . '/' . $dir->basename, '../tmp', TRUE);
                drush_move_dir('../tmp', $path, TRUE);
              }
            }

            // Projekktor source files need to be installed.
            if ($pluginInstance->getPluginId() == 'projekktor_audio_player') {
              drush_op('chdir', '..');
              drush_op('chdir', $path);
              drush_shell_exec('npm install');
              drush_shell_exec('grunt --force');
            }
          }
          if ($pluginInstance->checkInstalled()) {
            if ($print_messages) {
              drush_log(dt('Audiofield library for @library has been successfully installed at @location', [
                '@library' => $pluginInstance->getPluginTitle(),
                '@location' => $pluginInstance->getPluginLibraryPath(),
              ], 'success'), 'success');
            }
          }
          else {
            // Remove the directory where we tried to install.
            drush_delete_dir($path);
            if ($print_messages) {
              drush_log(dt('Error: unable to install Audiofield library @library', [
                '@library' => $pluginInstance->getPluginTitle(),
              ], 'error'), 'error');
            }
          }
        }
        else {
          // Remove the directory where we tried to install.
          drush_delete_dir($path);
          if ($print_messages) {
            drush_log(dt('Error: unable to download Audiofield library @library', [
              '@library' => $pluginInstance->getPluginTitle(),
            ], 'error'), 'error');
          }
        }
      }
      else {
        if ($print_messages) {
          drush_log(dt('Audiofield library for @library is already installed at @location', [
            '@library' => $pluginInstance->getPluginTitle(),
            '@location' => $pluginInstance->getPluginLibraryPath(),
          ], 'success'), 'ok');
        }
      }
    }
  }
}

/**
 * Implements drush_MODULE_post_COMMAND().
 *
 * This is used to install libraries after the module is enabled in drush.
 */
function drush_audiofield_post_pm_enable() {
  $extensions = func_get_args();
  // Deal with comma delimited extension list.
  if (strpos($extensions[0], ',') !== FALSE) {
    $extensions = explode(',', $extensions[0]);
  }

  if (in_array('audiofield', $extensions) && !drush_get_option('skip')) {
    if (drush_confirm('Do you want to install external libraries for use with Audiofield? (Note: you can install manually later)')) {
      audiofield_drush_lib_download();
    }
  }
}
