<?php

/**
 * @file
 * Contains \Drupal\at_theme_generator\File\FileOperations
 */

namespace Drupal\at_theme_generator\File;

use Drupal\Component\Serialization\Yaml;

class FileOperations {

  /**
   * Rename old file to new file.
   *
   * @param string $old_file
   *   Source file to be renamed.
   * @param string $new_file
   *   The new file name.
   */
  public function fileRename($old_file, $new_file) {
    if (file_exists($old_file)) {
      rename($old_file, $new_file);
    }
  }

  /**
   * Replace strings in a file.
   *
   * @param $file_path
   *   The file to be processed (haystack).
   * @param $find
   *   The target string (needle).
   * @param $replace
   *   The replacement string.
   */
  public function fileStrReplace($file_path, $find, $replace) {
    if (file_exists($file_path)) {
      $file_contents = file_get_contents($file_path);
      $file_contents = str_replace($find, $replace, $file_contents);
      file_put_contents($file_path, $file_contents);
    }
  }

  /**
   * Copy and rename a file.
   *
   * @param array $file_paths
   *   Associative array:
   *    - copy_source => "path to the source file"
   *    - copy_dest => "the destination path"
   *    - rename_oldname => "the old file name"
   *    - rename_newname => "the new file name"
   */
  public function fileCopyRename($file_paths) {
    if (file_exists($file_paths['copy_source'])) {
      file_unmanaged_copy($file_paths['copy_source'], $file_paths['copy_dest'], FILE_EXISTS_RENAME);
    }
  }

  /**
   * Replace old file content with new content.
   *
   * @param string $data
   *   Content to replace old file contents.
   * @param string $file_path
   *   Path to file to be replaced.
   */
  public function fileReplace($data, $file_path) {
    if (file_exists($file_path)) {
      file_unmanaged_save_data($data, $file_path, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * Generate an .info.yml file that can be parsed by drupal_parse_info_file().
   *
   * @param array $data
   *   The associative array data to build the .info.yml file.
   * @return string
   *   A string corresponding to $data encoded in the .yml format.
   *
   * @see drupal_parse_info_file()
   */
  public function fileBuildInfoYml(array $data) {
    $info = Yaml::encode($data);
    return $info;
  }

  /**
   * Unlink all files by extension in a directory.
   *
   * @param string $ext
   * @param string $file_path
   */
  public function fileDeleteByExtension($file_path, $ext) {
    $glob_files = glob("$file_path/*.$ext");
    foreach ($glob_files as $file) {
      \Drupal::service('file_system')->unlink($file);
    }
  }

}
