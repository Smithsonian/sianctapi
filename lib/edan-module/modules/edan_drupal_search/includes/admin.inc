<?php

/**
 * Page callback: EDAN Searchtab settings
 *
 * @see edan_drupal_searchtab_menu()
 */
function edan_searchtab_settings_form($form, &$form_state) {

  $form['searchtab_results_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Path'),
    '#default_value' => _edan_search_variable_get('searchtab_results_page'),
    '#description' => t('The path for the search results page. The path always begins with "search/", Drupal\'s default.'),
    '#required' => TRUE,
  );

  $form['searchtab_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Search Tab Title'),
    '#default_value' => _edan_search_variable_get('searchtab_title'),
    '#description' => t('The title for the search tab.'),
    '#required' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}
function edan_searchtab_settings_form_validate($form, &$form_state) {

  $pg_path = $form_state['values']['searchtab_results_page'];

  if(strlen(trim($pg_path)) > 0) {
    if(strpos($pg_path, '/') !== FALSE || strpos($pg_path, '\\') !== FALSE) {
      form_set_error('searchtab_results_page', t('Please enter a single word using standard alphabet characters, optional dashes and underscores. Please do not enter any slashes.'));
    }
  }

}
function edan_searchtab_settings_form_submit($form, &$form_state) {
  // Get the current settings
  $current_settings = variable_get('edan_search', NULL);
  $original_path = isset($current_settings['searchtab_results_page']) ? $current_settings['searchtab_results_page'] : '';

  // Build settings from submission
  $current_settings['searchtab_results_page'] = (isset($form_state['values']['searchtab_results_page'])) ? $form_state['values']['searchtab_results_page'] : _edan_search_variable_defaults('searchtab_results_page');
  $current_settings['searchtab_title'] = (isset($form_state['values']['searchtab_title'])) ? $form_state['values']['searchtab_title'] : _edan_search_variable_defaults('searchtab_title');

  // Save settings
  variable_set('edan_search', $current_settings);

  // Reset our variable_get() wrapper
  drupal_static_reset('_edan_search_variable_get');

  // Since our settings affect how the menu is configured test for differences then rebuild
//  if ( ($original_path != $current_settings['searchtab_results_page']) ) {
    menu_rebuild();
//  }

}

