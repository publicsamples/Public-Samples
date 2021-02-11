<?php

namespace Drupal\Tests\pagerer\FunctionalJavascript;

use Drupal\Tests\views\FunctionalJavascript\PaginationAJAXTest;

/**
 * Tests the click sorting AJAX functionality of Views exposed forms.
 *
 * @group Pagerer
 */
class CorePagerReplacePaginationAJAXTest extends PaginationAJAXTest {

  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_test_config', 'pagerer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add a 'core_replace' pagerer preset.
    $this->drupalGet($this->pagererAdmin . '/preset/add');
    $this->submitForm([
      'label' => 'core_replace',
    ], t('Create'));

    // Make 'core_replace' pagerer preset the global pager replacement.
    \Drupal::configFactory()->getEditable('pagerer.settings')
      ->set('core_override_preset', 'core_replace')
      ->save();
  }

}
