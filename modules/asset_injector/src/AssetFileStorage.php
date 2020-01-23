<?php

namespace Drupal\asset_injector;

/**
 * Class AssetFileStorage.
 *
 * @package Drupal\asset_injector
 *
 * This asset file storage class implements a content-addressed file system
 * where each file is stored in a location like so:
 * public://asset_injector/[extension]/[name]-[md5].[extension]
 * Note that the name and extension-dir are redundant and purely for DX.
 *
 * Due to the nature of the config override system, the content of any asset
 * config entity can vary on external factory beyond our control, be it
 * language, domain, settings.php overrides or anything else. In other words,
 * any asset entity can map to an arbitrary number of actual assets.
 * Thus asset files are generated in AssetFileStorage::internalFileUri()
 * with a file name that is unique by their content, and only deleted on cache
 * flush.
 *
 * Also see comments on caching in @see asset_injector_page_attachments().
 */
final class AssetFileStorage {

  /**
   * Asset with file storage.
   *
   * @var AssetInjectorInterface
   */
  protected $asset;

  /**
   * AssetFileStorage constructor.
   *
   * @param AssetInjectorInterface $asset
   *   The asset.
   */
  public function __construct(AssetInjectorInterface $asset) {
    $this->asset = $asset;
  }

  /**
   * Create file and return internal uri.
   *
   * @return string
   *   Internal file URI using public:// stream wrapper.
   */
  public function createFile() {
    $internal_uri = self::internalFileUri();
    if (!is_file($internal_uri)) {
      $directory = dirname($internal_uri);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      file_unmanaged_save_data($this->asset->getCode(), $internal_uri, FILE_EXISTS_REPLACE);
    }
    return $internal_uri;
  }

  /**
   * Delete files for an asset.
   *
   * Yes, we can have multiple files for an asset configuration, if we have
   * overrides.
   */
  public function deleteFiles() {
    $pattern = $this->internalFileUri(TRUE);
    $paths = glob($pattern);
    foreach ($paths as $path) {
      file_unmanaged_delete($path);
    }
  }

  /**
   * Create internal file URI or pattern.
   *
   * @param bool $pattern
   *   Get Pattern instead of internal file URI.
   *
   * @return string
   *   File uri.
   */
  protected function internalFileUri($pattern = FALSE) {
    $name = $this->asset->id();
    $extension = $this->asset->extension();
    $hash = $pattern ? '*' : md5($this->asset->getCode());
    $all_assets_directory = self::internalDirectoryUri();
    if ($pattern) {
      // glob() does not understand stream wrappers. Sigh.
      $all_assets_directory = \Drupal::service('file_system')
        ->realpath($all_assets_directory);
    }
    $internal_uri = "$all_assets_directory/$extension/$name-$hash.$extension";
    return $internal_uri;
  }

  /**
   * Get our directory.
   *
   * @return string
   *   Directory of the assets.
   */
  protected static function internalDirectoryUri() {
    return 'public://asset_injector';
  }

  /**
   * Delete all asset files.
   *
   * @see asset_injector_cache_flush()
   */
  public static function deleteAllFiles() {
    $internal_uri = self::internalDirectoryUri();
    if (file_exists($internal_uri)) {
      file_unmanaged_delete_recursive($internal_uri);
    }
  }

}
