<?php
/**
 * @file
 * Lists available colors and color schemes for the AT Starterkit. By default
 * the color scheme relies almost entirely on color shift, where the user
 * changes the base color and all other unspecified colors shift relative to
 * the new base.
 */
use Drupal\at_core\Ext\ExtGet;
$theme_name = \Drupal::service('theme.manager')->getActiveTheme()->getName();
$file_path = drupal_get_path('theme', $theme_name) . '/styles/uikit/components/partials/_variables.scss';
$sass_variables = (new ExtGet)->getSassVariables($file_path);

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'base' => t('Base'),
    'text' => t('Text color'),
    'link' => t('Link color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Carbon Neutral'),
      'colors' => [
        'base' => $sass_variables['base-color'],
        'text' => $sass_variables['text-color'],
        'link' => $sass_variables['link-color'],
      ],
    ],
    'goodearth' => [
      'title' => t('The Good Earth'),
      'colors' => [
        'base' => '#4ba943',
        'text' => '#555555',
        'link' => '#8dd087',
      ],
    ],
    'tippingpoint' => [
      'title' => t('Tipping Point'),
      'colors' => [
        'base' => '#e65200',
        'text' => '#1b1918',
        'link' => '#ff792f',
      ],
    ],
    'palebluedot' => [
      'title' => t('Pale Blue Dot'),
      'colors' => [
        'base' => '#089de3',
        'text' => '#777777',
        'link' => '#089de3',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'styles/css/components/color.css',
    'styles/css/components/tabs.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
    'logo.png',
  ],

  // Gradient definitions.
  'gradients' => [],
];
