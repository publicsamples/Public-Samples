<?php

namespace Drupal\header_and_footer_scripts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide settings page for adding CSS/JS before the end of body tag.
 */
class FooterForm extends ConfigFormBase {

  /**
   * Implements FormBuilder::getFormId.
   */
  public function getFormId() {
    return 'hfs_footer_settings';
  }

  /**
   * Implements ConfigFormBase::getEditableConfigNames.
   */
  protected function getEditableConfigNames() {
    return ['header_and_footer_scripts.footer.settings'];
  }

  /**
   * Implements FormBuilder::buildForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $footer_section = $this->config('header_and_footer_scripts.footer.settings')->get();

    $form['hfs_footer'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Add Scripts and Styles in Footer'),
      '#description' => $this->t('All the defined scripts and styles in this section would be added just before closing the <strong>body</strong> tag.'),
    ];

    $form['hfs_footer']['styles'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Footer Styles'),
      '#default_value' => isset($footer_section['styles']) ? $footer_section['styles'] : '',
      '#description'   => $this->t('<p>You can add multiple <strong>stylesheets</strong> here with multiple ways, For example: </p><p>1. &lt;link type="text/css" rel="stylesheet" href="http://www.example.com/style.css" media="all" /&gt;</p><p> 2. &lt;link type="text/css" rel="stylesheet" href="/style.css" media="all" /&gt;</p><p> 3. &lt;style&gt;#header { color: grey; }&lt;/style&gt;</p>'),
      '#rows'          => 10,
    ];

    $form['hfs_footer']['scripts'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Footer Scripts'),
      '#default_value' => isset($footer_section['scripts']) ? $footer_section['scripts'] : '',
      '#description'   => $this->t('<p>You can add multiple <strong>scripts</strong> here with multiple ways, For example: </p><p>1. &lt;script type="text/javascript" src="http://www.example.com/script.js"&gt;&lt;/script&gt;</p><p> 2. &lt;script type="text/javascript" src="/script.js"&gt;&lt;/script&gt;</p><p> 3. &lt;script type="text/javascript"&gt;console.log("HFS Footer");&lt;/script&gt;</p>'),
      '#rows'          => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements FormBuilder::submitForm().
   *
   * Serialize the user's settings and save it to the Drupal's config Table.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory()
      ->getEditable('header_and_footer_scripts.footer.settings')
      ->set('styles', $values['styles'])
      ->set('scripts', $values['scripts'])
      ->save();

    drupal_set_message($this->t('Your Settings have been saved.'), 'status');
  }

}
