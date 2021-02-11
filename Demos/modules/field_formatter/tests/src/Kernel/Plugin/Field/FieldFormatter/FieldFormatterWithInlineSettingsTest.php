<?php

namespace Drupal\Tests\field_formatter\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\field_formatter\Plugin\Field\FieldFormatter\FieldFormatterWithInlineSettings
 * @group field_formatter
 */
class FieldFormatterWithInlineSettingsTest extends KernelTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'user',
    'field',
    'field_formatter',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $admin_role = Role::create([
      'id' => 'admin',
      'permissions' => ['view test entity'],
    ]);
    $admin_role->save();

    $this->adminUser = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$admin_role->id()],
    ]);
    $this->adminUser->save();
    \Drupal::currentUser()->setAccount($this->adminUser);
  }

  /**
   * Tests field_formatter_with_inline_settings formatter output.
   *
   * @param string|null $label_option
   *   The value for the "label" formatter option.
   * @param string $expected_output
   *   The expected output.
   *
   * @covers ::viewElements
   * @covers ::getAvailableFieldNames
   * @covers ::getViewDisplay
   *
   * @dataProvider providerTestRender
   */
  public function testRender($label_option, $expected_output) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_er_field',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_name' => 'test_er_field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field_config->save();

    $parent_entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'content' => [],
    ]);
    $parent_entity_view_display->setComponent('test_er_field', [
      'type' => 'field_formatter_with_inline_settings',
      'settings' => [
        'field_name' => 'name',
        'type' => 'string',
        'settings' => [],
        'label' => $label_option,
      ],
    ]);
    $parent_entity_view_display->save();

    $child_entity = EntityTest::create([
      'name' => ['child name'],
    ]);
    $child_entity->save();

    $entity = EntityTest::create([
      'test_er_field' => [[
        'target_id' => $child_entity->id(),
      ],
      ],
    ]);
    $entity->save();

    $build = $parent_entity_view_display->build($entity);

    \Drupal::service('renderer')->renderRoot($build);

    $this->assertEquals($expected_output, $build['test_er_field']['#markup']);
  }

  /**
   * Data provider for ::testRender().
   */
  public function providerTestRender() {
    $output_with_label = <<<EXPECTED

  <div>
    <div>test_er_field</div>
              <div>
  <div>
    <div>Name</div>
              <div>child name</div>
          </div>
</div>
          </div>

EXPECTED;
    $output_without_label = <<<EXPECTED

  <div>
    <div>test_er_field</div>
              <div>
            <div>child name</div>
      </div>
          </div>

EXPECTED;

    return [
      ['above', $output_with_label],
      ['inline', $output_with_label],
      ['hidden', $output_without_label],
      ['visually_hidden', $output_with_label],
    ];
  }

}
