<?php

namespace Drupal\pagerer_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for Pagerer example.
 */
class PagererExampleController extends ControllerBase {

  /**
   * Get pagerer example page title.
   *
   * @return string
   *   The page title.
   */
  public function examplePageTitle() {
    // Set the page title to show current Pagerer version.
    $module_info = system_get_info('module', 'pagerer');
    return $this->t("Pagerer @version - example page", ['@version' => $module_info['version']]);
  }

  /**
   * Build the pagerer example page.
   *
   * @return array
   *   A render array.
   */
  public function examplePage() {

    // First data table - associated to pager element 0.
    $header_0 = [
      ['data' => 'wid'],
      ['data' => 'type'],
      ['data' => 'timestamp'],
    ];
    $query_0 = \Drupal::database()->select('watchdog', 'd')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element(0);
    $result_0 = $query_0
      ->fields('d', ['wid', 'type', 'timestamp'])
      ->limit(5)
      ->orderBy('d.wid')
      ->execute();
    $rows_0 = [];
    foreach ($result_0 as $row) {
      $rows_0[] = ['data' => (array) $row];
    }

    // Second data table - associated to pager element 1.
    $header_1 = [
      ['data' => 'collection'],
      ['data' => 'name'],
    ];
    $query_1 = \Drupal::database()->select('key_value', 'd')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element(1);
    $result_1 = $query_1
      ->fields('d', ['collection', 'name'])
      ->limit(10)
      ->orderBy('d.collection')
      ->orderBy('d.name')
      ->execute();
    $rows_1 = [];
    foreach ($result_1 as $row) {
      $rows_1[] = ['data' => (array) $row];
    }

    // Third data table - associated to pager element 2.
    $header_2 = [
      ['data' => 'name'],
      ['data' => 'path'],
    ];
    $query_2 = \Drupal::database()->select('router', 'd')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element(2);
    $result_2 = $query_2
      ->fields('d', ['name', 'path'])
      ->limit(5)
      ->orderBy('d.name')
      ->execute();
    $rows_2 = [];
    foreach ($result_2 as $row) {
      $rows_2[] = ['data' => (array) $row];
    }

    // Create a render array ($build) which will be themed for output.
    $build = [];

    // Some description.
    $build['initdesc'] = ['#markup' => $this->t("This page is an example of pagerer's features. It runs three separate queries on the database, and renders three tables with the results. A distinct pager is associated to each of the tables, and each pager is rendered through various pagerer's styles.") . '<p/><hr/>'];

    // First table.
    $build['l_pager_table_0'] = ['#markup' => '<br/><br/><h2><b>' . $this->t("First data table:") . '</b></h2>'];
    $build['pager_table_0'] = [
      '#theme' => 'table',
      '#header' => $header_0,
      '#rows' => $rows_0,
      '#empty' => $this->t("There are no watchdog records found in the db"),
    ];

    // Attach the pager themes.
    $build['l_pager_pager_0'] = ['#markup' => '<b>' . $this->t("Drupal standard 'pager' theme:") . '</b>'];
    $build['pager_pager_0'] = [
      '#type' => 'pager',
      '#theme' => 'pager',
      '#element' => 0,
    ];
    $build['l_pagerer_standard_0'] = ['#markup' => '<br/>' . $this->t("<b>'Standard' pagerer style (mimick of Drupal's standard)</b> in three 'display' modes: 'pages', 'items', and 'item_ranges'")];
    $build['pagerer_standard_pages_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'standard',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['pagerer_standard_items_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'standard',
      '#config' => [
        'display_restriction' => 0,
        'display' => 'items',
      ],
    ];
    $build['pagerer_standard_item_ranges_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'standard',
      '#config' => [
        'display_restriction' => 0,
        'display' => 'item_ranges',
      ],
    ];
    $build['l_pagerer_progressive_0'] = ['#markup' => '<br/><b>' . $this->t("'Progressive' pagerer style:") . '</b>'];
    $build['pagerer_progressive_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'progressive',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_adaptive_0'] = ['#markup' => '<br/><b>' . $this->t("'Adaptive' pagerer style:") . '</b>'];
    $build['pagerer_adaptive_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'adaptive',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_mini_0'] = ['#markup' => '<br/><b>' . $this->t("'Mini' pagerer style:") . '</b>'];
    $build['pagerer_mini_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'mini',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_scrollpane_0'] = ['#markup' => '<br/><b>' . $this->t("'Scrollpane' pagerer style:") . '</b>'];
    $build['pagerer_scrollpane_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'scrollpane',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_slider_0'] = ['#markup' => '<br/><b>' . $this->t("'Slider' pagerer style:") . '</b>'];
    $build['pagerer_slider_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 0,
      '#style' => 'slider',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];

    $build['l_pagerer_pagerer_0'] = ['#markup' => '<br/><b>' . $this->t("'pagerer' core replacement theme:") . '</b>'];
    $build['pagerer_pagerer_0'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#element' => 0,
      '#config' => [
        'preset' => $this->config('pagerer.settings')->get('core_override_preset'),
      ],
    ];

    $build['end_table_0'] = [
      '#markup' => '<p/><hr/>',
    ];

    // Second table.
    $build['l_pager_table_1'] = ['#markup' => '<br/><br/><h2><b>' . $this->t("Second data table:") . '</b></h2>'];
    $build['pager_table_1'] = [
      '#theme' => 'table',
      '#header' => $header_1,
      '#rows' => $rows_1,
      '#empty' => $this->t("There are no date formats found in the db"),
    ];

    // Attach the pager themes.
    $build['l_pagerer_basic_1'] = ['#markup' => '<br/><b>' . $this->t("'Basic' pagerer style:") . '</b>'];
    $build['pagerer_basic_1'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 1,
      '#style' => 'basic',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_adaptive_1'] = ['#markup' => '<br/><b>' . $this->t("'Adaptive' pagerer style:") . '</b>'];
    $build['pagerer_adaptive_1'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 1,
      '#style' => 'adaptive',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_pagerer_1'] = ['#markup' => '<br/><b>' . $this->t("'pagerer' core replacement theme:") . '</b>'];
    $build['pagerer_pagerer_1'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#element' => 1,
      '#config' => [
        'preset' => $this->config('pagerer.settings')->get('core_override_preset'),
      ],
    ];
    $build['l_pagerer_pagerer_direct_1'] = ['#markup' => '<br/><b>' . $this->t("'pagerer' (direct call from module) theme:") . '</b> ' . $this->t("Note the usage of the 'tags' variables to customise labels and hover titles.")];
    $build['pagerer_pagerer_direct_1'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#element' => 1,
      '#config' => [
        'panes' => [
          'left' => [
            'style' => 'mini',
            'config' => [
              'tags'            => [
                'items'         => [
                  'first_title'    => $this->t("Go to the beginning of the recordset"),
                  'previous_title' => $this->t("Go to the previous range of records"),
                ],
              ],
              'display'         => 'items',
              'display_mode'    => 'none',
              'prefix_display'  => FALSE,
              'suffix_display'  => FALSE,
              'first_link'      => 'always',
              'previous_link'   => 'always',
              'next_link'       => 'never',
              'last_link'       => 'never',
            ],
          ],
          'center' => [
            'style' => 'mini',
            'config' => [
              'tags'            => [
                'items'         => [
                  'prefix_label'    => $this->t("Record"),
                  'widget_title'    => $this->t("Enter record and press Return. Up/Down arrow keys are enabled."),
                ],
              ],
              'display_restriction' => 0,
              'display'         => 'items',
              'display_mode'    => 'widget',
              'prefix_display'  => TRUE,
              'suffix_display'  => TRUE,
              'first_link'      => 'never',
              'previous_link'   => 'never',
              'next_link'       => 'never',
              'last_link'       => 'never',
            ],
          ],
          'right' => [
            'style' => 'mini',
            'config' => [
              'tags'            => [
                'items'         => [
                  'next_title'     => $this->t("Go to the next range of records"),
                  'last_title'     => $this->t("Go to the end of the recordset"),
                ],
              ],
              'display'         => 'items',
              'display_mode'    => 'none',
              'prefix_display'  => FALSE,
              'suffix_display'  => FALSE,
              'first_link'      => 'never',
              'previous_link'   => 'never',
              'next_link'       => 'always',
              'last_link'       => 'always',
            ],
          ],
        ],
      ],
    ];

    $build['end_table_1'] = [
      '#markup' => '<p/><hr/>',
    ];

    // Third table.
    $build['l_pager_table_2'] = ['#markup' => '<br/><br/><h2><b>' . $this->t("Third data table:") . '</b></h2>'];
    $build['pager_table_2'] = [
      '#theme' => 'table',
      '#header' => $header_2,
      '#rows' => $rows_2,
      '#empty' => $this->t("There are no routes found in the db"),
    ];

    // Attach the pager themes.
    $build['l_pagerer_adaptive_2'] = ['#markup' => '<br/><b>' . $this->t("'Adaptive' pagerer style:") . '</b>'];
    $build['pagerer_adaptive_2'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer_base',
      '#element' => 2,
      '#style' => 'adaptive',
      '#config' => [
        'display_restriction' => 0,
      ],
    ];
    $build['l_pagerer_pagerer_2'] = ['#markup' => '<br/><b>' . $this->t("'pagerer' core replacement theme:") . '</b>'];
    $build['pagerer_pagerer_2'] = [
      '#type' => 'pager',
      '#theme' => 'pagerer',
      '#element' => 2,
      '#config' => [
        'preset' => $this->config('pagerer.settings')->get('core_override_preset'),
      ],
    ];

    $build['end_table_2'] = [
      '#markup' => '<p/><hr/>',
    ];

    return $build;
  }

}
