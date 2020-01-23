<?php

namespace Drupal\static_generator\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The form for setting entity type bundles for static generation.
 *
 * @internal
 */
class StaticGeneratorConfigureEntityTypesForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type definition object.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'static_generator_type_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {

    try {
      $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    } catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    $bundles_string = $this->configFactory->get('static_generator.settings')
      ->get('gen_' . $this->entityType->id());
    $gen_bundles = explode(',', $bundles_string);

    $options = $defaults = [];
    foreach ($this->bundleInfo->getBundleInfo($this->entityType->id()) as $bundle_id => $bundle) {
      $options[$bundle_id] = [
        'type' => $bundle['label'],
      ];
      $gen_bundle = in_array($bundle_id, $gen_bundles);
      $defaults[$bundle_id] = $gen_bundle;
    }

    if (!empty($options)) {
      $bundles_header = $this->t('All @entity_type types', ['@entity_type' => $this->entityType->getLabel()]);
      if ($bundle_entity_type_id = $this->entityType->getBundleEntityType()) {
        $bundles_header = $this->t('All @entity_type_plural_label', [
          '@entity_type_plural_label' => $this->entityTypeManager->getDefinition($bundle_entity_type_id)
            ->getPluralLabel(),
        ]);
      }
      $form['bundles'] = [
        '#type' => 'tableselect',
        '#header' => [
          'type' => $bundles_header,
        ],
        '#options' => $options,
        '#default_value' => $defaults,
        '#attributes' => ['class' => ['no-highlight']],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'ajaxcallback'],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => [$this, 'ajaxcallback'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles_generate = [];
    foreach ($form_state->getValue('bundles') as $bundle_id => $checked) {
      if ($checked) {
        $bundles_generate[] = $bundle_id;
      }
    }
    $this->configFactory->getEditable('static_generator.settings')
      ->set('gen_' . $this->entityType->id(), implode(',', $bundles_generate))
      ->save();
  }

  /**
   * Ajax callback to close the modal and update the selected text.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  public function ajaxCallback() {
    $selected_bundles = [];
    foreach ($this->bundleInfo->getBundleInfo($this->entityType->id()) as $bundle_id => $bundle) {
      //if ($this->workflow->getTypePlugin()->appliesToEntityTypeAndBundle($this->entityType->id(), $bundle_id)) {
      $selected_bundles[$bundle_id] = $bundle['label'];
      //}
    }
    $selected_bundles_list = [
      '#theme' => 'item_list',
      '#items' => $selected_bundles,
      '#context' => ['list_style' => 'comma-list'],
      '#empty' => $this->t('none'),
    ];
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    $response->addCommand(new HtmlCommand('#selected-' . $this->entityType->id(), $selected_bundles_list));
    return $response;
  }

  /**
   * Route title callback.
   *
   * @param $entity_type_id
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTitle($entity_type_id) {
    $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);

    $title = $this->t('Select the @entity_type types for static generation.', ['@entity_type' => $this->entityType->getLabel()]);
    if ($bundle_entity_type_id = $this->entityType->getBundleEntityType()) {
      $title = $this->t('Select the @entity_type_plural_label for for static generation', [
        '@entity_type_plural_label' => $this->entityTypeManager->getDefinition($bundle_entity_type_id)
          ->getPluralLabel(),
      ]);
    }

    return $title;
  }

}
