<?php

function edan_custom_search_blocks_search_form($delta = '') {

  $this_slug = '';
  $title = '';
  $content = '';

  // try to find the custom block based on the delta
  $all_custom_searches = variable_get('edan_custom_searches');
  if(is_array($all_custom_searches)) {
    foreach($all_custom_searches as $slug => $search_details) {

      if('ecs_' . trim(str_replace('-', '_', $slug), 18) == $delta
        && isset($search_details['create_block']) && $search_details['create_block'] == 1) {
        $title = isset($search_details['custom_results_title']) ? $search_details['custom_results_title'] : t('Custom Search');
        $content = edan_custom_search_menupage_results($slug);
      }
    }
  }

  return array(
    'subject' => t($title),
    'content' => $content
  );
}

function edan_custom_search_blocks_facets($delta = '') {
  if (!isset($GLOBALS['edan_search']['facet_block_content'])) {
    return array();
  }

  return array(
    'subject' => 'Facets',
    'content' => $GLOBALS['edan_search']['facet_block_content']
  );
}