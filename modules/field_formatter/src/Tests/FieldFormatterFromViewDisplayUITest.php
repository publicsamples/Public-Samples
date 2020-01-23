<?php

namespace Drupal\field_formatter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that field_formatter UI work correctly.
 *
 * @group field_formatter
 */
class FieldFormatterFromViewDisplayUITest extends WebTestBase {

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_formatter_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests a field_formatter from view display.
   */
  public function testFieldFormatterFromViewDisplay() {
    // Add term.
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/add');
    $term_name = strtolower($this->randomMachineName());
    $field = strtolower($this->randomMachineName());
    $edit_term = [
      'name[0][value]' => $term_name,
      'field_test_field[0][value]' => $field,
    ];
    $this->drupalPostForm(NULL, $edit_term, t('Save'));
    $this->assertText("Created new term $term_name.", 'Created term.');

    // Add content.
    $this->drupalGet('node/add/test_content_type');
    $content_name = strtolower($this->randomMachineName());
    $edit_content = [
      'title[0][value]' => $content_name,
      'field_field_test_ref[0][target_id]' => $term_name,
    ];
    $this->drupalPostForm(NULL, $edit_content, t('Save'));
    $this->assertRaw('<div class="field__label">test_field</div>', 'Field is correctly displayed on node page.');
    $this->assertRaw('<div class="field__item">' . $field . '</div>', "Field's content was found.");
  }

  /**
   * Tests a field_formatter from view config form.
   */
  public function testFieldFormatterFromViewConfigForm() {
    $account = $this->drupalCreateUser(['administer node display']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/structure/types/manage/test_content_type/display');
    $this->drupalPostAjaxForm(NULL, [], 'field_field_test_ref_settings_edit');
    $this->assertFieldByName('fields[field_field_test_ref][settings_edit_form][settings][view_mode]', NULL, 'Field to select the view mode is available.');
    $this->assertRaw('<option value="default">Default</option>', 'Default view mode can be selected.');
    $this->assertRaw('<option value="taxonomy_term.test_view_mode">test_view_mode</option>', 'Created test mode can be selected.');
    $this->assertFieldByName('fields[field_field_test_ref][settings_edit_form][settings][field_name]', NULL, 'Field to select the field name is available.');
  }

}
