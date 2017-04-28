<?php

function edan_custom_searches_list($custom_search_id = NULL) {

  if(NULL !== $custom_search_id) {
    // redirect to admin/config/search/edan-custom/$custom_search_id
    //$form = drupal_get_form('edan_custom_search_settings_form');
    drupal_goto("admin/config/search/edan-custom-search-edit/$custom_search_id");
  }

  drupal_set_title(t('Custom EDAN Searches'));

  // Build the form
  $form = drupal_get_form('edan_custom_search_list_form');

  $output = render($form);
  return $output;

}

function edan_custom_search_list_form() {

  $form = array();
  $options = array();

  // do we have order (field) and possibly sort (order) in the URI?
  $params = drupal_get_query_parameters();
  $page = 0; // get this from the querystring; use it to set $start below
  $start = 0;
  $rows = 25;

  // @todo note fixed upper bound for page numbers
  // how should we represent "a lot"? - maybe use a 'more' button
  if(array_key_exists('page', $params) && is_numeric($params['page']) && $params['page'] < 1000) {
    $page = $params['page'];
    $start = $page * $rows;
  }

  // get our saved custom search settings
  $custom_searches = variable_get('edan_custom_searches');

  // ew, re-sort by title
  $results_by_title = array();
  foreach($custom_searches as $k => $src) {
    $title = $src['custom_results_title'];
    $results_by_title[$title] = $src;
  }
  ksort($results_by_title);

  // now put them back into our array
  $custom_searches = array();
  foreach($results_by_title as $k => $src) {
    $key = $src['custom_search_id'];
    $custom_searches[$key] = $src;
  }

  if(is_array($custom_searches)) {

    foreach($custom_searches as $slug => $search_details) {

    $block = 'No';
    if(isset($search_details['create_block']) && $search_details['create_block'] == 1) {
      $block = 'Yes';
    }
    $page = 'No';
    if(isset($search_details['create_page']) && $search_details['create_page'] == 1) {
      $page = 'Yes';
    }
    $endpoint = 'collections';
    if(isset($search_details['endpoint']) && $search_details['endpoint'] !== 'collections') {
      $endpoint = 'universal';
    }

    $options[$slug] = array(
      'custom_search' => array(
      ),
      'block' => $block,
      'page' => $page,
      'endpoint' => $endpoint,

    );

    $operations = array(
      'edit' => array(
        'title' => t('edit'),
        'href' => 'admin/config/search/edan-custom-search-edit/' . $slug,
      ),
      'remove' => array(
        'title' => t('delete'),
        'href' => 'admin/config/search/edan-custom-search-delete/' . $slug,
      ),
    );

    $options[$slug]['operations'] = array(
      'data' => array(
        '#theme' => 'links',
        '#links' => $operations,
        '#attributes' => array('class' => array('links', 'inline')),
      ),
    );

    if(isset($search_details['menu_results_page']) && strlen($search_details['menu_results_page']) > 0
      && isset($search_details['create_page']) && $search_details['create_page'] == 1) {

      $options[$slug]['custom_search'] = array(
        'data' => array(
          '#type' => 'link',
          '#title' => $search_details['custom_results_title'],
          '#href' => $search_details['menu_results_page'],
        ),
      );
    }
    else {
      $options[$slug]['custom_search'] = array(
        'data' => array(
          '#type' => 'markup',
          '#markup' => $search_details['custom_results_title'],
        ),
      );
    }

  } // for each public OG
  }
  // Table header
  $header = array(
    'custom_search' => array('data' => t('Custom Search'), 'field' => 'custom_search'),
    'block' => array('data' => t('Block?'), 'field' => 'block'),
    'page' => array('data' => t('Page?'), 'field' => 'page'),
    'endpoint' => array('data' => t('Endpoint'), 'field' => 'endpoint'),
    'operations' => array('data' => t('Operations')),
  );

  // with a query-powered tableselect, we would ordinarily set the sort field info here

  // Add bulk actions
  /*
  $form['update_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
    '#description' => t('Apply the following action to the selected saved searches.'),
  );

  $form['update_options']['update_option'] = array(
    '#type' => 'select',
    '#title' => '',
    '#options' => array(t('Delete')),
    '#suffix' => '&nbsp;&nbsp;&nbsp;&nbsp;',
  );

  $form['update_options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );
*/
  $form['update_options']['add_new_link'] = array(
    '#type' => 'markup',
    '#markup' => '<br />' . l('Create new search', '/admin/config/search/edan-custom-search-edit' ),
  );

  // Add table
  $form['custom_searches'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No content available.'),
    '#attributes' => array(),
  );

  // Pager
  $total_count = count($custom_searches);
  $pager_vars = array(
    'parameters' => array(),
  );

  pager_default_initialize($total_count, $rows);
  $pager = theme('pager', $pager_vars);

  $form['pager'] = array(
    '#type' => 'markup',
    '#markup' => $pager,
  );

  return $form;
}

/**
 * @param $form
 * @param $form_state
 */
function edan_custom_search_list_form_submit($form, &$form_state) {
//@todo
/*
  // perform the selected action
  if($form_state['values']['op'] == 'Update') {

    // get the selected grouptype
    $grouptype_id = $form_state['values']['group_type'];

    $og_types = _ogmt_variable_get('object_group_types');


    // make an array of just the object groups
    $object_groups= array();
    foreach($form_state['values']['object_groups'] as $og_id => $value) {
      if(NULL !== $value && $og_id == (string)$value) {
        $object_groups[] = $og_id;
      }
    }
    $object_groups_string = implode('+', $object_groups);

    // pass the action
    $action = '';
    switch($form_state['values']['update_option']) {
      case 0:
        $action = 'add';
        break;
      case 1:
        $action = 'remove';
        break;
      case 2:
        $action = 'feature';
        break;
      case 3:
        $action = 'unfeature';
        break;
    }

    if($action == 'remove') {
      // do a redirect to the confirmation page
      unset($form_state['destination']);
      $form_state['redirect'] ='admin/content/external-object-group-remove/' . $object_groups_string;
    }
    else {
      // call the multi-update function to perform the task
      $ret = ogmt_admin_menupage_external_object_groups_multiple($action, $object_groups_string, $grouptype_id);
    }
  }
  else { // set variables for filtering the list
    //@todo $_SESSION['ogmt_admin']['unit_filter'] = $form_state['values']['unit'];
  }
*/
}

function edan_custom_search_list_remove_form($form, &$form_state, $slug) {

  // default
  $msg = t('No Saved searches were specified.');
  $form['msg'] = array(
    '#type' => 'markup',
    '#markup' => '<p>' . $msg . '</p>',
  );

  $query = current_path();
  $query_array = explode('/', $query);
  if(!is_array($query_array)) {
    return $form;
  }

  $query_array = array_map('filter_xss', $query_array);
  if(!in_array('edan-custom-search-delete', $query_array)) {
    // we should only get here from the menu item
    return MENU_NOT_FOUND;
  }

  $key = array_search ('edan-custom-search-delete', $query_array);
  if(count($query_array) == $key + 1) {
    // fail, we need the custom search slugs in the param after the menu item
    return $form;
  }
  $custom_search_ids = $query_array[$key + 1];
  $page_id = NULL;

  if(array_key_exists($key + 2, $query_array)) {
    $page_id = $query_array[$key + 2];
  }

  drupal_set_title("Are you sure?");

  if(strlen(trim($custom_search_ids)) > 0) {
    $custom_searches_array = explode('+', $custom_search_ids);
    $custom_searches_array = array_map('trim', $custom_searches_array);
  }

  $what = "Custom Search";
  $id = "ID";
  $ids = implode(', ', $custom_searches_array);
  if(count($custom_searches_array) > 1) {
    $what .= "es";
    $id .= "s";
    $page_id = NULL;
  }
  $msg = t('Are you sure you want to no longer show on this website the %what with %id:<br /> %ids?',
    array('%what' => $what, '%id' => $id, '%ids' => $ids));
  $form['msg'] = array(
    '#type' => 'markup',
    '#markup' => '<p>' . $msg . '</p>',
  );

  $form['custom_searches'] = array(
    '#type' => 'hidden',
    '#value' => implode('+', $custom_searches_array),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Remove',
  );
  $form['cancel'] = array(
    '#type' => 'submit',
    '#value' => 'Cancel',
  );
  return $form;

  return array();


}

function edan_custom_search_list_remove_form_submit($form, &$form_state) {

  $custom_searches = $form_state['values']['custom_searches'];
  $custom_searches_array = explode('+', $custom_searches);

  if($form_state['values']['op'] == 'Cancel') {
    // just redirect to object groups list
    $form_state['redirect'] = 'admin/config/search/edan-custom-search-list';
    return;
  }


  $all_custom_searches = variable_get('edan_custom_searches');
  foreach($custom_searches_array as $slug) {
    if(array_key_exists($slug, $all_custom_searches)) {
      unset($all_custom_searches[$slug]);
    }
  } // each object group
  variable_set('edan_custom_searches', $all_custom_searches);
  menu_rebuild();

  drupal_set_message("The searches were deleted.");
  // redirect to searches list
  $form_state['redirect'] = 'admin/config/search/edan-custom-search-list';

}

// ogmt_admin_external_object_group_remove_form
function edan_custom_search_list_multiple($action = null, $slug = null) {
/*
  // List of possible actions that can be done on selected items
  $actions = array(
    'add', // we can add or remove, feature or unfeature external OGs
    'remove',
    'feature',
    'unfeature',
  );

  $object_groups = explode('+', $object_groups_string);
  $has_action = !empty($action);

  // Debug
  if (OGMT_ADMIN_DEBUG === TRUE) {
    echo '<pre>';
    echo "\n" . 'Has Action: ' . var_export($has_action, TRUE) . ' | Action: ' . var_export($action, TRUE);
    echo "\n" . 'Object Groups: ' . var_export($object_groups_string, TRUE);
    echo '</pre>';
  }

  // Validation
  if (!$has_action || !in_array($action, $actions)) {
    return NULL;
  }

  if (count($object_groups) < 1) {
    drupal_set_message('No Object Groups selected.');
    return NULL;
  }

  // ---
  // Now start processing the actual requests!
  // ---

  $add = TRUE;
  $feature = FALSE;
  if($action == 'remove') {
    $add = FALSE;
  }
  elseif($action == 'feature') {
    $feature = TRUE;
  }

  // Object Group Page
  if (count($object_groups) > 0) {

    switch ($action) {
      case 'remove':
        // Delete confirmation page
        return t('Remove form would go here!');
        break;

      default:
        foreach($object_groups as $og) {
          // Call edan for a list of groups
          $ec = get_edan_connection();

          $params = array(
            'id' => $og,
          );
          $ok = $ec->callEDAN('content/v1.1/content/getContent.htm', $params);
          $this_og = $ec->getResultsJSON();
          $url = isset($this_og['url']) ? $this_og['url'] : '';

          _object_groups_external_objectgroup_set($og, $url, $add, $feature, $grouptype_id);
        }
        return array();
        break;
    }
  }
  return NULL;
*/
}


/**
 * Page callback: EDAN Custom Search settings- settings for an individual custom search
 *
 * @see edan_custom_search_menu()
 */
function edan_custom_search_edit_form($form, &$form_state, $custom_form_id = NULL) {

  module_load_include('inc', 'edan_custom_search', 'includes/edan_custom_search');
  $slug = make_slug($custom_form_id);

  $defaults = array();
  if(NULL !== $custom_form_id) {
    $defaults = _edan_custom_search_variable_get($slug);
  }

  $form['custom_search_id'] = array(
    '#type' => 'hidden',
    '#default_value' => NULL !== $custom_form_id ? $slug : '',
  );

  $form['custom_search_details'] = array(
    '#type' => 'fieldset',
    '#title' => t('Custom Search Details'),
    '#description' => t('Basic details for your custom search.'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => -100,
  );

  $form['custom_search_details']['custom_results_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Custom Search Title'),
    '#default_value' => isset($defaults['custom_results_title']) ? $defaults['custom_results_title'] : '',
    '#description' => t('A title useful for administrators to recognize this search results page.'),
    '#required' => TRUE,
  );

  $form['custom_search_details']['endpoint'] = array(
    '#type' => 'radios',
    '#title' => t('Which endpoint do you want to use?'),
    '#options' => array('universal' => 'Universal Search. All EDAN record types, limited facets. metadata/v1.1/metadata/search.htm',
    'collections' => 'Collections Search. Only edanmdm records, lots of facets. metadata/v1.1/collections/search.htm'),
    '#default_value' => isset($defaults['endpoint']) ? $defaults['endpoint'] : 0,
    '#required' => TRUE,
  );

  $form['custom_search_details']['search_params'] = array(
    '#type' => 'textarea',
    '#title' => t('Search parameters'),
    '#default_value' => isset($defaults['search_params']) ? $defaults['search_params'] : '',
    '#required' => TRUE,
    '#description' => 'Enter each parameter on one line (separate with line breaks). For example q=test on one line and fqs=["type:edanmdm"] on the next line.',
  );

  $form['custom_search_details']['boost'] = array(
    '#type' => 'textfield',
    '#title' => t('Boost'),
    '#default_value' => isset($defaults['boost']) ? $defaults['boost'] : '',
    '#required' => FALSE,
    '#description' => 'You can boost certain things using this field. Typical content weighting is around 1.4. '
        . '<br />To boost objectgroups to the top of search results for example, you could enter: type:objectgroup^4'
        . '<br />To boost events above others, and objectgroups above events, you could do this: type:objectgroup^6,type:event^4',
  );

  $form['custom_search_details']['create_page'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a page?'),
    '#default_value' => isset($defaults['create_page']) ? $defaults['create_page'] : '',
    '#description' => t('Check the box to create a results page using this query.'),
    '#required' => FALSE,
  );

  $form['custom_search_details']['create_block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a block?'),
    '#default_value' => isset($defaults['create_block']) ? $defaults['create_block'] : '',
    //'#default_value' => 0,
    //'#value' => 0,
    '#description' => t('Check the box to create a results block using this query.'),
    '#required' => FALSE,
  );

  $form['custom_search_details']['hide_search_form'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide search form?'),
    '#default_value' => isset($defaults['hide_search_form']) ? $defaults['hide_search_form'] : 0,
      '#description' => t('Check the box to hide the search form for this custom search. Only search results will be displayed.'),
    '#required' => FALSE,
  );

  $form['custom_search_details']['hide_default_facets'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide default search facets'),
    '#default_value' => isset($defaults['hide_default_facets']) ? $defaults['hide_default_facets'] : 1,
    '#description' => t('Check the box to hide the "remove facet" links for the facets you set for this search.'),
    '#required' => FALSE,
  );

  $tpl_slug = str_replace('-', '_', $slug);
  $form['custom_search_details']['search_results_template'] = array(
    '#type' => 'select',
    '#title' => t('Search results template'),
    '#default_value' => isset($defaults['search_results_template']) ? $defaults['search_results_template'] : 'tpl_default',
    '#options' => array('tpl_default' => 'edan_custom_search_results', 'tpl_custom' => 'edan_custom_search_' . $tpl_slug . '_results'),
    '#description' => t('You can use the default template or provide your own.'),
    '#required' => TRUE,
  );

  $form['search_basics'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search Page'),
    '#description' => t('Configure the path, title and tabs for the search page.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['search_basics']['menu_results_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Path'),
    '#default_value' => isset($defaults['menu_results_page']) ? $defaults['menu_results_page'] : '',
    '#description' => t('The path for the search results page.'),
    '#required' => FALSE,
  );

  $form['search_basics']['menu_results_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Page Title'),
    '#default_value' => isset($defaults['menu_results_title']) ? $defaults['menu_results_title'] : '',
    '#description' => t('The title for the search results page.'),
    '#required' => TRUE,
  );

  $form['search_basics']['menu_results_tabs'] = array(
    '#type' => 'checkbox',
    '#title' => t('Results Page Tabs'),
    '#default_value' => isset($defaults['menu_results_tabs']) ? $defaults['menu_results_tabs'] : '',
    '#description' => t('Do you want to show search tabs on your results page allowing visitors to toggle between tabs- your unit, SI-wide results and/or collections results?'),
  );

  $form['search_basics']['menu_results_tab_order'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show Museum Collections tab before All Smithsonian Collections tab'),
    '#default_value' => isset($defaults['menu_results_tab_order']) ? $defaults['menu_results_tab_order'] : '',
    '#description' => t('If you show search tabs on the results page, by default All Smithsonian Collections results will be shown in the first tab and Museum Collections in the second tab. Check this box to reverse the order.'),
  );

  $form['search_basics']['results_count_message'] = array(
    '#type' => 'textfield',
    '#title' => t('"Have Results" Message'),
    '#default_value' => isset($defaults['results_count_message']) ? $defaults['results_count_message'] : '',
    '#description' => t('The message that\'s shown when a search returns results. Tokens for @count.'),
    '#required' => FALSE,
  );

  // added empty results redirect variable
  $form['search_basics']['results_empty_redirect'] = array(
    '#type' => 'textfield',
    '#title' => t('Empty Results Redirect'),
    '#default_value' => isset($defaults['results_empty_redirect']) ? $defaults['results_empty_redirect'] : '',
    '#description' => t('Page to redirect when a search returns no results.  If a redirect is set, then the empty message will appear on that page.  It is best to use the sytem path (node/[nid]) for the redirect page.'),
    '#required' => FALSE,
  );

  $form['search_basics']['results_empty_message'] = array(
    '#type' => 'textfield',
    '#title' => t('"No Results" Message'),
    '#default_value' => isset($defaults['results_empty_message']) ? $defaults['results_empty_message'] : '',
    '#description' => t('The message that\'s show when a search returns no results.'),
    '#required' => FALSE,
  );

  $form['search_basics']['results_per_page'] = array(
    '#type' => 'textfield',
    '#title' => t('Results Per Page'),
    '#default_value' => isset($defaults['results_per_page']) ? $defaults['results_per_page'] : '',
    '#description' => t('A number between 1 and 100.'),
    '#required' => FALSE,
  );

  $form['search_basics']['record_type'] = array(
    '#type' => 'textfield',
    '#title' => t('Limit results to this record type. If you leave this blank, records of all types will be returned.'),
    '#default_value' => isset($defaults['record_type']) ? $defaults['record_type'] : '',
    '#description' => t('If you want to limit search to a certain record type, specify it here (for example edanmdm).'),
    //'#description' => t('If you want to limit search to a certain record type, specify it here (for example edanmdm). To limit to multiple record types, enter the record types separated by a space (for example: edanmdm event).'),
    '#required' => FALSE,
  );
/*
  $form['search_basics']['default_to_online_media'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default search to results containing online media'),
    '#default_value' => isset($defaults['results_empty_message']) ? $defaults['results_empty_message'] : '',
    '#description' => t('Check the box to default searches to results that contain online media (images or other).'),
    '#required' => FALSE,
  );
    $form['si_unit'] = array(
      '#type' => 'fieldset',
      '#title' => t('SI Unit Details'),
      '#description' => t('Configure the Unit name, codes, and default for search.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $local_units = isset($defaults['local_units']) ? $defaults['local_units'] : '';
    $form['si_unit']['local_units'] = array(
      '#type' => 'textarea',
      '#title' => t('Local Units'),
      '#default_value' => @implode("\n", $local_units),
      '#description' => t('Specify your unit codes here. This is used to flag your own records in searches.'),
      '#required' => FALSE,
    );

    $form['si_unit']['default_to_local_units'] = array(
      '#type' => 'checkbox',
      '#title' => t('Default search to local units'),
      '#default_value' => isset($defaults['default_to_local_units']) ? $defaults['default_to_local_units'] : '',
      '#description' => t('Check the box to default searches to the local units specified in the box above.'),
      '#required' => FALSE,
    );

    $form['si_unit']['unit_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Unit Name'),
      '#default_value' => isset($defaults['unit_name']) ? $defaults['unit_name'] : '',
      '#description' => t('What is displayed to the user when limiting searches to your museum or unit, e.g. "National Museum of American History" or "Air and Space".'),
      '#required' => FALSE,
    );
*/
    $form['fields_labels'] = array(
      '#type' => 'fieldset',
      '#title' => t('Fields and Labels'),
      '#description' => t('Configure the field order, display and field labels.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $field_order = isset($defaults['field_order']) ? $defaults['field_order'] : '';
    $form['fields_labels']['field_order'] = array(
      '#type' => 'textarea',
      '#title' => t('Field Order'),
      '#default_value' => @implode("\n", $field_order),
      '#description' => t('Metadata to show in search results. Each field should be on its own line. Leave blank to hide all field, or * to show all fields. If you want to specify a set of fields and then show the remaining add an * as the last line. Examples of topics: creditLine dataSource objectType.'),
      '#required' => FALSE,
    );

    $label_replacements = isset($defaults['label_replacements']) ? $defaults['label_replacements'] : array();
    $form['fields_labels']['label_replacements'] = array(
      '#type' => 'textarea',
      '#title' => t('Label Replacement'),
      '#default_value' => _edan_custom_search_associative_array_to_string($label_replacements),
      '#description' => t('Replace the labels shown with a different label. When making this list, do not list the "facet" name, but the "label." For example the metadata facet, physicalDescription, has a label "Physical Description" -- For this to appear on the object listing as "Phys. Descr." you enter the following line (without quotes) "Physical Description | Phys. Descr." -- notice the pipe "|" character between the label and desired replacement. Replacements are not case sensitive.'),
      '#required' => false,
    );

    $mini_fields = isset($defaults['mini_fields']) ? $defaults['mini_fields'] : '';
    $form['fields_labels']['mini_fields'] = array(
      '#type' => 'textarea',
      '#title' => t('Mini Fields'),
      '#default_value' => @implode("\n", $mini_fields),
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
      '#default_value' => isset($defaults['facets']) ? $defaults['facets'] : 'tpl',
      '#options' => array(
        'tpl' => t('Search Results Template (edan_custom_search_facets)'),
        'tpl_custom' => t('Search Results Template (@tpl)', array('@tpl' => 'edan_custom_search_' . $tpl_slug . '_facets')),
        'block' => t('Show as a block'),
        'none' => t('Do not show facets'),
      ),
      '#description' => t('Set the facet display type.'),
      '#required' => true,
    );

  $facet_remove_message = isset($defaults['facet_remove_message']) && strlen(trim($defaults['facet_remove_message'])) > 0 ? t($defaults['facet_remove_message']) : t('Remove facets below');
    $form['facet_details']['facet_remove_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Remove facets message'),
      '#default_value' => $facet_remove_message,
      '#description' => t('You can modify the message that gets displayed above the list of currently selected facets.'),
      '#required' => FALSE,
    );

    $facet_replacements = isset($defaults['facet_replacements']) ? $defaults['facet_replacements'] : array();
    $form['facet_details']['facet_replacements'] = array(
      '#type' => 'textarea',
      '#title' => t('Facet Names'),
      '#default_value' => _edan_custom_search_associative_array_to_string($facet_replacements),
      '#description' => t('Use this box to change the order of facets and replace facet names with different names.
      Use the facet name and the new name/label for the facet, separated by a pipe character. Enter one facet per line.
      For example to rename the facet name data_source enter "data_source | Data Source" without the quotes.
      Notice the pipe "|" character between the name and desired replacement. Replacements are case sensitive.
      By default, any facets not listed here will be shown at the end of the list. You can explicitly remove facets using the "Facets to Hide" box below.'),
      '#required' => false,
    );

    $facets_hide = isset($defaults['facets_hide']) ? $defaults['facets_hide'] : array();
    $form['facet_details']['facets_hide'] = array(
      '#type' => 'textarea',
      '#title' => t('Facets to Hide'),
      '#default_value' => _edan_custom_search_associative_array_to_string($facets_hide, TRUE),
      '#description' => t('Use this box to indicate any facets which should be hidden. Enter one facet per line, and enter only the facet name such as "data_source" without the quotes.'),
      '#required' => false,
    );

  //@todo: add a description

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

function edan_custom_search_edit_form_validate($form, &$form_state, $custom_form_id = NULL) {

  $create_page = (isset($form_state['values']['create_page'])) ? $form_state['values']['create_page'] : NULL;
  $page_path = (isset($form_state['values']['menu_results_page'])) ? $form_state['values']['menu_results_page'] : NULL;

  if(NULL !== $create_page && $create_page == 1) {
    if(NULL == $page_path || strlen(trim($page_path)) < 0) {
      form_set_error('menu_results_page', 'You must specify a path for your custom page. Or, un-check the "Create a page" checkbox.');
      return FALSE;
    }
  }

  //@todo- custom search title must be unique

  //@todo- path must be unique also- check drupal paths and other saved searches

  //@todo- show the machine slug for this saved search and allow the user to save it (?)

}

function edan_custom_search_edit_form_submit($form, &$form_state, $custom_form_id = NULL) {

  $slug = (isset($form_state['values']['custom_search_id']) && strlen($form_state['values']['custom_search_id']) > 0)
      ? $form_state['values']['custom_search_id'] : make_slug($form_state['values']['custom_results_title']);

  // Get the current settings for this search
  $current_settings = _edan_custom_search_variable_get($slug, NULL);

  // Build settings from submission
  $settings = array();

  $settings['custom_search_id'] = $slug;
  $settings['custom_results_title'] = (isset($form_state['values']['custom_results_title'])) ? $form_state['values']['custom_results_title'] : _edan_custom_search_variable_defaults('custom_results_title');
  $settings['endpoint'] = (isset($form_state['values']['endpoint'])) ? $form_state['values']['endpoint'] : _edan_custom_search_variable_defaults('endpoint');
  $settings['search_params'] = (isset($form_state['values']['search_params'])) ? $form_state['values']['search_params'] : _edan_custom_search_variable_defaults('search_params');
  $settings['boost'] = (isset($form_state['values']['boost'])) ? $form_state['values']['boost'] : _edan_custom_search_variable_defaults('boost');
  $settings['create_page'] = (isset($form_state['values']['create_page'])) ? $form_state['values']['create_page'] : _edan_custom_search_variable_defaults('create_page');
  $settings['create_block'] = (isset($form_state['values']['create_block'])) ? $form_state['values']['create_block'] : _edan_custom_search_variable_defaults('create_block');
  $settings['hide_search_form'] = (isset($form_state['values']['hide_search_form'])) ? $form_state['values']['hide_search_form'] : _edan_custom_search_variable_defaults('hide_search_form');
  $settings['hide_default_facets'] = (isset($form_state['values']['hide_default_facets'])) ? $form_state['values']['hide_default_facets'] : _edan_custom_search_variable_defaults('hide_default_facets');
  $settings['search_results_template'] = (isset($form_state['values']['search_results_template'])) ? $form_state['values']['search_results_template'] : _edan_custom_search_variable_defaults('search_results_template');

  $settings['menu_results_page'] = (isset($form_state['values']['menu_results_page'])) ? $form_state['values']['menu_results_page'] : '';
  $settings['menu_results_title'] = (isset($form_state['values']['menu_results_title'])) ? $form_state['values']['menu_results_title'] : _edan_custom_search_variable_defaults('menu_results_title');
  $settings['menu_results_tabs'] = (isset($form_state['values']['menu_results_tabs'])) ? (bool) $form_state['values']['menu_results_tabs'] : (bool) _edan_custom_search_variable_defaults('menu_results_tabs');
  $settings['menu_results_tab_order'] = (isset($form_state['values']['menu_results_tab_order'])) ? (bool) $form_state['values']['menu_results_tab_order'] : (bool) _edan_custom_search_variable_defaults('menu_results_tab_order');

  $settings['results_count_message'] = (isset($form_state['values']['results_count_message'])) ? $form_state['values']['results_count_message'] : _edan_custom_search_variable_defaults('results_count_message');
  $settings['results_empty_redirect'] = (isset($form_state['values']['results_empty_redirect'])) ? $form_state['values']['results_empty_redirect'] : _edan_custom_search_variable_defaults('results_empty_redirect');
  $settings['results_empty_message'] = (isset($form_state['values']['results_empty_message'])) ? $form_state['values']['results_empty_message'] : _edan_custom_search_variable_defaults('results_empty_message');

/*
  $settings['unit_name'] = (isset($form_state['values']['unit_name'])) ? $form_state['values']['unit_name'] : _edan_custom_search_variable_defaults('unit_name');
  $settings['default_to_local_units'] = (isset($form_state['values']['default_to_local_units']) && is_numeric($form_state['values']['default_to_local_units']))
    ? $form_state['values']['default_to_local_units']
    : _edan_custom_search_variable_defaults('default_to_local_units');

  $settings['default_to_online_media'] = (isset($form_state['values']['default_to_online_media']) && is_numeric($form_state['values']['default_to_online_media']))
    ? $form_state['values']['default_to_online_media']
    : _edan_custom_search_variable_defaults('default_to_online_media');
*/
  if (isset($form_state['values']['results_per_page'])) {
    if ($form_state['values']['results_per_page'] < 1) {
      $settings['results_per_page'] = 1;
    } else if ($form_state['values']['results_per_page'] > 100) {
      $settings['results_per_page'] = 100;
    } else {
      $settings['results_per_page'] = $form_state['values']['results_per_page'];
    }
  } else {
    $settings['results_per_page'] = _edan_custom_search_variable_defaults('results_per_page');
  }

  if (isset($form_state['values']['local_units'])) {
    $arr = explode("\n", $form_state['values']['local_units']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['local_units'] = $arr;
  } else {
    $settings['local_units'] = _edan_custom_search_variable_defaults('local_units');
  }

  if (isset($form_state['values']['field_order'])) {
    $arr = explode("\n", $form_state['values']['field_order']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['field_order'] = $arr;
  } else {
    $settings['field_order'] = _edan_custom_search_variable_defaults('field_order');
  }

  if (isset($form_state['values']['label_replacements'])) {
    $settings['label_replacements'] = _edan_custom_search_string_to_associative_array($form_state['values']['label_replacements']);
  } else {
    $settings['label_replacements'] = _edan_custom_search_variable_defaults('label_replacements');
  }

  if (isset($form_state['values']['mini_fields'])) {
    $arr = explode("\n", $form_state['values']['mini_fields']);
    array_walk($arr, create_function('&$val', '$val = trim($val);'));
    $settings['mini_fields'] = $arr;
  } else {
    $settings['mini_fields'] = _edan_custom_search_variable_defaults('mini_fields');
  }

  $settings['facets'] = $form_state['values']['facets'];

  $settings['facet_remove_message'] = $form_state['values']['facet_remove_message'];

  if (isset($form_state['values']['facet_replacements'])) {
    $settings['facet_replacements'] = _edan_custom_search_string_to_associative_array($form_state['values']['facet_replacements'], false);
  } else {
    $settings['facet_replacements'] = _edan_custom_search_variable_defaults('facet_replacements');
  }

  if (isset($form_state['values']['facets_hide'])) {
    $settings['facets_hide'] = _edan_custom_search_string_to_associative_array($form_state['values']['facets_hide'], true);
  } else {
    $settings['facets_hide'] = _edan_custom_search_variable_defaults('facets_hide');
  }

  if (isset($form_state['values']['record_type'])) {
    $settings['record_type'] = trim($form_state['values']['record_type']);
  } else {
    $settings['record_type'] = _edan_custom_search_variable_defaults('record_type');
  }

  // Save settings for this search
  $custom_searches = variable_get('edan_custom_searches');
  if(!is_array($custom_searches)) {
    $custom_searches = array();
  }

  // if the first 18 chars of this slug are already in use, create a new one
  $i = 0;
  $tries = 0;
  $temp_slug = substr($slug, 0, 18);
  while (isset($custom_searches[$temp_slug]) && $i < 100) {
    $temp_slug = $slug . (string)$i;
    $tries++;
    $i++;
  }
  // if we already have a custom form id, and we have 0 or 1 matches on the slug, leave it alone
  if(!empty($form_state['values']['custom_search_id']) && $tries < 2) {
    $temp_slug = $slug;
  }

  if($tries < 99) {
    $slug = $temp_slug;
    $settings['custom_search_id'] = $slug;
    $custom_searches[$slug] = $settings;
    variable_set('edan_custom_searches', $custom_searches);

    // Since our settings affect how the menu is configured test for differences then rebuild
    if (empty($current_settings) || ($settings['menu_results_page'] != $current_settings['menu_results_page']) ) {
      menu_rebuild();
    }

    drupal_set_message("Your search was saved.");

    $form_state['redirect'] = 'admin/config/search/edan-custom-search-list';
  }
  else {
    // @todo - watchdog- we have 100 blocks with the same prefix!
    drupal_set_message("Sorry but your custom search cannot be saved. Please change the Custom Search Title so that the first 18 characeters are unique.", 'error');
  }


}

function _edan_custom_search_string_to_associative_array($str, $assumeValue = true) {
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

function _edan_custom_search_associative_array_to_string($arr, $without_keys = false) {
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
