<?php
/*
 Functions to create and expose tokens
 useful: https://www.drupal.org/node/1308488
*/

/**
 * Implements hook_token_info(). This hook will register tokens schema type, id, url, title
 */
function edan_record_admin_token_info() {
  $info['types']['edan'] = array(
    'name' => t('EDAN content'),
    'description' => t('Tokens related to EDAN content.'),
  );
  $info['tokens']['edan']['edan_type'] = array(
    'name' => t('EDAN Type'),
    'description' => t('EDAN record type, such as concept or event'),
  );
  $info['tokens']['edan']['edan_id'] = array(
    'name' => t('EDAN ID'),
    'description' => t('ID of the EDAN content'),
  );
  $info['tokens']['edan']['url'] = array(
    'name' => t('URL'),
    'description' => t('URL of the EDAN content'),
  );
  $info['tokens']['edan']['title'] = array(
    'name' => t('Title'),
    'description' => t('Title of the EDAN content'),
  );
  return $info;
}

/**
 * Implements hook_tokens(). This does the actual replacement of values.
 *
 */
function edan_record_admin_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  $sanitize = !empty($options['sanitize']);

  // EDAN content tokens.
  if ($type == 'edan') { // && !empty($data['edan'])) {
    $edan_data = $data; // $data['edan'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'edan_type':
        case 'edan_id':
        case 'url':
        case 'title':
          $custom_token = $edan_data[$name];
          $replacements[$original] = str_replace(
            array('&', '&'),
            'and',
            $sanitize ? filter_xss($custom_token) : $custom_token
          );
          break;
        default:
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Example- use the token_replace() function to get the actual content after replacement.
 */
function _edan_record_admin_example_getmenupath() {
  $edanvars = array();
  $edanvars['edan_type'] = 'edan-type';
  $edanvars['edan_id'] = 'edanid';
  $edanvars['title'] = 'title!';
  $edanvars['url'] = 'u/r/l.com';
  $temp = "Example: [edan:edan_id] [edan:edan_type] [edan:url] [edan:title]";
  $temp = token_replace($temp, $edanvars);
  //dpm($temp);
}
