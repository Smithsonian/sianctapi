<?php

if(module_exists('pathauto')) {
  require_once(drupal_get_path('module', 'pathauto') . '/pathauto.inc');
}

/**
 * Implements hook_pathauto() on behalf of edan record admin module.
 */
function edan_record_admin_pathauto($op) {

  if ($op == 'settings') {
    $settings = array();
    $settings['module'] = 'edan_record_admin';
    $settings['token_type'] = 'edan_record_admin';
    $settings['groupheader'] = t('EDAN Content paths');
    $settings['patterndescr'] = t('Default path pattern (applies to all EDAN content types with blank patterns below)');
    $settings['patterndefault'] = '[edan:edan_type]/[edan:title]';

    // we don't need batch settings, because the uris aren't getting stored in drupal
    // our hook menu builds the menu paths based on whatever is set in pathauto
    $settings['batch_update_callback'] = 'edan_record_admin_pathauto_bulk_update_batch_process';

    $schema_types = _edan_record_admin_schema_types();
    foreach ($schema_types as $edan_record_schema_type) {
      $settings['patternitems'][$edan_record_schema_type] = t('Pattern for all @schema_type paths',
        array('@schema_type' => $edan_record_schema_type));
    }
    return (object) $settings;
  }

}

function edan_record_admin_pathauto_bulk_update_batch_process(&$context) {
  // The paths aren't stored in Drupal, so we don't need to update paths here.
  // We are using Pathauto to store the pattern we want to use, but the actual path data comes from EDAN.

  // But, we do want to rebuild the menus:
  menu_rebuild();

}

// Example to get the pattern saved for an EDAN content type.
function _edan_record_admin_pathauto_example() {

  $data = array();
  $data['edan_type'] = 'museum';
  $data['edan_id'] = '%'; // 'edan-id-1234234234';
  $data['title'] = '%'; // 'title!';
  $data['url'] = '%'; // 'edan-uri';

  $source = 'museum';
  // $source: An internal Drupal path to be aliased.
  // ($module, $op, $source, $data, $type = NULL, $language = LANGUAGE_NONE)
  $newpath = pathauto_create_alias('edan_record_admin', 'return', $source, $data, 'museum', LANGUAGE_NONE);
//  dpm("new path: " . $newpath);

//  $data['edan_type'] = 'event';
//  $newpath = pathauto_create_alias('edan_record_admin', 'return', $source, $data, 'event', LANGUAGE_NONE);
//  dpm($newpath);

}