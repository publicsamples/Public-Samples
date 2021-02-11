<?php

namespace Drupal\scanner\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configure messages.
 */
class ScannerConfirmForm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $tempStore) {
    $this->tempStore = $tempStore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scanner_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $store = $this->tempStore->get('scanner');
    $form = parent::buildForm($form, $form_state);
    $msg = '<span>' . $this->t('Search for') . ':</span> <strong>' . $store->get('search') . '</strong></span><br/><span>' . $this->t('Replace with') . ':</span><strong> ' . $store->get('replace') . '</strong></span>';
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $msg
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scannerStore = $this->tempStore->get('scanner');
    foreach (['search', 'replace', 'mode', 'wholeword', 'regex', 'preceded', 'followed', 'published', 'language'] as $value) {
      $values[$value] = $scannerStore->get($value);
    }
    $fields = \Drupal::config('scanner.admin_settings')->get('fields_of_selected_content_type');
    $operations = [];
    // Build an array of batch operation jobs. Batch job will need the field
    // and the filter values the users entered in the form.
    foreach ($fields as $key => $field) {
      $operations[] = ['\Drupal\scanner\Form\ScannerConfirmForm::batchReplace', [$field, $values]];
    }
    $batch = [
      'title' => t('Scanner Replace Batch'),
      'operations' => $operations,
      'finished' => '\Drupal\scanner\Form\ScannerConfirmForm::batchFinished',
      'progress_message' => t('Processed @current out of @total'),
    ];
    batch_set($batch);
    // Redirect to the scanner page after the batch is done.
    $form_state->setRedirect('scanner.admin_content');
  }

  public static function batchReplace($field, $values,&$context) {
    $pluginManager = \Drupal::service('plugin.manager.scanner');
    list($entityType, $bundle, $fieldname) = explode(':', $field);

    try {
      $plugin = $pluginManager->createInstance('scanner_entity');
      // This process can take a while so we want to extend the execution time
      // if it's less then 300 (5 minutes).
      if (ini_get('max_execution_time') < 300) {
        ini_set('max_execution_time', 300);
      }
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      drupal_set_message(t('An error occured: '. $e->getMessage()),'error');
    }

    $results = $plugin->replace($field, $values, $context['results']['data']);
    if (!empty($results)) {
      $entityKeys = array_keys($results);
      foreach ($entityKeys as $entityKey) {
        $context['results']['data'][$entityKey] = $results[$entityKey];
      }
      $context['results']['inputs'] = [
        'search' => $values['search'],
        'replace' => $values['replace']
      ];
      $context['message'] = 'Searching through field: ' . $fieldname;
    }
  }

  public static function batchFinished($success, $results, $operations) {
    $count = 0;
    if ($success) {
      if (!empty($results['data'])) {
        foreach ($results['data'] as $key => $value) {
          if (count($value) == 2) {
            $count++;
          }
          else {
            // Something went wrong.
            \Drupal::logger('scanner')->error('An issue has occured during the replace operation.');
          }
        }
        $connection = \Drupal::service('database');
        // Insert to row into the scanner table so that the action can be undone in the future.
        $undoQuery = $connection->insert('scanner')
          ->fields([
            'undo_data' => serialize($results['data']),
            'undone' => 0,
            'searched' => $results['inputs']['search'],
            'replaced' => $results['inputs']['replace'],
            'count' => $count,
            'time' => REQUEST_TIME,
          ])
          ->execute();
      }
    }
    else {
      $message = t('There were some errors.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $this->tempStore->get('scanner')->set('scanner_op','');
    return new Url('scanner.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to make the following replacement?');
  }

}