<?php

namespace Drupal\Tests\tome_sync\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\Tests\tome_base\Kernel\TestBase;

/**
 * Tests that the file sync works.
 *
 * @group tome_sync
 */
class FileSyncTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'tome_sync',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('tome_sync', ['tome_sync_content_hash']);
  }

  /**
   * @covers \Drupal\tome_sync\FileSync::importFiles
   */
  public function testImportFiles() {
    $directory = Settings::get('tome_files_directory') . '/public/foo';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    touch(Settings::get('tome_files_directory') . '/public/foo/example.txt');
    touch(Settings::get('tome_files_directory') . '/public/example.txt');
    \Drupal::service('tome_sync.file_sync')->importFiles();
    $this->assertTrue(file_exists('public://foo/example.txt'));
    $this->assertTrue(file_exists('public://example.txt'));
  }

  /**
   * @covers \Drupal\tome_sync\FileSync::deleteExportDirectory
   */
  public function testDeleteExportDirectory() {
    $directory = Settings::get('tome_files_directory') . '/public';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    touch($directory . '/example.txt');
    \Drupal::service('tome_sync.file_sync')->deleteExportDirectory();
    $this->assertFalse(file_exists($directory));
  }

  /**
   * @covers \Drupal\tome_sync\FileSync::deleteFile
   */
  public function testDeleteFile() {
    $directory = Settings::get('tome_files_directory') . '/public';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    touch($directory . '/example.txt');
    \Drupal::service('tome_sync.file_sync')->deleteFile('example.txt');
    $this->assertFalse(file_exists($directory . '/example.txt'));
  }

  /**
   * @covers \Drupal\tome_sync\FileSync::exportFile
   */
  public function testExportFile() {
    touch('public://example.txt');
    $file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $file->save();

    $this->assertTrue(file_exists(Settings::get('tome_files_directory') . '/public/example.txt'));
  }

  /**
   * @covers \Drupal\tome_sync\FileSync::deleteFileExport
   */
  public function testDeleteFileExport() {
    touch('public://example.txt');
    $file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $file->save();

    $this->assertTrue(file_exists(Settings::get('tome_files_directory') . '/public/example.txt'));
    $file->delete();
    $this->assertFalse(file_exists(Settings::get('tome_files_directory') . '/public/example.txt'));
  }

}
