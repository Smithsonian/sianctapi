<?php

function edan_search_blocks_search_form($delta = '') {
  return array(
    'subject' => 'Search Collections',
    'content' => drupal_get_form('edan_search_search_form')
  );
}

function edan_search_blocks_facets($delta = '') {
  if (!isset($GLOBALS['edan_search']['facet_block_content'])) {
    return array();
  }

  return array(
    'subject' => 'Facets',
    'content' => $GLOBALS['edan_search']['facet_block_content']
  );
}