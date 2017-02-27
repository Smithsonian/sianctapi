<?php

/**
 * Page callback: EDAN Record Admin Settings
 *
 * @see edan_record_menu()
 */
function edan_record_settings_form($form, &$form_state) {
  $form['menu_record_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Record Path'),
    '#default_value' => _edan_record_variable_get('menu_record_page'),
    '#description' => t('The base path for record pages.'),
    '#required' => TRUE,
  );

  $form['field_order'] = array(
    '#type' => 'textarea',
    '#title' => t('Field Order'),
    '#default_value' => @implode("\n", _edan_record_variable_get('field_order')),
    '#description' => t('Metadata to show on a record page. Each field should be on its own line. Leave blank for all. Enter 0 for none. If you want to specify a set of fields and then show the remaining add an * as the last line. Examples of topics: creditLine dataSource objectType.'),
    '#required' => FALSE,
  );

  // hpham: added settings for edan_image variables
  $image = variable_get('edan_image', array());

  $form['edan_image'] = array(
    '#type' => 'fieldset',
    '#title' => t('Image Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#tree' => TRUE
  );
  $form['edan_image']['ids'] = array(
    '#type' => 'textfield',
    '#title' => t('IDS Default Link'),
    '#default_value' => isset($image['ids']) ? $image['ids'] : 'http://ids.si.edu/ids/deliveryService',
  );
  $form['edan_image']['dynamic'] = array(
    '#type' => 'textfield',
    '#title' => t('IDS Dynamic Link'),
    '#default_value' => isset($image['dynamic']) ? $image['dynamic'] : 'http://ids.si.edu/ids/dynamic',
  );

  $form['edan_image']['thumbnail'] = array(
    '#type' => 'textfield',
    '#title' => t('Thumbnail'),
    '#default_value' => isset($image['thumbnail']) ? $image['thumbnail'] : '100',
    '#description' => t("Thumbnails are used for slideshow navigation."),
  );
  $form['edan_image']['medium'] = array(
    '#type' => 'textfield',
    '#title' => t('Search Results'),
    '#default_value' => isset($image['medium']) ? $image['medium'] : '300',
    '#description' => t("Set the image size for images when appearing as part of the search results."),
  );
  $form['edan_image']['large'] = array(
    '#type' => 'textfield',
    '#title' => t('Object Page'),
    '#default_value' => isset($image['large']) ? $image['large'] : '700',
    '#description' => t("Set the image size for the object page."),
  );

  $form['edan_image']['constrain'] = array(
    '#type' => 'select',
    '#title' => t('Constrain Image To'),
    '#options' => array(
      'max_h' => t('Height'),
      'max_w' => t('Width'),
      'max' => t('Both')
    ),
    '#default_value' => isset($image['constrain']) ? $image['constrain'] : 'max',
    '#description' => t("Determine to apply the sizes above to the height, width or both."),
  );

  $form['show_within_site'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always show EDAN Records within this website'),
    '#default_value' => _edan_record_variable_get('show_within_site'),
    '#description' => t("Check this box if you want to display all EDAN objects within this website. Leaving the box un-checked means that any EDAN Record that does not belong to this website's unit will be linked to its source record on another website."),
    '#required' => FALSE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

function edan_record_settings_form_submit($form, &$form_state) {
  // Get the current settings
  $current_settings = variable_get('edan_record', NULL);

  // Build settings from submission
  $settings = array();

  $settings['menu_record_page'] = (isset($form_state['values']['menu_record_page'])) ? $form_state['values']['menu_record_page'] : _edan_record_variable_defaults('menu_record_page');
  $settings['show_within_site'] = (isset($form_state['values']['show_within_site'])) ? $form_state['values']['show_within_site'] : _edan_record_variable_defaults('show_within_site');

  // ids, dynamic, thumbnail, medium, large, constrain
  $image = array();

  $image['ids'] = (isset($form_state['values']['edan_image']['ids'])) ? $form_state['values']['edan_image']['ids'] : '';
  $image['dynamic'] = (isset($form_state['values']['edan_image']['dynamic'])) ? $form_state['values']['edan_image']['dynamic'] : true;
  $image['thumbnail'] = (isset($form_state['values']['edan_image']['thumbnail'])) ? $form_state['values']['edan_image']['thumbnail'] : 200;
  $image['medium'] = (isset($form_state['values']['edan_image']['medium'])) ? $form_state['values']['edan_image']['medium'] : 300;
  $image['large'] = (isset($form_state['values']['edan_image']['large'])) ? $form_state['values']['edan_image']['large'] : 700;
  $image['constrain'] = (isset($form_state['values']['edan_image']['constrain'])) ? $form_state['values']['edan_image']['constrain'] : 'max_h';

  variable_set('edan_image', $image);

  if (isset($form_state['values']['field_order'])) {
    $arr = explode("\n", $form_state['values']['field_order']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['field_order'] = $arr;
  } else {
    $settings['field_order'] = _edan_record_variable_defaults('field_order');
  }

  /*
  if (isset($form_state['values']['label_replacements'])) {
    $settings['label_replacements'] = _edan_record_string_to_associative_array($form_state['values']['label_replacements']);
  } else {
    $settings['label_replacements'] = _edan_record_variable_defaults('label_replacements');
  }
  */

  // Save settings
  variable_set('edan_record', $settings);

  // Reset our variable_get() wrapper
  drupal_static_reset('_edan_record_variable_get');
  if (!empty($form_state['values']['edan_image'])) {
    variable_set('edan_image', $form_state['values']['edan_image']);
  }
  // Since our settings affect how the menu is configured test for differences then rebuild
  if ( ($settings['menu_record_page'] != $current_settings['menu_record_page'])) {
    menu_rebuild();
  }
}

function _edan_record_string_to_associative_array($str) {
  $str = trim($str);

  if (empty($str)) {
    return array();
  }

  $out = array();

  $str = str_replace("\r", "\n", $str);
  do {
    $str = str_replace("\n\n", "\n", $str);
  } while (strpos("\n\n", $str) !== FALSE);

  $arr = explode("\n", $str);
  $i = 0;
  foreach ($arr as $item) {
    $ele = explode('|', $item);
    $k = trim((count($ele) == 1) ? $i : $ele[0]);
    $v = trim($ele[1]);
    $out[$k] = $v;
    $i++;
  }
  return $out;
}

function _edan_record_associative_array_to_string($arr) {
  $out = array();

  if (empty($arr)) {
    return '';
  }

  foreach ($arr as $k => $v) {
    $out[] = trim($k) . '|' . trim($v);
  }

  return implode("\n", $out);
}
