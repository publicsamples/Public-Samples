<?php

namespace Drupal\pagerer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\pagerer\PagererFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main Pagerer settings admin form.
 */
class PagererConfigForm extends ConfigFormBase {

  /**
   * The list of pagerer presets.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface
   */
  protected $presetsList;

  /**
   * The Pagerer factory.
   *
   * @var \Drupal\pagerer\PagererFactory
   */
  protected $pagererFactory;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * Constructs a PagererConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityListBuilderInterface $presets_list
   *   The list of Pagerer presets.
   * @param \Drupal\pagerer\PagererFactory $pagerer_factory
   *   The Pagerer factory.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager
   *   The element info manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityListBuilderInterface $presets_list, PagererFactory $pagerer_factory, ElementInfoManagerInterface $element_info_manager) {
    parent::__construct($config_factory);
    $this->presetsList = $presets_list;
    $this->pagererFactory = $pagerer_factory;
    $this->elementInfoManager = $element_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getListBuilder('pagerer_preset'),
      $container->get('pagerer.factory'),
      $container->get('plugin.manager.element_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagerer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pagerer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Add admin UI library.
    $form['#attached']['library'][] = 'pagerer/admin.ui';

    // Prepare fake pager for previews.
    $this->pagererFactory->get(5)->init(47884, 50);

    // Presets table.
    $form['presets'] = $this->presetsList->render();

    // Container for global options.
    $form['pagerer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("General"),
    ];
    // Global option for pager override.
    $default_label = (string) $this->t('Default:');
    $replace_label = (string) $this->t('Replace with:');
    $options = [
      $default_label => ['core' => $this->t('No - use Drupal core pager')],
      $replace_label => $this->presetsList->listOptions(),
    ];
    $form['pagerer']['core_override_preset'] = [
      '#type' => 'select',
      '#title' => $this->t("Replace standard pager"),
      '#description' => $this->t("Core pager theme requests can be overridden. Select whether they need to be fulfilled by Drupal core pager, or the Pagerer pager to use."),
      '#options' => $options,
      '#default_value' => $this->config('pagerer.settings')->get('core_override_preset'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set pager override if it has changed.
    $pager_override = $form_state->getValue('core_override_preset');
    if ($this->config('pagerer.settings')->get('core_override_preset') !== $pager_override) {
      $this->config('pagerer.settings')->set('core_override_preset', $pager_override)->save();
      $this->elementInfoManager->clearCachedDefinitions();
    }
    parent::submitForm($form, $form_state);
  }

}
