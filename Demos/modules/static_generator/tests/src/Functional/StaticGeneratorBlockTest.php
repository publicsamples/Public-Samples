<?php

namespace Drupal\Tests\static_generator\Functional;

use Drupal;
use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\BlockCreationTrait;

/**
 * Tests generating blocks.
 *
 * @group block
 */
class StaticGeneratorBlockTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Installation profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'static_generator',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissionsAdmin = [
    'administer static generator',
    'access administration pages',
    'administer users',
    'administer account settings',
    'administer site configuration',
    'administer user fields',
    'administer user form display',
    'administer user display',
    'administer blocks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissionsAdmin, 'test_admin', TRUE);

    $this->container->get('theme_installer')->install(['stable', 'classy']);
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'classy')
      ->save();
  }

  /**
   * Tests block generation.
   *
   * @throws \Exception
   */
  public function testBlockGeneration() {

    // Login as admin user.
    $this->drupalLogin($this->adminUser);

    for ($i = 0; $i < 5000; $i++) {
      //      $block_id = substr(uniqid(), 0, 10);
      //      $default_theme = $this->config('system.theme')->get('default');
      //      $edit = [
      //        'edit-info-0-value' => $block_id,
      //      ];
      //      $this->drupalGet('block/add');
      //      $this->assertSession()->pageTextContains('Add custom block');
      //      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->placeBlock('system_powered_by_block');
    }

    //    $this->drupalGet('/node');
    //    $this->assertSession()->statusCodeEquals(200);
    //    $this->assertSession()->responseContains('Powered by');
    //    $this->assertSession()
    //      ->elementsCount('css', '.block-system-powered-by-block', 2);

    //    $directory =  'private://';
    //    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $elapsed_time = Drupal::service('static_generator')->generateBlocks();
    $this->assertTrue($elapsed_time > 0, 'Block generation elapsed time: ' . $elapsed_time);
    file_unmanaged_save_data($elapsed_time, 'private://elapsed_time.txt', FILE_EXISTS_REPLACE);

    //drupal_unlink('private://index.html');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {

  }
}