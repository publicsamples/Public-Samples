<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Checks Pagerer functionality.
 *
 * @group Pagerer
 */
class PagererTest extends BrowserTestBase {

  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  public static $modules = ['dblog', 'pagerer', 'pagerer_example'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer site configuration',
    ]));
  }

  /**
   * Tests Pagerer functionality.
   */
  public function testPagerer() {
    // Admin UI tests.
    $edit = [
      'label' => 'ui_test',
      'id' => 'ui_test',
    ];
    $this->drupalPostForm($this->pagererAdmin . '/preset/add', $edit, 'Create');
    $edit = [
      'core_override_preset' => 'ui_test',
    ];
    $this->drupalPostForm($this->pagererAdmin, $edit, 'Save configuration');
    $styles = [
      'standard',
      'none',
      'basic',
      'progressive',
      'adaptive',
      'mini',
      'slider',
      'scrollpane',
    ];
    foreach ($styles as $style) {
      $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
      $edit = [
        'panes_container[left][style]' => 'none',
        'panes_container[center][style]' => 'none',
        'panes_container[right][style]' => $style,
      ];
      $this->drupalPostForm(NULL, $edit, 'Save');
      $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
      if ($style !== 'none') {
        $this->click('[id="edit-panes-container-right-actions-reset"]');
        $this->click('[id="edit-submit"]');
        $this->assertNoRaw('fooxiey');
        $this->click('[id="edit-panes-container-right-actions-configure"]');
        $edit = [
          'prefix_display' => '1',
          'tags_container[pages][prefix_label]' => 'fooxiey',
        ];
        $this->drupalPostForm(NULL, $edit, 'Save');
        $this->assertRaw('fooxiey');
      }
    }

    // Load example page.
    $this->drupalGet('pagerer/example');
  }

}
