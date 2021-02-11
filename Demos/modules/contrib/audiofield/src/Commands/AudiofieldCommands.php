<?php

namespace Drupal\audiofield\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\audiofield\AudioFieldPlayerManager;

/**
 * A Drush commandfile for Audiofield module.
 */
class AudiofieldCommands extends DrushCommands {

  /**
   * Library discovery service.
   *
   * @var Drupal\audiofield\AudioFieldPlayerManager
   */
  protected $playerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AudioFieldPlayerManager $player_manager) {
    $this->playerManager = $player_manager;
  }

  /**
   * Downloads the suggested Audiofield libraries from their remote repos.
   *
   * @param string $installLibrary
   *   The name of the library. If omitted, all libraries will be installed.
   * @param bool $print_messages
   *   Flag indicating if messages should be displayed.
   *
   * @command audiofield:download
   * @aliases audiofield-download
   */
  public function download($installLibrary = '', $print_messages = TRUE) {

    // Declare filesystem container.
    $fs = new Filesystem();

    // Get a list of the audiofield plugins.
    $pluginList = $this->playerManager->getDefinitions();

    // If there is an argument, check to make sure its valid.
    if (!empty($installLibrary)) {
      if (!isset($pluginList[$installLibrary . '_audio_player'])) {
        $this->logger()->error(dt('Error: @library is not a valid Audiofield library.', [
          '@library' => $installLibrary,
        ], 'error'));
        return;
      }
      // If the argument is valid, we only want to install that plugin.
      $pluginList = [$installLibrary . '_audio_player' => $pluginList[$installLibrary . '_audio_player']];
    }

    // Loop over each plugin and make sure it's library is installed.
    foreach ($pluginList as $pluginName => $plugin) {
      // Create an instance of this plugin.
      $pluginInstance = $this->playerManager->createInstance($pluginName);

      // Only check install if there is a library for the plugin.
      if (!$pluginInstance->getPluginLibrary()) {
        continue;
      }

      // Skip if the plugin is installed.
      if ($pluginInstance->checkInstalled()) {
        if ($print_messages) {
          $this->logger()->notice(dt('Audiofield library for @library is already installed at @location', [
            '@library' => $pluginInstance->getPluginTitle(),
            '@location' => $pluginInstance->getPluginLibraryPath(),
          ], 'success'));
        }
        continue;
      }

      // Get the library install path.
      $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();
      // Create the install directory if it does not exist.
      if (!is_dir($path)) {
        $fs->mkdir($path);
      }

      // Download the file.
      $client = new Client();
      $destination = tempnam(sys_get_temp_dir(), 'file.') . "tar.gz";
      try {
        $client->get($pluginInstance->getPluginRemoteSource(), ['save_to' => $destination]);
      }
      catch (RequestException $e) {
        // Remove the directory.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to download @library. @exception', [
          '@library' => $pluginInstance->getPluginTitle(),
          '@exception' => $e->getMessage(),
        ], 'error'));
        continue;
      }
      $fs->rename($destination, $path . '/audiofield-dl.zip');
      if (!file_exists($path . '/audiofield-dl.zip')) {
        // Remove the directory where we tried to install.
        $fs->remove($path);
        if ($print_messages) {
          $this->logger()->error(dt('Error: unable to download Audiofield library @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ], 'error'));
          continue;
        }
      }

      // Unzip the file.
      $zip = new \ZipArchive();
      $res = $zip->open($path . '/audiofield-dl.zip');
      if ($res === TRUE) {
        $zip->extractTo($path);
        $zip->close();
      }
      else {
        // Remove the directory.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to unzip @library.', [
          '@library' => $pluginInstance->getPluginTitle(),
        ], 'error'));
        continue;
      }

      // Remove the downloaded zip file.
      $fs->remove($path . '/audiofield-dl.zip');

      // If the library still is not installed, we need to move files.
      if (!$pluginInstance->checkInstalled()) {
        // Find all folders in this directory and move their
        // subdirectories up to the parent directory.
        $directories = Finder::create()
          ->directories()
          ->depth('< 1')
          ->in($path)
          ->ignoreDotFiles(TRUE)
          ->ignoreVCS(TRUE);
        foreach ($directories as $dirName) {
          $fs->mirror($dirName, $path, NULL, ['override' => TRUE]);
          $fs->remove($dirName);
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
          $this->logger()->notice(dt('Audiofield library for @library has been successfully installed at @location', [
            '@library' => $pluginInstance->getPluginTitle(),
            '@location' => $pluginInstance->getPluginLibraryPath(),
          ], 'success'));
        }
      }
      else {
        // Remove the directory where we tried to install.
        $fs->remove($path);
        if ($print_messages) {
          $this->logger()->error(dt('Error: unable to install Audiofield library @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ], 'error'));
        }
      }
    }
  }

  /**
   * Updates Audiofield libraries from their remote repos if out of date.
   *
   * @param string $updateLibrary
   *   The name of the library. If omitted, all libraries will be updated.
   * @param bool $print_messages
   *   Flag indicating if messages should be displayed.
   *
   * @command audiofield:update
   * @aliases audiofield-update
   */
  public function update($updateLibrary = '', $print_messages = TRUE) {

    // Declare filesystem container.
    $fs = new Filesystem();

    // Get a list of the audiofield plugins.
    $pluginList = $this->playerManager->getDefinitions();

    // If there is an argument, check to make sure its valid.
    if (!empty($updateLibrary)) {
      if (!isset($pluginList[$updateLibrary . '_audio_player'])) {
        $this->logger()->error(dt('Error: @library is not a valid Audiofield library.', [
          '@library' => $updateLibrary,
        ], 'error'));
        return;
      }
      // If the argument is valid, we only want to install that plugin.
      $pluginList = [$updateLibrary . '_audio_player' => $pluginList[$updateLibrary . '_audio_player']];
    }

    // Loop over each plugin and make sure it's library is installed.
    foreach ($pluginList as $pluginName => $plugin) {
      // Create an instance of this plugin.
      $pluginInstance = $this->playerManager->createInstance($pluginName);

      // Only check install if there is a library for the plugin.
      if (!$pluginInstance->getPluginLibrary()) {
        continue;
      }

      // Get the library install path.
      $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();

      // If the library isn't installed at all we just run the install.
      if (!$pluginInstance->checkInstalled(FALSE)) {
        $this->download($pluginInstance->getPluginLibraryName());
        continue;
      }

      // Don't updating the library if its up to date.
      if ($pluginInstance->checkVersion(FALSE)) {
        $this->logger()->notice(dt('Audiofield library for @library is already up to date', [
          '@library' => $pluginInstance->getPluginTitle(),
        ], 'success'));
        continue;
      }

      // Move the current installation to the temp directory.
      $fs->rename($path, file_directory_temp() . '/temp_audiofield', TRUE);
      // If the directory failed to move, just delete it.
      if (is_dir($path)) {
        $fs->remove($path);
      }

      // Run the install command now to get the latest version.
      $this->download($updateLibrary, FALSE);

      // Check if library has been properly installed.
      if ($pluginInstance->checkInstalled()) {
        // Remove the temporary directory.
        $fs->remove(file_directory_temp() . '/temp_audiofield');
        $this->logger()->notice(dt('Audiofield library for @library has been successfully updated at @location', [
          '@library' => $pluginInstance->getPluginTitle(),
          '@location' => $pluginInstance->getPluginLibraryPath(),
        ], 'success'));
      }
      else {
        // Remove the directory where we tried to install.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to update Audiofield library @library', [
          '@library' => $pluginInstance->getPluginTitle(),
        ], 'error'));
        // Restore the original install since we failed to update.
        $fs->rename(file_directory_temp() . '/temp_audiofield', $path, TRUE);
      }
    }
  }

}
