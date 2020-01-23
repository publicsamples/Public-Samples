<?php

namespace Drupal\Tests\field_formatter\Kernel\Plugin\Field\FieldFormatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\field_formatter\Plugin\Field\FieldFormatter\FieldLink
 * @group field_formatter
 */
class FieldLinkTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field_formatter', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::viewElements
   * @covers ::getFieldOutput
   * @covers ::getViewDisplay
   */
  public function testFieldLinkFormatter() {
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create([
      'name' => 'test name',
    ]);
    $entity->save();

    $build = $entity->name->view([
      'type' => 'field_link',
      'settings' => [
        'type' => 'string',
      ],
    ]);

    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->setRawContent($output);

    $href = (string) $this->cssSelect('a')[0]->attributes()['href'];
    $this->assertEquals($href, $entity->toUrl()->toString(TRUE)->getGeneratedUrl());
    $this->assertText('test name');
  }

}
