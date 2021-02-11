<?php

namespace Drupal\tome_sync;

use Drupal\Core\Config\StorageException;
use Drupal\Core\Site\Settings;
use Drupal\file\FileInterface;
use Drupal\tome_base\PathTrait;

/**
 * Handles file import and exports by keeping a file export directory in sync.
 *
 * @internal
 */
class FileSync implements FileSyncInterface {

  use PathTrait;

  /**
   * {@inheritdoc}
   */
  public function importFiles() {
    $file_directory = $this->getFileDirectory();
    /** @var \Drupal\file\FileInterface $file */
    foreach (file_scan_directory($file_directory, '/.*/') as $file) {
      $destination = 'public://' . ltrim(str_replace($file_directory, '', $file->uri), '/');
      $directory = dirname($destination);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
      file_unmanaged_copy($file->uri, $destination, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteExportDirectory() {
    $file_directory = $this->getFileDirectory();
    if (file_exists($file_directory)) {
      if (!file_unmanaged_delete_recursive($file_directory)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exportFile(FileInterface $file) {
    $this->ensureFileDirectory();
    $file_directory = $this->getFileDirectory();
    if (strpos($file->getFileUri(), 'public://') === 0 && file_exists($file->getFileUri())) {
      $destination = $this->joinPaths($file_directory, file_uri_target($file->getFileUri()));
      $directory = dirname($destination);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
      file_unmanaged_copy($file->getFileUri(), $destination, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFileExport(FileInterface $file) {
    $file_directory = $this->getFileDirectory();
    if (strpos($file->getFileUri(), 'public://') === 0) {
      $path = $this->joinPaths($file_directory, file_uri_target($file->getFileUri()));
      if (file_exists($path)) {
        file_unmanaged_delete($path);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($filename) {
    $path = $this->joinPaths($this->getFileDirectory(), $filename);
    if (file_exists($path)) {
      file_unmanaged_delete($path);
    }
  }

  /**
   * Gets the file directory.
   *
   * @return string
   *   The file directory.
   */
  protected function getFileDirectory() {
    return Settings::get('tome_files_directory', '../files') . '/public';
  }

  /**
   * Ensures that the file directory exists.
   */
  protected function ensureFileDirectory() {
    $file_directory = $this->getFileDirectory();
    file_prepare_directory($file_directory, FILE_CREATE_DIRECTORY);
    file_save_htaccess($file_directory);
    if (!file_exists($file_directory)) {
      throw new StorageException('Failed to create config directory ' . $file_directory);
    }
  }

}
