<?php

namespace Drupal\static_generator\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;


/**
 * Class StaticSettingsForm.
 *
 * @package Drupal\static\Form
 *
 * @ingroup static
 */
class StaticGeneratorSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'static_generator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'static_generator.settings',
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Render method.
    $render_method = $form_state->getValue('render_method');
    $this->config('static_generator.settings')
      ->set('render_method', $render_method)
      ->save();

    // Generator directory.
    $generator_directory = $form_state->getValue('generator_directory');
    $this->config('static_generator.settings')
      ->set('generator_directory', $generator_directory)
      ->save();

    // CSS directory.
    $css_directory = $form_state->getValue('css_directory');
    $this->config('static_generator.settings')
      ->set('css_directory', $css_directory)
      ->save();

    // JS directory.
    $js_directory = $form_state->getValue('js_directory');
    $this->config('static_generator.settings')
      ->set('js_directory', $js_directory)
      ->save();

    // Paths - generate.
    $paths_generate = $form_state->getValue('paths_generate');
    $this->config('static_generator.settings')
      ->set('paths_generate', $paths_generate)
      ->save();

    // Paths - do not generate.
    $paths_do_not_generate = $form_state->getValue('paths_do_not_generate');
    $this->config('static_generator.settings')
      ->set('paths_do_not_generate', $paths_do_not_generate)
      ->save();

    // Blocks - ESI.
    //    $blocks_esi = $form_state->getValue('blocks_esi');
    //    $this->config('static_generator.settings')
    //      ->set('blocks_esi', $blocks_esi)
    //      ->save();

    // Blocks - No ESI.
    $blocks_no_esi = $form_state->getValue('blocks_no_esi');
    $this->config('static_generator.settings')
      ->set('blocks_no_esi', $blocks_no_esi)
      ->save();

    // Blocks - frequent.
    $blocks_frequent = $form_state->getValue('blocks_frequent');
    $this->config('static_generator.settings')
      ->set('blocks_frequent', $blocks_frequent)
      ->save();

    // Drupal
    $drupal = $form_state->getValue('drupal');
    $this->config('static_generator.settings')
      ->set('drupal', $drupal)
      ->save();

    // Non Drupal
    $non_drupal = $form_state->getValue('non_drupal');
    $this->config('static_generator.settings')
      ->set('non_drupal', $non_drupal)
      ->save();

    // Verbose Logging
    $verbose_logging = $form_state->getValue('verbose_logging');
    $this->config('static_generator.settings')
      ->set('verbose_logging', $verbose_logging)
      ->save();

    // Generate index.html
    $generate_index = $form_state->getValue('generate_index');
    $this->config('static_generator.settings')
      ->set('generate_index', $generate_index)
      ->save();

    // ESI Blocks
    $esi_blocks = $form_state->getValue('esi_blocks');
    $this->config('static_generator.settings')
      ->set('esi_blocks', $esi_blocks)
      ->save();

    // ESI sg-esi
    $esi_sg_esi = $form_state->getValue('esi_sg_esi');
    $this->config('static_generator.settings')
      ->set('esi_sg_esi', $esi_sg_esi)
      ->save();

    // Static URL
    $static_url = $form_state->getValue('static_url');
    $this->config('static_generator.settings')
      ->set('static_url', $static_url)
      ->save();

    // Guzzle Host
    $guzzle_host = $form_state->getValue('guzzle_host');
    $this->config('static_generator.settings')
      ->set('guzzle_host', $guzzle_host)
      ->save();

    // Guzzle Options
    $guzzle_options = $form_state->getValue('guzzle_options');
    $this->config('static_generator.settings')
      ->set('guzzle_options', $guzzle_options)
      ->save();

    // Rsync Code
    $rsync_code = $form_state->getValue('rsync_code');
    $this->config('static_generator.settings')
      ->set('rsync_code', $rsync_code)
      ->save();

    // Rsync Public
    $rsync_public = $form_state->getValue('rsync_public');
    $this->config('static_generator.settings')
      ->set('rsync_public', $rsync_public)
      ->save();

    // Public Files Exclude
    $rsync_public_exclude = $form_state->getValue('rsync_public_exclude');
    $this->config('static_generator.settings')
      ->set('rsync_public_exclude', $rsync_public_exclude)
      ->save();

    drupal_set_message($this->t('Your settings have been saved.'));

  }

  /**
   * Defines the settings form for the Static Generator module.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('static_generator.settings');

    $entityTypeManager = \Drupal::entityTypeManager();
    $entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');

    // Render method.
    $form['render_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Render method'),
      '#default_value' => $config->get('render_method'),
      '#options' => ['Core' => 'Core', 'Guzzle' => 'Guzzle'],
      '#description' => $this->t('The method used to render pages.'),
    ];

    // Verbose logging.
    $form['verbose_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose logging'),
      '#default_value' => $config->get('verbose_logging'),
    ];

    // Generate index.html.
    $form['generate_index'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate index.html'),
      '#default_value' => $config->get('generate_index'),
    ];

    // ESI blocks.
    $form['esi_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate ESI for blocks.'),
      '#default_value' => $config->get('esi_blocks'),
    ];

    // ESI sg_esi.
    $form['esi_sg_esi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate ESI for class="sg-esi--<id>".'),
      '#default_value' => $config->get('esi_sg_esi'),
    ];

    // Static URL.
    $form['static_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Static URL'),
      '#default_value' => $config->get('static_url'),
    ];

    // Guzzle Host.
    $form['guzzle_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Guzzle host'),
      '#default_value' => $config->get('guzzle_host'),
    ];

    // Guzzle Options.
    $form['guzzle_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Guzzle options'),
      '#default_value' => $config->get('guzzle_options'),
      '#description' => $this->t('Guzzle options, e.g. [\'verify\' => false] to turn off HTTPS checking.'),
    ];

    // Generator directory.
    $form['generator_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Generator directory'),
      '#default_value' => $config->get('generator_directory'),
      '#description' => $this->t('The static generator target directory.'),
    ];

    // CSS directory.
    $form['css_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS directory'),
      '#default_value' => $config->get('css_directory'),
      '#description' => $this->t('The CSS rsync target directory.'),
    ];

    // JS directory.
    $form['js_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JS directory'),
      '#default_value' => $config->get('js_directory'),
      '#description' => $this->t('The JS rsync target directory.'),
    ];

    // Paths to generate.
    $form['paths_generate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths to generate'),
      '#description' => $this->t('Specify paths to generate - comma separated, no spaces.'),
      '#default_value' => $config->get('paths_generate'),
    ];

    // Paths to not generate.
    $form['paths_do_not_generate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths and path wildcards to not generate'),
      '#description' => $this->t('Specify paths or path wildcards (ending with *) to not generate - comma separated, no spaces.'),
      '#default_value' => $config->get('paths_do_not_generate'),
    ];

    //    $form['blocks_esi'] = [
    //      '#type' => 'textarea',
    //      '#title' => $this->t('Blocks to ESI'),
    //      '#description' => $this->t('Specify block ids or block id wildcards (ending in *) to ESI include - comma separated, no spaces. If empty, all blocks are ESI included.'),
    //      '#default_value' => $config->get('blocks_esi'),
    //    ];

    $form['blocks_no_esi'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blocks and block wildcards to not ESI'),
      '#description' => $this->t('Specify block ids or block id wildcards (ending in *) to not ESI include - comma separated, no spaces.'),
      '#default_value' => $config->get('blocks_no_esi'),
    ];

    $form['blocks_frequent'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blocks and elements with class="sg-esi--<id>" to generate frequently'),
      '#description' => $this->t("Specify block ids and sg esi ids to generate frequently - comma separated, no spaces, wildcard ending with *."),
      '#default_value' => $config->get('blocks_frequent'),
    ];

    $form['drupal'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Drupal files and directories.'),
      '#description' => $this->t('Specify files and directories from which to rsync - comma separated, no spaces.'),
      '#default_value' => $config->get('drupal'),
    ];
    $form['non_drupal'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Non-Drupal files and directories.'),
      '#description' => $this->t('Specify files and directories that should never be deleted - comma separated, no spaces.'),
      '#default_value' => $config->get('non_drupal'),
    ];
    $form['rsync_public'] = [
      '#type' => 'textarea',
      '#title' => $this->t('rSync public'),
      '#default_value' => $config->get('rsync_public'),
      '#description' => $this->t('rSync command for public files.'),
    ];
    $form['rsync_public_exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('rSync public exclude'),
      '#default_value' => $config->get('rsync_public_exclude'),
      '#description' => $this->t('rSync exclude file for public files.'),
    ];
    $form['rsync_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('rSync code'),
      '#default_value' => $config->get('rsync_code'),
      '#description' => $this->t('rSync command for code files.'),
    ];

    $header = [
      'type' => $this->t('Items'),
      'operations' => $this->t('Operations'),
    ];
    $form['entity_types_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity types to generate:'),
      '#open' => TRUE,
    ];
    $form['entity_types_container']['entity_types'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no entity types.'),
    ];

    $entity_types = $entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if (!$this->canGenerateEntitiesOfEntityType($entity_type->id())) {
        continue;
      }

      $bundles = [];
      foreach ($entityTypeBundleInfo->getBundleInfo($entity_type->id()) as $bundle_id => $bundle) {
        $bundles[$bundle_id] = $bundle['label'];
      }

      $bundles_list = [
        '#theme' => 'item_list',
        '#items' => $bundles,
        '#context' => ['list_style' => 'comma-list'],
        '#empty' => $this->t('none'),
      ];

      $form['entity_types_container']['entity_types'][$entity_type->id()] = [
        'type' => [
          '#type' => 'inline_template',
          //'#template' => '<strong>{{ label }}</strong></br><span id="selected-{{ entity_type_id }}">{{ selected_bundles }}</span>',
          '#template' => '<strong>{{ label }}</strong></br>',
          '#context' => [
            'label' => $this->t('@bundle types', ['@bundle' => $entity_type->getLabel()]),
            'entity_type_id' => $entity_type->id(),
            'selected_bundles' => $bundles_list,
          ],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'select' => [
              'title' => $this->t('Select'),
              'url' => Url::fromRoute('static_generator.type_edit_form', ['entity_type_id' => $entity_type->id()]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function canGenerateEntitiesOfEntityType($entity_type) {
    if ($entity_type == 'node') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
