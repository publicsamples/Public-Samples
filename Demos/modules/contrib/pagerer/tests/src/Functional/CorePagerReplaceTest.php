<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Pager\PagerTest;

/**
 * Test replacement of Drupal core pager.
 *
 * @group Pagerer
 */
class CorePagerReplaceTest extends PagerTest {

  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  public static $modules = [
    'dblog',
    'image',
    'pager_test',
    'pagerer',
    'pagerer_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    BrowserTestBase::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer image styles',
      'administer site configuration',
    ]));

    $edit = [
      'label' => 'core_replace',
      'id' => 'core_replace',
    ];
    $this->drupalPostForm($this->pagererAdmin . '/preset/add', $edit, 'Create');
    $edit = [
      'core_override_preset' => 'core_replace',
    ];
    $this->drupalPostForm($this->pagererAdmin, $edit, 'Save configuration');
  }

  /**
   * Test that pagerer specific cache tags have been added.
   */
  public function testPagerQueryParametersAndCacheContext() {
    parent::testPagerQueryParametersAndCacheContext();
    $this->assertCacheTag('config:pagerer.settings');
    $this->assertCacheTag('config:pagerer.preset.core_replace');
  }

}
