<?php

namespace Drupal\Tests\scanner\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensure the module works as intended.
 *
 * @group scanner
 */
class Scanner extends BrowserTestBase {

  // Contains helper methods.
  use ScannerHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'views',

    // This custom module.
    'scanner',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Make sure to complete the normal setup steps first.
    parent::setUp();

    // Set the front page to "/node".
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);

    // Create a test content type.
    $this->createContentTypeNode();

    // Log in as user 1.
    $this->loginUser1();
  }

  /**
   * Make sure the site still works. For now just check the front page.
   */
  public function testTheSiteStillWorks() {
    // Load the front page.
    $this->drupalGet('<front>');

    // Confirm that the site didn't throw a server error or something else.
    $this->assertSession()->statusCodeEquals(200);

    // Because test content exists, look for the test node.
    $this->assertText('Title test');
  }

  /**
   * Confirm the settings page works.
   */
  public function testSettingsForm() {
    // Confirm the settings form loads.
    $this->drupalGet('admin/config/content/scanner');
    $this->assertSession()->statusCodeEquals(200);

    // Check for all checkboxes.
    $this->assertFieldByName('scanner_mode');
    $this->assertFieldByName('scanner_wholeword');
    $this->assertFieldByName('scanner_regex');
    $this->assertFieldByName('scanner_published');
    $this->assertFieldByName('scanner_pathauto');
    $this->assertFieldByName('scanner_language');
    $this->assertFieldByName('enabled_content_types[node:article]');
  }

}
