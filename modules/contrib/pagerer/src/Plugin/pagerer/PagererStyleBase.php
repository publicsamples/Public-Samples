<?php

namespace Drupal\pagerer\Plugin\pagerer;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Template\Attribute;
use Drupal\pagerer\Entity\PagererPreset;
use Drupal\pagerer\Pagerer;
use Drupal\pagerer\Plugin\PagererStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin for Pagerer.
 */
abstract class PagererStyleBase extends PluginBase implements PagererStyleInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The Pagerer pager object.
   *
   * @var \Drupal\pagerer\Pagerer
   */
  protected $pager;

  /**
   * Query parameters as requested by the theme call.
   *
   * @var array
   */
  protected $parameters;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config type plugins manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * The PagererPreset object being configured.
   *
   * @var \Drupal\pagerer\Entity\PagererPreset
   */
  protected $pagererPreset;

  /**
   * The PagererPreset pane being configured.
   *
   * @var string
   */
  protected $pagererPresetPane;

  /**
   * Constructs a \Drupal\pagerer\Plugin\pagerer\PagererStyleBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config_manager
   *   The config type plugins manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TypedConfigManager $typed_config_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->typedConfigManager = $typed_config_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.typed'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setPager(Pagerer $pager) {
    $this->pager = $pager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $config = [];
    $d = $this->configFactory->get('pagerer.style.' . $this->getPluginId())->get('default_config');

    // General variables.
    $display_options = [
      'pages'       => $this->t('Pages'),
      'items'       => $this->t('Items'),
      'item_ranges' => $this->t('Item ranges'),
    ];
    $config['display'] = [
      '#type' => 'select',
      '#title' => $this->t("Display"),
      '#options' => $display_options,
      '#default_value' => $this->configuration['display'],
      '#description' => $this->t("Select whether to display pages, items, or item ranges."),
      '#required' => TRUE,
    ];

    // Display configuration.
    $config['display_container'] = [
      '#type' => 'details',
      '#title' => $this->t("Main options"),
    ];
    if (isset($d['quantity'])) {
      $config['display_container']['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t("Quantity"),
        '#default_value' => $this->configuration['quantity'],
        '#description' => $this->t("The number of pages in the pager list."),
        '#required' => TRUE,
        '#size' => 3,
        '#maxlength' => 3,
        '#min' => 1,
      ];
    }
    if (isset($d['display_mode'])) {
      $options = [
        'normal' => $this->t('Text elements.'),
      ];
      $config['display_container']['display_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t("Pager mode"),
        '#options' => $options,
        '#default_value' => $this->configuration['display_mode'],
        '#description' => $this->t("Select how to render the pager."),
        '#required' => TRUE,
      ];
    }
    if (isset($d['prefix_display'])) {
      $config['display_container']['prefix_display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Prefix label"),
        '#default_value' => $this->configuration['prefix_display'],
        '#description' => $this->t("Display a text label (e.g. 'Page') before the pager. Configure the label in the 'Text strings' section below."),
      ];
    }
    if (isset($d['suffix_display'])) {
      $config['display_container']['suffix_display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Suffix label"),
        '#default_value' => $this->configuration['suffix_display'],
        '#description' => $this->t("Display a text label (e.g. 'of @total') after the pager. Configure the label in the 'Text strings' section below."),
      ];
    }
    if (isset($d['display_restriction'])) {
      $options = [
        2 => $this->t('Display pager if there are at least two pages of results (default).'),
        1 => $this->t('Display pager if there is at least one page of results.'),
        0 => $this->t('Display pager even if the result set is empty.'),
      ];
      $config['display_container']['display_restriction'] = [
        '#type' => 'radios',
        '#title' => $this->t("Restriction"),
        '#options' => $options,
        '#default_value' => $this->configuration['display_restriction'],
        '#required' => TRUE,
      ];
    }
    if (isset($d['progr_links'])) {
      $options = ['relative', 'absolute'];
      $options = array_combine($options, $options);
      $config['display_container']['progr_links'] = [
        '#type' => 'select',
        '#title' => $this->t("Outer pages"),
        '#options' => $options,
        '#default_value' => $this->configuration['progr_links'],
        '#description' => $this->t("Select how to render links to pages far from the current, as 'absolute' page numbers (or items/item ranges), or as 'relative' offsets from current (e.g. +10 +100 +1000)."),
        '#required' => TRUE,
      ];
    }

    // Links configuration.
    $config['links_container'] = [
      '#type' => 'details',
      '#title' => $this->t("Links"),
      '#description' => $this->t("Configure link elements like '« First', '‹ Previous', 'Next ›' and 'Last »'."),
    ];
    if (isset($d['first_link'])) {
      $options = ['never', 'not_on_first', 'always'];
      $options = array_combine($options, $options);
      $config['links_container']['first_link'] = [
        '#type' => 'select',
        '#title' => $this->t("First"),
        '#options' => $options,
        '#default_value' => $this->configuration['first_link'],
        '#description' => $this->t("Select when to render a link to the first page (e.g. '« First'). Options are 'never' (not displayed), 'not_on_first' (not displayed if current page is the first), 'always' (always displayed)."),
        '#required' => TRUE,
      ];
    }
    if (isset($d['previous_link'])) {
      $options = ['never', 'not_on_first', 'always'];
      $options = array_combine($options, $options);
      $config['links_container']['previous_link'] = [
        '#type' => 'select',
        '#title' => $this->t("Previous"),
        '#options' => $options,
        '#default_value' => $this->configuration['previous_link'],
        '#description' => $this->t("Select when to render a link to the previous page (e.g. '‹ Previous'). Options are 'never' (not displayed), 'not_on_first' (not displayed if current page is the first), 'always' (always displayed)."),
        '#required' => TRUE,
      ];
    }
    if (isset($d['next_link'])) {
      $options = ['never', 'not_on_last', 'always'];
      $options = array_combine($options, $options);
      $config['links_container']['next_link'] = [
        '#type' => 'select',
        '#title' => $this->t("Next"),
        '#options' => $options,
        '#default_value' => $this->configuration['next_link'],
        '#description' => $this->t("Select when to render a link to the next page (e.g. 'Next ›'). Options are 'never' (not displayed), 'not_on_last' (not displayed if current page is the last), 'always' (always displayed)."),
        '#required' => TRUE,
      ];
    }
    if (isset($d['last_link'])) {
      $options = ['never', 'not_on_last', 'always'];
      $options = array_combine($options, $options);
      $config['links_container']['last_link'] = [
        '#type' => 'select',
        '#title' => $this->t("Last"),
        '#options' => $options,
        '#default_value' => $this->configuration['last_link'],
        '#description' => $this->t("Select when to render a link to the last page (e.g. 'Last »'). Options are 'never' (not displayed), 'not_on_last' (not displayed if current page is the last), 'always' (always displayed)."),
        '#required' => TRUE,
      ];
    }

    // Separators configuration.
    $config['separators_container'] = [
      '#type' => 'details',
      '#title' => $this->t("Separators"),
      '#description' => $this->t("Configure separators."),
    ];
    if (isset($d['breaker_display'])) {
      $config['separators_container']['breaker_display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Page breaker"),
        '#default_value' => $this->configuration['breaker_display'],
        '#description' => $this->t("Display a breaker when the page sequence breaks."),
      ];
    }
    if (isset($d['tags']['page_breaker'])) {
      $config['separators_container']['page_breaker'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Breaker"),
        '#default_value' => $this->configuration['tags']['page_breaker'],
        '#description' => $this->t("Text to use as page breaker."),
        '#states' => [
          'visible' => [
            ':input[name="breaker_display"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    if (isset($d['separator_display'])) {
      $config['separators_container']['separator_display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Page separator"),
        '#default_value' => $this->configuration['separator_display'],
        '#description' => $this->t("Display a separator between the page links."),
      ];
    }
    if (isset($d['tags']['page_separator'])) {
      $config['separators_container']['page_separator'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Separator"),
        '#default_value' => $this->configuration['tags']['page_separator'],
        '#description' => $this->t("Text to use as page separator."),
        '#states' => [
          'visible' => [
            ':input[name="separator_display"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Tags configuration. For each display type, loops through the
    // default config to fetch the tags, and retrieves titles from
    // the config schema.
    $display_tags = $this->typedConfigManager->getDefinition('pagerer.tags_display_config.' . $this->getPluginId());
    $config['tags_container'] = [
      '#tree' => TRUE,
    ];
    foreach ($d['tags'] as $tags_key => $tags_set) {
      if (in_array($tags_key, ['pages', 'items', 'item_ranges'])) {
        $config['tags_container'][$tags_key] = [
          '#type' => 'details',
          '#title' => $display_options[$tags_key] . ' - ' . $this->t("Text strings"),
          '#description' => $this->t("Configure text strings."),
          '#states' => [
            'visible' => [
              ':input[name="display"]' => ['value' => $tags_key],
            ],
          ],
        ];
        foreach ($tags_set as $tag => $map) {
          $config['tags_container'][$tags_key][$tag] = [
            '#type' => 'textfield',
            '#title' => $display_tags['mapping'][$tag]['label'],
            '#default_value' => $this->configuration['tags'][$tags_key][$tag],
          ];
        }
      }
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $default_configuration = $this->configFactory->get('pagerer.style.' . $this->getPluginId())->get('default_config');
    $display_mode = $form_state->getValue('display');
    foreach ($default_configuration as $conf_key => $conf_item) {
      // Root level configuration.
      if ($form_state->hasValue($conf_key)) {
        $this->pagererPreset->setPaneData($this->pagererPresetPane, "config.$conf_key", $form_state->getValue($conf_key));
      }
      elseif ($conf_key == 'tags') {
        foreach ($conf_item as $tag_key => $tag_value) {
          // Tag options.
          if ($form_state->hasValue($tag_key)) {
            // Display independent.
            $this->pagererPreset->setPaneData($this->pagererPresetPane, "config.tags.$tag_key", $form_state->getValue($tag_key));
          }
          elseif (in_array($tag_key, ['pages', 'items', 'item_ranges'])) {
            // Display dependent block. Saves all the tags for the current
            // display, and only those that changed from default in other
            // displays. This to allow full translation of the current display
            // text elements.
            foreach ($tag_value as $tag_display_key => $tag_display_value) {
              $tag_display_value = $form_state->getValue([
                'tags_container', $tag_key, $tag_display_key,
              ]);
              if ($tag_key == $display_mode || $tag_display_value != $default_configuration['tags'][$tag_key][$tag_display_key]) {
                $this->pagererPreset->setPaneData($this->pagererPresetPane, "config.tags.$tag_key.$tag_display_key", $tag_display_value);
              }
              else {
                $this->pagererPreset->unsetPaneData($this->pagererPresetPane, "config.tags.$tag_key.$tag_display_key");
              }
            }
          }
        }
      }
    }
  }

  /**
   * Sets the current PagererPreset and pane being configured.
   *
   * @param \Drupal\pagerer\Entity\PagererPreset $pagerer_preset
   *   The PagererPreset.
   * @param string $pagerer_preset_pane
   *   The PagererPreset pane.
   */
  public function setConfigurationContext(PagererPreset $pagerer_preset, $pagerer_preset_pane) {
    $this->pagererPreset = $pagerer_preset;
    $this->pagererPresetPane = $pagerer_preset_pane;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables) {
    // Save theme requested query parameters.
    $this->parameters = $variables['pager']['#parameters'];

    // Check if pager is needed; if not, return immediately.
    if ($this->pager->getTotalPages() < $this->getOption('display_restriction')) {
      return;
    }

    if ($this->pager->getTotalPages() == 0) {
      // Manage empty pageset.
      $items['pages'] = $this->buildEmptyPager();
    }
    else {
      // Compose pager.
      $items = [];
      // 1 - First + previous links.
      if ($this->getOption('first_link') == 'always' or ($this->getOption('first_link') == 'not_on_first' and $this->pager->getCurrentPage() <> 0)) {
        $items['first'] = $this->getNavigationItem('first');
      }
      if ($this->getOption('previous_link') == 'always' or ($this->getOption('previous_link') == 'not_on_first' and $this->pager->getCurrentPage() <> 0)) {
        $items['previous'] = $this->getNavigationItem('previous');
      }
      // 2 - Prefix.
      if ($this->getOption('prefix_display')) {
        $items['prefix'] = [
          'text' => $this->getDisplayTag('prefix_label'),
          'attributes' => new Attribute(),
        ];
      }
      // 3 - Pager.
      $items['pages'] = $this->buildPagerItems();

      // 4 - Suffix.
      if ($this->getOption('suffix_display')) {
        $items['suffix'] = [
          'text' => $this->getDisplayTag('suffix_label'),
          'attributes' => new Attribute(),
        ];
      }
      // 5 - Next + last links.
      if ($this->getOption('next_link') == 'always' or ($this->getOption('next_link') == 'not_on_last' and $this->pager->getCurrentPage() <> $this->pager->getLastPage())) {
        $items['next'] = $this->getNavigationItem('next');
      }
      if ($this->getOption('last_link') == 'always' or ($this->getOption('last_link') == 'not_on_last' and $this->pager->getCurrentPage() <> $this->pager->getLastPage())) {
        $items['last'] = $this->getNavigationItem('last');
      }
    }

    // Pager items list.
    $variables['items'] = $items;
  }

  /**
   * Render a 'no pages to display' text.
   */
  protected function buildEmptyPager() {
    return [
      [
        'text' => $this->getDisplayTag('pageset_empty'),
        'attributes' => new Attribute(),
      ],
    ];
  }

  /**
   * Returns a configuration element.
   *
   * @param string $key
   *   The configuration element to return. Dots (.) will be interpreted
   *   as a nesting in the configuration array structure.
   *
   * @return mixed
   *   The configuration element, or NULL if non existing.
   */
  protected function getOption($key) {
    $keys = explode('.', $key);
    $n = $this->configuration;
    foreach ($keys as $k) {
      if (!empty($n[$k])) {
        if (is_array($n[$k])) {
          $n = $n[$k];
        }
        else {
          return $n[$k];
        }
      }
      else {
        return NULL;
      }
    }
  }

  /**
   * Returns a translated textual element from the configuration.
   *
   * @param string $key
   *   The tag key.
   *
   * @return string
   *   A text tag string.
   */
  protected function getTag($key) {
    return $this->getOption('tags.' . $key);
  }

  /**
   * Returns a translated textual element for pages/items/item ranges.
   *
   * Depending on the 'display' option, gets a translated text element
   * and formats it to replace placeholders.
   *
   * @param string $key
   *   The tag key.
   * @param int $offset
   *   (Optional) The offset from current page. Defaults to 0.
   *
   * @return string
   *   A text tag string.
   */
  protected function getDisplayTag($key, $offset = 0) {
    // Get the translated tag, with placeholders.
    $tag = $this->getTag($this->getOption('display') . '.' . $key);

    // Items.
    $l_item = ($this->pager->getCurrentPage() + $offset) * $this->pager->getLimit() + 1;
    $h_item = min(($this->pager->getCurrentPage() + $offset + 1) * $this->pager->getLimit(), $this->pager->getTotalItems());
    $item_offset = abs($offset * $this->pager->getLimit());

    // Pages.
    $number = $this->pager->getCurrentPage() + $offset + 1;
    $t_offset = abs($offset);

    // Return the formatted tag.
    return new FormattableMarkup(
      $tag,
      [
        '@number' => $number,
        '@offset' => $t_offset,
        '@total' => $this->pager->getTotalPages(),
        '@item_low' => $l_item,
        '@item_high' => $h_item,
        '@item' => $l_item,
        '@item_offset' => $item_offset,
        '@total_items' => $this->pager->getTotalItems(),
      ]
    );
  }

  /**
   * Gets a 'page' item in the pager.
   *
   * Value returned is dependent on what's being displayed in the pager via the
   * 'display' option, the offset mode selected {absolute|relative}, and
   * whether the page is a progressive one.
   *
   * @param int $offset
   *   Offset of page to be rendered, from current page.
   * @param string $offset_mode
   *   Possible values:
   *   - 'absolute' returns the page/item/item range at offset.
   *   - 'relative' returns the offset (pages/items) from current.
   * @param bool $progr_page
   *   TRUE indicates a page outside of neighborhood.
   * @param string $title_tag
   *   Title tag [page|first|previous|next|last].
   * @param bool $set_query
   *   TRUE indicates to build the query parameters for the link.
   *
   * @return array
   *   render array of the page item.
   */
  protected function getPageItem($offset, $offset_mode = 'absolute', $progr_page = FALSE, $title_tag = 'page', $set_query = TRUE) {
    // Get relevant page tag.
    if ($offset == 0) {
      $page_tag_key = 'page_current';
    }
    else {
      if ($progr_page && $offset_mode == 'relative') {
        $page_tag_key = ($offset < 0) ? 'page_previous_relative' : 'page_next_relative';
      }
      else {
        $page_tag_key = ($offset < 0) ? 'page_previous' : 'page_next';
      }
    }

    // Return if requested not to display current page.
    if ($offset == 0 && $this->getOption('display_mode') == 'none') {
      return [];
    }

    // Link data.
    // - 'text' holds the the formatted page text.
    // - 'title' holds the the formatted HTML title, used by the browser to
    //   display microhelp text.
    // - 'reader_text' holds the the text used by automated readers.
    // - 'href' holds the HTTP URL link to the destination page.
    // - 'is_current' indicates if the page displayed in the pager is the
    //   current page.
    $ret = [
      'text' => $this->getDisplayTag($page_tag_key, $offset),
      'href' => $this->pager->getHref($this->parameters, $this->pager->getCurrentPage() + $offset, NULL, $set_query),
      'title' => $this->getDisplayTag($title_tag . '_title', $offset),
      'reader_text' => $this->getDisplayTag($title_tag . '_reader', $offset),
      'attributes' => new Attribute(),
    ];
    if ($offset == 0) {
      $ret['is_current'] = TRUE;
    }
    return $ret;
  }

  /**
   * Gets a link/button item to first/previous/next/last link.
   *
   * @param string $scope
   *   Target page [first|previous|next|last].
   * @param bool $href
   *   Whether the item should contain the pager link.
   *
   * @return array
   *   Render array.
   */
  protected function getNavigationItem($scope, $href = TRUE) {
    // Determine the offset to current page and whether the link is
    // active or not.
    switch ($scope) {
      case 'first':
        $offset = -$this->pager->getCurrentPage();
        break;

      case 'previous':
        $offset = ($this->pager->getCurrentPage() == 0) ? 0 : -1;
        break;

      case 'next':
        $offset = ($this->pager->getCurrentPage() == $this->pager->getLastPage()) ? 0 : 1;
        break;

      case 'last':
        $offset = $this->pager->getLastPage() - $this->pager->getCurrentPage();
        break;

    }

    // Format the page text and HTML title, used by the browser to display
    // microhelp text.
    $ret = [
      'text' => $this->getDisplayTag($scope, $offset),
      'title' => $this->getDisplayTag($scope . '_title', $offset),
      'reader_text' => $this->getDisplayTag($scope . '_reader', $offset),
      'attributes' => new Attribute(),
    ];

    // Get the destination URL link if neeeded.
    if ($href) {
      $ret['href'] = $this->pager->getHref($this->parameters, $this->pager->getCurrentPage() + $offset);
    }

    return $ret;
  }

  /**
   * Prepares input parameters for a JS enabled pager widget.
   *
   * This method stores the input state data for a Pagerer widget in the
   * drupalSettings.
   *
   * @param array $state_settings
   *   The array of state settings to be enriched and stored.
   *
   * @return string
   *   the pagerer widget id to refer to in the drupalSettings.pagerer.state
   *   entry.
   */
  protected function prepareJsState(array &$state_settings) {
    // Prepare query parameters.
    // In the 'page' querystring fragment, the current page is overridden
    // with a text that the js widget will then replace with the content of HTML
    // 'value' attribute.
    $query = $this->pager->getQueryParameters($this->parameters, 'pagererpage');

    // Prepare the query string.
    $query_string = UrlHelper::buildQuery($query);

    // Are we displaying pages or items; 'value' HTML attribute will bear
    // the current $current value.
    if ($this->getOption('display') == 'pages') {
      $current = $this->pager->getCurrentPage() + 1;
      $interval = 1;
    }
    else {
      $current = $this->pager->getLimit() * $this->pager->getCurrentPage() + 1;
      $interval = $this->pager->getLimit();
    }

    // Prepare js widget state.
    $default_settings = [
      'url'            => $this->pager->getHref([], NULL, NULL, FALSE)->toString(),
      'queryString'    => $query_string,
      'element'        => $this->pager->getElement(),
      'total'          => $this->pager->getTotalPages(),
      'totalItems'     => $this->pager->getTotalItems(),
      'current'        => $this->pager->getCurrentPage(),
      'interval'       => $interval,
      'display'        => $this->getOption('display'),
      'value'          => $current,
      'pageSeparator'  => $this->getOption('separator_display') ? $this->getTag('page_separator') : 'none',
      'pageTag'        => [
        'page_current'  => $this->getTag($this->getOption('display') . '.page_current'),
        'page_previous' => $this->getTag($this->getOption('display') . '.page_previous'),
        'page_next'     => $this->getTag($this->getOption('display') . '.page_next'),
      ],
    ];
    $state_settings = NestedArray::mergeDeep($default_settings, $state_settings);

    $random_generator = new Random();
    $pagerer_widget_id = 'pagerer-widget-' . $random_generator->name(8, TRUE);

    return $pagerer_widget_id;
  }

}
