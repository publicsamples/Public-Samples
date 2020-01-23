<?php

namespace Drupal\field_formatter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that field_formatter UI works correctly.
 *
 * @group field_formatter
 */
class FieldFormatterWithInlineSettingsUITest extends WebTestBase {

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
  public static $modules = [
    'field_formatter_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
      'administer node display',
      'administer node fields',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests a field formatter with inline settings.
   */
  public function testFieldFormatterWithInlineSettings() {
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
      'field_field_test_ref_inline[0][target_id]' => $term_name,
    ];
    $this->drupalPostForm(NULL, $edit_content, t('Save'));
    $this->assertRaw('<div class="field__label">test_field</div>', 'Field is correctly displayed on node page.');
    $this->assertRaw('<div class="field__item">' . $field . '</div>', "Field's content was found.");

    // Check that on display management all fields of the destination entity
    // are available (all bundles).
    $this->drupalGet('admin/structure/types/manage/test_content_type/display');
    // Open the formatter settings.
    $this->drupalPostAjaxForm(NULL, [], 'field_field_test_ref_inline_settings_edit');
    $this->assertFieldByName('fields[field_field_test_ref_inline][settings_edit_form][settings][field_name]', NULL, 'Destination fields dropdown element found.');
    $field_select_element = $this->xpath('//*[@name="fields[field_field_test_ref_inline][settings_edit_form][settings][field_name]"]');
    $field_select_id = $field_select_element[0]['id'];
    $this->assertOption($field_select_id, 'field_test_field', 'First target field is an available option.');
    $this->assertOption($field_select_id, 'field_test_field2', 'Second target field is an available option.');
    $this->assertFieldByName('fields[field_field_test_ref_inline][settings_edit_form][settings][label]', 'above', 'The "Label" dropdown element exists and is set to "Above".');

  }

}
