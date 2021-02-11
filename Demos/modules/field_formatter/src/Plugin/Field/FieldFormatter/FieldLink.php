<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;

/**
 * Provides the field link formatter.
 *
 * @FieldFormatter(
 *   id = "field_link",
 *   label = @Translation("Field linker"),
 *   field_types = {
 *   },
 * )
 */
class FieldLink extends FieldWrapperBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $field_output = $this->getFieldOutput($items, $langcode);

    $elements = [];
    foreach (Element::children($field_output) as $key) {
      $elements[$key] = [
        '#type' => 'link',
        '#url' => $items->getEntity()->toUrl(),
        '#title' => $field_output[$key],
      ];
    }
    return $elements;
  }

}
