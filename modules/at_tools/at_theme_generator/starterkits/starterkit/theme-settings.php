<?php

/**
 * @file
 * Theme settings.
 */

/**
 * Implementation of hook_form_system_theme_settings_alter()
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */

/* -- Delete this line to enable.
function HOOK_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  // $build_info = $form_state->getBuildInfo();
  // $theme = $build_info['args'][0];

  // $form['#validate'][] = 'HOOK_settings_form_validate';
  // $form['#submit'][] = 'HOOK_settings_form_submit';
}
// */


/**
 * Form validation handler for the theme settings form.
 */
/* -- Delete this line to enable.
function HOOK_settings_form_validate($form, &$form_state) {

}
// */


/**
 * Form submit handler for the theme settings form.
 */
 /* -- Delete this line to enable.
function HOOK_settings_form_submit($form, &$form_state) {

}
// */
