<?php

namespace Drupal\ace_editor\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "ace_filter",
 *   title = @Translation("Ace Filter"),
 *   description = @Translation("Use &lt;ace&gt; and &lt;/ace&gt; tags to show it with syntax highlighting.
 * Add attributes to <ace> tag to control formatting."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *           "theme" = "cobalt",
 *           "syntax" = "html",
 *           "height" = "500px",
 *           "width" = "700px",
 *           "font_size" = "12pt",
 *           "line_numbers" = TRUE,
 *           "show_invisibles" = FALSE,
 *           "print_margins" = TRUE,
 *           "auto_complete" = TRUE,
 *           "use_wrap_mode" = TRUE,
 *     }
 * )
 */
class AceFilter extends FilterBase {

  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->settings;
    $config = \Drupal::config('ace_editor.settings');

    return [
      'theme' => [
        '#type' => 'select',
        '#title' => t('Theme'),
        '#options' => $config->get('theme_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['theme'],
      ],
      'syntax' => [
        '#type' => 'select',
        '#title' => t('Syntax'),
        '#description' => t('The syntax that will be highlighted.'),
        '#options' => $config->get('syntax_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['syntax'],
      ],
      'height' => [
        '#type' => 'textfield',
        '#title' => t('Height'),
        '#description' => t('The height of the editor in either pixels or percents.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['height'],
      ],
      'width' => [
        '#type' => 'textfield',
        '#title' => t('Width'),
        '#description' => t('The width of the editor in either pixels or percents.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['width'],
      ],
      'font_size' => [
        '#type' => 'textfield',
        '#title' => t('Font size'),
        '#description' => t('The the font size of the editor.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['font_size'],
      ],
      'line_numbers' => [
        '#type' => 'checkbox',
        '#title' => t('Show line numbers'),
        '#default_value' => $settings['line_numbers'],
      ],
      'print_margins' => [
        '#type' => 'checkbox',
        '#title' => t('Show print margin (80 chars)'),
        '#default_value' => $settings['print_margins'],
      ],
      'show_invisibles' => [
        '#type' => 'checkbox',
        '#title' => t('Show invisible characters (whitespaces, EOL...)'),
        '#default_value' => $settings['show_invisibles'],
      ],
      'use_wrap_mode' => [
        '#type' => 'checkbox',
        '#title' => t('Toggle word wrapping'),
        '#default_value' => $settings['use_wrap_mode'],
      ],
    ];
  }

  /**
   *
   */
  public function process($text, $langcode) {

    $text = html_entity_decode($text);

    if (preg_match_all("/<ace.*?>(.*?)\s*<\/ace>/s", $text, $match)) {
      $js_settings = [
        'instances' => [],
        'theme_settings' => $this->getConfiguration()['settings'],
      ];

      foreach ($match[0] as $key => $value) {
        $element_id = 'ace-editor-inline' . $key;
        $content = trim($match[1][$key], "\n\r\0\x0B");
        $replace = '<pre id="' . $element_id . '"></pre>';
        // Override settings with attributes on the tag.
        $settings = $this->getConfiguration()['settings'];
        $attach_lib = [];

        foreach ($this->tag_attributes('ace', $value) as $attribute_key => $attribute_value) {
          $settings[$attribute_key] = $attribute_value;

          if ($attribute_key == "theme" && \Drupal::service('library.discovery')->getLibraryByName('ace_editor', 'theme.' . $attribute_value)) {
            $attach_lib[] = "ace_editor/theme." . $attribute_value;
          }
          if ($attribute_key == "syntax" && \Drupal::service('library.discovery')->getLibraryByName('ace_editor', 'mode.' . $attribute_value)) {
            $attach_lib[] = "ace_editor/mode." . $attribute_value;
          }
        }

        $js_settings['instances'][] = [
          'id' => $element_id,
          'content' => $content,
          'settings' => $settings,
        ];
        $text = $this->str_replace_once($value, $replace, $text);
      }

      $result = new FilterProcessResult($text);
      $attach_lib[] = 'ace_editor/filter';
      $result->setAttachments(
        [
          'library' => $attach_lib,
          'drupalSettings' => [
            // Pass settings variable ace_formatter to javascript.
            'ace_filter' => $js_settings,
          ],
        ]
      );

      return $result;
    }

    $result = new FilterProcessResult($text);
    return $result;
  }

  /**
   * Get all attributes of an <ace> tag in key/value pairs.
   */
  public function tag_attributes($element_name, $xml) {
    // Grab the string of attributes inside the editor tag.
    $found = preg_match('#<' . $element_name . '\s+([^>]+(?:"|\'))\s?/?>#', $xml, $matches);

    if ($found == 1) {
      $attribute_array = [];
      $attribute_string = $matches[1];
      // Match attribute-name attribute-value pairs.
      $found = preg_match_all('#([^\s=]+)\s*=\s*(\'[^<\']*\'|"[^<"]*")#', $attribute_string, $matches, PREG_SET_ORDER);
      if ($found != 0) {
        // Create an associative array that matches attribute names
        // with their values.
        foreach ($matches as $attribute) {
          $value = substr($attribute[2], 1, -1);
          if ($value == "1" || $value == "0" || $value == "true" || $value == "false") {
            $value = intval($value);
          }
          $attribute_array[str_replace('-', '_', $attribute[1])] = $value;
        }
        return $attribute_array;
      }
    }
    // Attributes either weren't found, or couldn't be extracted
    // by the regular expression.
    return FALSE;
  }

  /**
   * Custom function to replace the code only once.
   *
   * Probably not the most efficient way, but at least it works.
   */
  public function str_replace_once($needle, $replace, $haystack) {
    // Looks for the first occurence of $needle in $haystack
    // and replaces it with $replace.
    $pos = strpos($haystack, $needle);
    if ($pos === FALSE) {
      // Nothing found.
      return $haystack;
    }
    return substr_replace($haystack, $replace, $pos, strlen($needle));
  }
}
