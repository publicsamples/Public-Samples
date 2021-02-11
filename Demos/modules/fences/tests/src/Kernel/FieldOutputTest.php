<?php

namespace Drupal\Tests\fences\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the field output under different configurations.
 *
 * @group fences
 */
class FieldOutputTest extends KernelTestBase {

  use StripWhitespaceTrait;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The test entity used for testing output.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * The entity display under test.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $entityViewDisplay;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'field_test',
    'fences',
  ];

  /**
   * Test cases for the field output test.
   */
  public function fieldTestCases() {
    return [
      'No field markup' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        'lorem ipsum',
      ],
      'Only a field tag' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items">lorem ipsum</article>',
      ],
      'Only a field and label tag' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'h3',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items"><h3 class="field__label">field_test</h3>lorem ipsum</article>',
      ],
      'Only a field and field item tag' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_item_tag' => 'h2',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items"><div class="field__label">field_test</div><h2 class="field__item">lorem ipsum</h2></article>',
      ],
      'Default field, no label' => [
        [
          'fences_field_tag' => '',
          'fences_field_classes' => '',
          'fences_field_item_tag' => '',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<div class="field field--name-field-test field--type-text field--label-hidden field__items"><div class="field__item">lorem ipsum</div></div>',
      ],
      'Default field, with label' => [
        [
          'fences_field_tag' => '',
          'fences_field_classes' => '',
          'fences_field_item_tag' => '',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<div class="field field--name-field-test field--type-text field--label-above field__items"><div class="field__label">field_test</div><div class="field__item">lorem ipsum</div></div>',
      ],
      'Classes and tags' => [
        [
          'fences_field_tag' => 'ul',
          'fences_field_classes' => 'item-list',
          'fences_field_item_tag' => 'li',
          'fences_field_item_classes' => 'item-list__item',
          'fences_label_tag' => 'li',
          'fences_label_classes' => 'item-list__label',
        ],
        TRUE,
        '<ul class="item-list field field--name-field-test field--type-text field--label-above field__items"><li class="item-list__label field__label">field_test</li><li class="item-list__item field__item">lorem ipsum</li></ul>',
      ],
    ];
  }

  /**
   * Test the field output.
   *
   * @dataProvider fieldTestCases
   */
  public function testFieldOutput($settings, $label_visible, $field_markup) {
    // The entity display must be updated because the view method on fields
    // doesn't support passing third party settings.
    $this->entityViewDisplay->setComponent($this->fieldName, [
      'label' => $label_visible ? 'above' : 'hidden',
      'settings' => [],
      'type' => 'text_default',
      'third_party_settings' => [
        'fences' => $settings,
      ],
    ])->setStatus(TRUE)->save();
    $field_output = $this->entity->{$this->fieldName}->view('default');
    $rendered_field_output = $this->stripWhitespace($this->container->get('renderer')
      ->renderRoot($field_output));
    $this->assertEquals($this->stripWhitespace($field_markup), $rendered_field_output);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema($this->entityTypeId);
    $this->installEntitySchema('filter_format');

    // Setup a field and an entity display.
    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->entityTypeId,
    ])->save();

    $this->entityViewDisplay = EntityViewDisplay::load('entity_test.entity_test.default');

    // Create a test entity with a test value.
    $this->entity = EntityTest::create();
    $this->entity->{$this->fieldName}->value = 'lorem ipsum';
    $this->entity->save();

    // Set the default filter format.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ])->save();
    $this->container->get('config.factory')
      ->getEditable('filter.settings')
      ->set('fallback_format', 'test_format')
      ->save();
  }

}
