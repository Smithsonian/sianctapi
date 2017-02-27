<?php

/**
 * Page callback: OGMT settings
 *
 * @see edan_search_menu()
 */
function edan_search_settings_form($form, &$form_state) {

  $form['search_basics'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search Page'),
    '#description' => t('Configure the path, title and tabs for the search page.'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['search_basics']['menu_results_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Path'),
    '#default_value' => _edan_search_variable_get('menu_results_page'),
    '#description' => t('The path for the search results page.'),
    '#required' => TRUE,
  );

  $form['search_basics']['menu_results_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Page Title'),
    '#default_value' => _edan_search_variable_get('menu_results_title'),
    '#description' => t('The title for the search results page.'),
    '#required' => TRUE,
  );

  $form['search_basics']['menu_limit_search_to_unit_checkbox'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display the "Limit Search To ' . _edan_search_variable_get('unit_name') . '" Checkbox'),
    '#default_value' => _edan_search_variable_get('menu_limit_search_to_unit_checkbox'),
    '#description' => t('Check this box to display the "Limit Search to ' . _edan_search_variable_get('unit_name') . '" checkbox.'),
  );

  $form['search_basics']['menu_results_tabs'] = array(
    '#type' => 'checkbox',
    '#title' => t('Results Page Tabs'),
    '#default_value' => _edan_search_variable_get('menu_results_tabs'),
    '#description' => t('Do you want to show search tabs on your results page allowing visitors to toggle between tabs- your unit, SI-wide results and/or collections results?'),
  );

  $form['search_basics']['menu_results_tab_order'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show Museum Collections tab before All Smithsonian Collections tab'),
    '#default_value' => _edan_search_variable_get('menu_results_tab_order'),
    '#description' => t('If you show search tabs on the results page, by default All Smithsonian Collections results will be shown in the first tab and Museum Collections in the second tab. Check this box to reverse the order.'),
  );

  $form['search_basics']['menu_results_tab_objectgroups'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show tab of related Object Groups'),
    '#default_value' => _edan_search_variable_get('menu_results_tab_objectgroups'),
    '#description' => t('Check this box to show a third tab, Object Groups.'),
  );

  // Git 10b79b1e30
  /*
  $form['search_basics']['menu_results_collections_tab'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show Collections tab'),
    '#default_value' => _edan_search_variable_get('menu_results_collections_tab'),
    '#description' => t('Show a tab to search all Collections.'),
  );
  */

  $form['search_basics']['results_count_message'] = array(
    '#type' => 'textfield',
    '#title' => t('"Have Results" Message'),
    '#default_value' => _edan_search_variable_get('results_count_message'),
    '#description' => t('The message that\'s shown when a search returns results. Tokens for @count.'),
    '#required' => FALSE,
  );

  // added empty results redirect variable
  $form['search_basics']['results_empty_redirect'] = array(
    '#type' => 'textfield',
    '#title' => t('Empty Results Redirect'),
    '#default_value' => _edan_search_variable_get('results_empty_redirect'),
    '#description' => t('Page to redirect when a search returns no results.  If a redirect is set, then the empty message will appear on that page.  It is best to use the sytem path (node/[nid]) for the redirect page.'),
    '#required' => FALSE,
  );

  $form['search_basics']['results_empty_message'] = array(
    '#type' => 'textfield',
    '#title' => t('"No Results" Message'),
    '#default_value' => _edan_search_variable_get('results_empty_message'),
    '#description' => t('The message that\'s show when a search returns no results.'),
    '#required' => FALSE,
  );

  $form['search_basics']['results_per_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Per Page'),
    '#default_value' => _edan_search_variable_get('results_per_page'),
    '#description' => t('A number between 1 and 100.'),
    '#required' => FALSE,
  );

  $form['search_basics']['record_type'] = array(
    '#type' => 'textfield',
    '#title' => t('Limit results to this record type. If you leave this blank, records of all types will be returned.'),
    '#default_value' => _edan_search_variable_get('record_type'),
    '#description' => t('If you want to limit search to a certain record type, specify it here (for example edanmdm).'),
    //'#description' => t('If you want to limit search to a certain record type, specify it here (for example edanmdm). To limit to multiple record types, enter the record types separated by a space (for example: edanmdm event).'),
    '#required' => FALSE,
  );

  $form['search_basics']['default_to_online_media'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default search to results containing online media'),
    '#default_value' => _edan_search_variable_get('default_to_online_media'),
    '#description' => t('Check the box to default searches to results that contain online media (images or other).'),
    '#required' => FALSE,
  );

  $form['search_basics']['default_to_online_media_checkbox_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Online Media Checkbox Text'),
    '#default_value' => _edan_search_variable_get('default_to_online_media_checkbox_text'),
    '#description' => t('The text to display next to the contains online media checkbox, on the front-end. Defaults to: "Only return results with online media"'),
    '#required' => FALSE,
  );

  $form['search_basics']['debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Debug?'),
    '#default_value' => _edan_search_variable_get('debug'),
    '#description' => t('Check the box to write all EDAN API calls to watchdog.'),
    '#required' => FALSE,
  );

  $form['si_unit'] = array(
    '#type' => 'fieldset',
    '#title' => t('SI Unit Details'),
    '#description' => t('Configure the Unit name, codes, and default for search.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['si_unit']['local_units'] = array(
    '#type' => 'textarea',
    '#title' => t('Local Units'),
    '#default_value' => @implode("\n", _edan_search_variable_get('local_units')),
    '#description' => t('Specify your unit codes here. This is used to flag your own records in searches.'),
    '#required' => FALSE,
  );

  $form['si_unit']['default_to_local_units'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default search to local units'),
    '#default_value' => _edan_search_variable_get('default_to_local_units'),
    '#description' => t('Check the box to default searches to the local units specified in the box above.'),
    '#required' => FALSE,
  );

  $form['si_unit']['unit_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Unit Name'),
    '#default_value' => _edan_search_variable_get('unit_name'),
    '#description' => t('What is displayed to the user when limiting searches to your museum or unit, e.g. "National Museum of American History" or "Air and Space".'),
    '#required' => FALSE,
  );

  $form['fields_labels'] = array(
    '#type' => 'fieldset',
    '#title' => t('Fields and Labels'),
    '#description' => t('Configure the field order, display and field labels.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['fields_labels']['field_order'] = array(
    '#type' => 'textarea',
    '#title' => t('Field Order'),
    '#default_value' => @implode("\n", _edan_search_variable_get('field_order')),
    '#description' => t('Metadata to show in search results. Each field should be on its own line. Leave blank to hide all field, or * to show all fields. If you want to specify a set of fields and then show the remaining add an * as the last line. Examples of topics: creditLine dataSource objectType.'),
    '#required' => FALSE,
  );

  $form['fields_labels']['label_replacements'] = array(
    '#type' => 'textarea',
    '#title' => t('Label Replacement'),
    '#default_value' => _edan_search_associative_array_to_string(_edan_search_variable_get('label_replacements')),
    '#description' => t('Replace the labels shown with a different label. When making this list, do not list the "facet" name, but the "label." For example the metadata facet, physicalDescription, has a label "Physical Description" -- For this to appear on the object listing as "Phys. Descr." you enter the following line (without quotes) "Physical Description | Phys. Descr." -- notice the pipe "|" character between the label and desired replacement. Replacements are not case sensitive.'),
    '#required' => false,
  );

  $form['fields_labels']['mini_fields'] = array(
    '#type' => 'textarea',
    '#title' => t('Mini Fields'),
    '#default_value' => @implode("\n", _edan_search_variable_get('mini_fields')),
    '#description' => t('Fields listed here will be marked with the mini class. By default this will cause non-mini fields to be hidden and add a "expand" button to each record to show non-mini fields. Each field should be on its own line. Leave blank for all.'),
    '#required' => false,
  );


  $form['facet_details'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search Facets'),
    '#description' => t('Configure facet display, order and more.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['facet_details']['facets'] = array(
    '#type' => 'select',
    '#title' => t('Show Facets'),
    '#default_value' => _edan_search_variable_get('facets'),
    '#options' => array(
      'tpl' => t('Search Template (@tpl)', array('@tpl' => 'edan-search-results.tpl.php')),
      'block' => t('Show as a block'),
      'none' => t('Do not show facets'),
     ),
    '#description' => t('Set the facet display type.'),
    '#required' => true,
  );

  $form['facet_details']['facet_remove_message'] = array(
    '#type' => 'textfield',
    '#title' => t('Remove facets message'),
    '#default_value' => strlen(_edan_search_variable_get('facet_remove_message')) > 0
        ? t(_edan_search_variable_get('facet_remove_message'))
        : t("Remove facets below"),
    '#description' => t('You can modify the message that gets displayed above the list of currently selected facets.'),
    '#required' => FALSE,
  );

  $form['facet_details']['facet_replacements'] = array(
    '#type' => 'textarea',
    '#title' => t('Facet Names'),
    '#default_value' => _edan_search_associative_array_to_string(_edan_search_variable_get('facet_replacements')),
    '#description' => t('Use this box to change the order of facets and replace facet names with different names.
    Use the facet name and the new name/label for the facet, separated by a pipe character. Enter one facet per line.
    For example to rename the facet name data_source enter "data_source | Data Source" without the quotes.
    Notice the pipe "|" character between the name and desired replacement. Replacements are case sensitive.
    By default, any facets not listed here will be shown at the end of the list. You can explicitly remove facets using the "Facets to Hide" box below.'),
    '#required' => false,
  );

  $form['facet_details']['facets_hide'] = array(
    '#type' => 'textarea',
    '#title' => t('Facets to Hide'),
    '#default_value' => _edan_search_associative_array_to_string(_edan_search_variable_get('facets_hide'), TRUE),
    '#description' => t('Use this box to indicate any facets which should be hidden. Enter one facet per line, and enter only the facet name such as "data_source" without the quotes.'),
    '#required' => false,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

function edan_search_settings_form_submit($form, &$form_state) {
  // Get the current settings
  $current_settings = variable_get('edan_search', NULL);

  // Build settings from submission
  $settings = array();

  $settings['menu_results_page'] = (isset($form_state['values']['menu_results_page'])) ? $form_state['values']['menu_results_page'] : _edan_search_variable_defaults('menu_results_page');
  $settings['menu_results_title'] = (isset($form_state['values']['menu_results_title'])) ? $form_state['values']['menu_results_title'] : _edan_search_variable_defaults('menu_results_title');
  $settings['menu_results_tabs'] = (isset($form_state['values']['menu_results_tabs'])) ? (bool) $form_state['values']['menu_results_tabs'] : (bool) _edan_search_variable_defaults('menu_results_tabs');
  $settings['menu_results_tab_order'] = (isset($form_state['values']['menu_results_tab_order'])) ? (bool) $form_state['values']['menu_results_tab_order'] : (bool) _edan_search_variable_defaults('menu_results_tab_order');
  $settings['menu_results_tab_objectgroups'] = (isset($form_state['values']['menu_results_tab_objectgroups'])) ? (bool) $form_state['values']['menu_results_tab_objectgroups'] : (bool) _edan_search_variable_defaults('menu_results_tab_objectgroups');
  //$settings['menu_results_collections_tab'] = (isset($form_state['values']['menu_results_collections_tab'])) ? (bool) $form_state['values']['menu_results_collections_tab'] : (bool) _edan_search_variable_defaults('menu_results_collections_tab');
  $settings['menu_limit_search_to_unit_checkbox'] = (isset($form_state['values']['menu_limit_search_to_unit_checkbox'])) ? (bool) $form_state['values']['menu_limit_search_to_unit_checkbox'] : (bool) _edan_search_variable_defaults('menu_limit_search_to_unit_checkbox');

  $settings['results_count_message'] = (isset($form_state['values']['results_count_message'])) ? $form_state['values']['results_count_message'] : _edan_search_variable_defaults('results_count_message');
  $settings['results_empty_redirect'] = (isset($form_state['values']['results_empty_redirect'])) ? $form_state['values']['results_empty_redirect'] : _edan_search_variable_defaults('results_empty_redirect');
  $settings['results_empty_message'] = (isset($form_state['values']['results_empty_message'])) ? $form_state['values']['results_empty_message'] : _edan_search_variable_defaults('results_empty_message');

  $settings['unit_name'] = (isset($form_state['values']['unit_name'])) ? $form_state['values']['unit_name'] : _edan_search_variable_defaults('unit_name');
  $settings['debug'] = (isset($form_state['values']['debug']) && is_numeric($form_state['values']['debug'])) ? $form_state['values']['debug'] : 0; // _edan_search_variable_defaults('debug');
  $settings['default_to_local_units'] = (isset($form_state['values']['default_to_local_units']) && is_numeric($form_state['values']['default_to_local_units']))
    ? $form_state['values']['default_to_local_units']
    : _edan_search_variable_defaults('default_to_local_units');

  $settings['default_to_online_media'] = (isset($form_state['values']['default_to_online_media']) && is_numeric($form_state['values']['default_to_online_media']))
    ? $form_state['values']['default_to_online_media']
    : _edan_search_variable_defaults('default_to_online_media');

  $settings['default_to_online_media_checkbox_text'] = isset($form_state['values']['default_to_online_media_checkbox_text'])
    ? $form_state['values']['default_to_online_media_checkbox_text']
    : _edan_search_variable_defaults('default_to_online_media_checkbox_text');

  if (isset($form_state['values']['results_per_page'])) {
    if ($form_state['values']['results_per_page'] < 1) {
      $settings['results_per_page'] = 1;
    } else if ($form_state['values']['results_per_page'] > 100) {
      $settings['results_per_page'] = 100;
    } else {
      $settings['results_per_page'] = $form_state['values']['results_per_page'];
    }
  } else {
    $settings['results_per_page'] = _edan_search_variable_defaults('results_per_page');
  }

  if (isset($form_state['values']['local_units'])) {
    $arr = explode("\n", $form_state['values']['local_units']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['local_units'] = $arr;
  } else {
    $settings['local_units'] = _edan_search_variable_defaults('local_units');
  }

  if (isset($form_state['values']['field_order'])) {
    $arr = explode("\n", $form_state['values']['field_order']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['field_order'] = $arr;
  } else {
    $settings['field_order'] = _edan_search_variable_defaults('field_order');
  }

  if (isset($form_state['values']['label_replacements'])) {
    $settings['label_replacements'] = _edan_search_string_to_associative_array($form_state['values']['label_replacements']);
  } else {
    $settings['label_replacements'] = _edan_search_variable_defaults('label_replacements');
  }

  if (isset($form_state['values']['mini_fields'])) {
    $arr = explode("\n", $form_state['values']['mini_fields']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['mini_fields'] = $arr;
  } else {
    $settings['mini_fields'] = _edan_search_variable_defaults('mini_fields');
  }

  $settings['facets'] = $form_state['values']['facets'];
  $settings['facet_remove_message'] = $form_state['values']['facet_remove_message'];

  if (isset($form_state['values']['facet_replacements'])) {
    $settings['facet_replacements'] = _edan_search_string_to_associative_array($form_state['values']['facet_replacements'], false);
  } else {
    $settings['facet_replacements'] = _edan_search_variable_defaults('facet_replacements');
  }

  if (isset($form_state['values']['facets_hide'])) {
    $settings['facets_hide'] = _edan_search_string_to_associative_array($form_state['values']['facets_hide'], true);
  } else {
    $settings['facets_hide'] = _edan_search_variable_defaults('facets_hide');
  }

  if (isset($form_state['values']['record_type'])) {
    $settings['record_type'] = trim($form_state['values']['record_type']);
  } else {
    $settings['record_type'] = _edan_search_variable_defaults('record_type');
  }

  // Save settings
  variable_set('edan_search', $settings);

  // Reset our variable_get() wrapper
  drupal_static_reset('_edan_search_variable_get');

  // Since our settings affect how the menu is configured test for differences then rebuild
  if ( ($settings['menu_results_page'] != $current_settings['menu_results_page']) ) {
    menu_rebuild();
  }

}

function _edan_search_string_to_associative_array($str, $assumeValue = true) {
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
    if(count($ele) == 2) {
      $k = trim($ele[0]);
      $v = trim($ele[1]);
      $out[$k] = $v;
    }
    else if(count($ele) ==1) {
      if($assumeValue) {
        // if we only have one item, assume it's the value, not the key
        $v = trim($ele[0]);
        if(strlen(trim($v)) > 0) {
          $out[] = $v;
        }
      }
      else {
        // otherwise assume it's the key
        $k = trim($ele[0]);
        $v = trim($i);
        if(strlen(trim($k)) > 0) {
          $out[$k] = $v;
        }
      }
    }

    $i++;
  }
  return $out;
}

function _edan_search_associative_array_to_string($arr, $without_keys = false) {
  $out = array();

  if (empty($arr)) {
    return '';
  }

  if($without_keys) {
    foreach ($arr as $k => $v) {
      $out[] = trim($v);
    }
  }
  else {
    foreach ($arr as $k => $v) {
      $out[] = trim($k) . '|' . trim($v);
    }
  }

  return implode("\n", $out);
}
