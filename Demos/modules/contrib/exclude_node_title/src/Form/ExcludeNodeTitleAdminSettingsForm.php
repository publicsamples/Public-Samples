<?php

/**
 * @file
 * Contains \Drupal\exclude_node_title\Form\ExcludeNodeTitleAdminSettingsForm.
 */

namespace Drupal\exclude_node_title\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\exclude_node_title\ExcludeNodeTitleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form object class for Exclude Node Title settings.
 */
class ExcludeNodeTitleAdminSettingsForm extends ConfigFormBase {

  /**
   * The Exclude Node Title module settings manager.
   *
   * @var \Drupal\exclude_node_title\ExcludeNodeTitleManagerInterface
   */
  protected $excludeNodeTitleManager;

  /**
   * Discovery and retrieval of entity type bundles manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity display repository
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExcludeNodeTitleManagerInterface $exclude_node_title_manager, EntityTypeBundleInfoInterface $entity_bundle_info, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($config_factory);

    $this->excludeNodeTitleManager = $exclude_node_title_manager;
    $this->bundleInfo = $entity_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('exclude_node_title.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exclude_node_title_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'exclude_node_title.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $enabled_link = Link::fromTextAndUrl(t('enabled'), Url::fromRoute('system.modules_list'));
    $form['#attached']['library'][] = 'system/drupal.system';

    $form['exclude_node_title_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove node title from search pages'),
      '#description' => $this->t('Select if you wish to remove title from search pages. You need to have Search module @link.', ['@link' => $enabled_link]),
      '#default_value' => $this->excludeNodeTitleManager->isSearchExcluded(),
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('search'),
    ];

    $form['content_type'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclude title by content types'),
      '#description' => $this->t('Define title excluding settings for each content type.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];

    foreach ($this->bundleInfo->getBundleInfo('node') as $node_type => $node_type_info) {
      $form['#attached']['drupalSettings']['exclude_node_title']['content_types'][$node_type] = $node_type_info['label'];
      $form['content_type'][$node_type]['content_type_value'] = [
        '#type' => 'select',
        '#title' => $node_type_info['label'],
        '#default_value' => $this->excludeNodeTitleManager->getBundleExcludeMode($node_type),
        '#options' => [
          'none' => $this->t('None'),
          'all' => $this->t('All nodes...'),
          'user' => $this->t('User defined nodes...'),
        ],
      ];

      $entity_view_modes = $this->entityDisplayRepository->getViewModes('node');
      $modes = [];
      foreach ($entity_view_modes as $view_mode_name => $view_mode_info) {
        $modes[$view_mode_name] = $view_mode_info['label'];
      }
      $modes += ['nodeform' => $this->t('Node form')];

      switch ($form['content_type'][$node_type]['content_type_value']['#default_value']) {
        case 'all':
          $title = $this->t('Exclude title from all nodes in the following view modes:');
          break;

        case 'user defined':
          $title = $this->t('Exclude title from user defined nodes in the following view modes:');
          break;

        default:
          $title = $this->t('Exclude from:');
      }

      $form['content_type'][$node_type]['content_type_modes'] = [
        '#type' => 'checkboxes',
        '#title' => $title,
        '#default_value' => $this->excludeNodeTitleManager->getExcludedViewModes($node_type),
        '#options' => $modes,
        '#states' => [
          // Hide the modes when the content type value is <none>.
          'invisible' => [
            'select[name="content_type[' . $node_type . '][content_type_value]"]' => [
              'value' => 'none',
            ],
          ],
        ],
      ];
    }

    $form['#attached']['library'][] = 'exclude_node_title/drupal.exclude_node_title.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('exclude_node_title.settings');
    $values = $form_state->getValues();
    foreach ($values['content_type'] as $node_type => $value) {
      $config->set('content_types.' . $node_type, $values['content_type'][$node_type]['content_type_value']);

      $modes = array_filter($values['content_type'][$node_type]['content_type_modes']);
      $modes = array_keys($modes);
      $config->set('content_type_modes.' . $node_type, $modes);
    }

    $config
      ->set('search', $values['exclude_node_title_search'])
      ->save();

    parent::submitForm($form, $form_state);

    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }
  }

}
