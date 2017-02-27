<?php
require_once(drupal_get_path('module', 'edan') . '/includes/EDANSearch.class.php');

/**
 * This is a catch-all function to implement calls not yet implemented?
 */
function _edan_call_edan($what = '', $params = array(), $uri = '') {
  $edan_server = variable_get('edan_server_url', 'http://edan.si.edu/');
  $edan_auth_key = variable_get('edan_auth_key', '');
  $edan_tier_type = variable_get('edan_tier_type', '0');
  $edan_app_id = variable_get('edan_app_id', '');

  $uri = '';
  //$content_service_base = 'content/v1.1/'; //http://edantest.si.edu/content/v1.1/fileupload/*.htm
  #$params['applicationId'] = $edan_app_id;

  switch($what) {
    case 'contentUrl':
      /*
      type
      url
      */
      $service = 'metadata/v1.1/metadata/search.htm'; // $content_service_base . 'content/contentUrl.htm';
      $uri = drupal_http_build_query($params);
      break;

    # for test case
    case 'objectlists':
      /*
      listName
      */
      $service = 'metadata/v1.1/metadata/getObjectLists.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'getSchema':
      $service = 'schema/v1.1/schema/getSchema.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'getContent':
      $service = 'content/v1.1/content/getContent.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'getContentRecords':
      $service = 'content/v1.1/admincontent/getContentRows.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'createContent':
      $service = 'content/v1.1/admincontent/createContent.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'editContent':
      $service = 'content/v1.1/admincontent/editContent.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'releaseContent':
      $service = 'content/v1.1/admincontent/releaseContent.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    case 'validate':
      $service = 'schema/v1.1/schema/validate.htm';
      // RB 20161111 - AG says do not pass applicationId
      //$params['applicationId'] = $edan_app_id;
      $uri = drupal_http_build_query($params);
      break;

    default:
      return FALSE;
  }

  $edan = new EDAN\EDANInterface($edan_server, $edan_app_id, $edan_auth_key, $edan_tier_type);

  $info = '';
  $results = $edan->sendRequest($uri, $service, FALSE, $info);

  if(module_exists('devel') && isset($_GET['dpm']) && user_access('access devel information')) {
    dpm($info);
    dpm($results);
  }
  if (isset($_GET['dump'])) {
    drupal_set_message('<pre>' . var_export($info, TRUE) . '</pre>');
    drupal_set_message('<pre>' . var_export($results, TRUE) . '</pre>');
  }

  if ($info['http_code'] != 200) {
    drupal_set_message('An error occurred when contacting the EDAN API. (' . $info['http_code'] . ').');
    watchdog('EDAN',
      'HTTP Error %err: %service?%uri <br /><br />Results<br />%results',
      array(
        '%err' => $info['http_code'],
        '%service' => $service,
        '%uri' => urldecode($uri),
        '%results' => $results,
      ),
      WATCHDOG_WARNING);
    return FALSE;
  }

  if (empty($results)) {
    return FALSE;
  } else if (is_string($results)) {
    return json_decode($results, TRUE);
  } else {
    return FALSE;
  }
}

/**
 * Get Unit Code for App ID
 *
 * Get the unit code corresponding to an EDAN application ID.
 *
 * @param string  $app_id The application id
 * @return  array  The Smithsonian unit code or codes corresponding to the specified app id.
 */
function _get_unitCodes_from_applicationId($app_id) {

  // default: assume they match
  $unit_codes = array($app_id);

  // test credentials
  if($app_id == 'QUOTIENTPROD') {
    $unit_codes = array('QUOTIENT');
  }
  elseif($app_id == 'OCIO3D') {
    $unit_codes = array('SI');
  }
  elseif($app_id == 'EDAN2_BASE') {
    $unit_codes = array('EDAN2_BASE');
  }
  else {
    // see if EDAN has a si-unit record that corresponds

    $edan_server = variable_get('edan_server_url', 'http://edan.si.edu/');
    $edan_auth_key = variable_get('edan_auth_key', '');
    $edan_tier_type = variable_get('edan_tier_type', '0');
    $edan_app_id = variable_get('edan_app_id', '');

    $ei = new \EDAN\EDANInterface($edan_server, $edan_app_id, $edan_auth_key, $edan_tier_type);
    $search = new \EDAN\EDANSearch($ei);
    $args = array (
      'q' => '*:*',
      'fq' => array(
        'type:si-unit',
        'p.si-unit.app_ids:' . $app_id,
      )
    );
    $result = $search->executeSearch($args);
    $search->info['url'];

    if(is_array($result)) {
      if(!$search->is_valid || array_key_exists('error', $result)) {
        $error = array_key_exists('error', $result) ? $result['error'] : 'Unknown error. Check EDAN Connection settings.';
        watchdog('EDAN Debug',
          '%service <br /><br />ERROR<br />%results',
          array(
            '%service' => $search->info['url'],
            '%results' => $error,
          ),
          WATCHDOG_ERROR);
      }
      elseif (array_key_exists('rows', $result) && isset($result['rows'][0])) {
        // look for it...
        $unit_codes = $result['rows'][0]['content']['unit_codes'];
      }
    }

  }

  /* Was:
  // unit codes (keys) mapped to application ids
  $a_uc_mapping = array(
    'AAA' => 'AAADCD',
    'EDAN2BASE' => 'EDAN2_BASE',
    'SIEDU' => 'SIEDU',
    'SIMUSIC' => 'SIMUSIC',
    'NAAOGMT' => 'NAA',
    'NASM' => 'NASM',
    'NMAAHCPROD' => 'NMAAHC',
    'NMAH' => 'NMAH',
    'NMAICOLLECTIONS' => 'NMAI',
    'NPGOGMT' => 'NPG',
    'OCIO3D' => 'SI',
    'QUOTIENTPROD' => 'QUOTIENT',
    'SIEDU' => 'SIEDU'
  );
  $unitCode = array_key_exists($app_id, $a_uc_mapping) ? $a_uc_mapping[$app_id] : NULL;
  */

  return $unit_codes;

}


/**
 * Get Application ID for Unit Code
 *
 * Get the EDAN Application ID corresponding to a unit code.
 *
 * @param string  $unit_code The unit code used to tag data in EDAN
 * @return  string  The EDAN Application ID corresponding to the specified unit code.
 */
function _get_applicationId_from_unitCode($unit_code) {

  // default: assume they match
  $app_id = $unit_code;

  // test credentials
  if($unit_code == 'QUOTIENT') {
    $app_id = 'QUOTIENTPROD';
  }
  elseif($unit_code == 'SI') {
    //@todo really? does SI only pertain to 3DSI?
    $app_id = 'OCIO3D';
  }
  else {
    // see if EDAN has a si-unit record that corresponds
    $edan_server = variable_get('edan_server_url', 'http://edan.si.edu/');
    $edan_auth_key = variable_get('edan_auth_key', '');
    $edan_tier_type = variable_get('edan_tier_type', '0');
    $edan_app_id = variable_get('edan_app_id', '');

    $ei = new \EDAN\EDANInterface($edan_server, $edan_app_id, $edan_auth_key, $edan_tier_type);
    $search = new \EDAN\EDANSearch($ei);
    $args = array (
      'q' => '*:*',
      'fq' => array(
        'type:si-unit',
        'p.si-unit.unit_codes:' . $unit_code,
      )
    );
    $result = $search->executeSearch($args);
    $search->info['url'];

    if(is_array($result)) {
      if(!$search->is_valid || array_key_exists('error', $result)) {
        $error = array_key_exists('error', $result) ? $result['error'] : 'Unknown error. Check EDAN Connection settings.';
        watchdog('EDAN Debug',
          '%service <br /><br />ERROR<br />%results',
          array(
            '%service' => $search->info['url'],
            '%results' => $error,
          ),
          WATCHDOG_ERROR);
      }
      elseif (array_key_exists('rows', $result) && isset($result['rows'][0])) {
        // look for it...
        $app_ids = $result['rows'][0]['content']['app_ids'];
        $app_id = array_key_exists(0, $app_ids) ? $app_ids[0] : $unit_code;
      }
    }

  }

  /* Was:
  // unit codes (keys) mapped to application ids
  $uc_a_mapping = array(
    'AAADCD' => 'AAA',
    'EDAN2_BASE' => 'EDAN2BASE',
    'NAA' => 'NAAOGMT',
    'NASM' => 'NASM',
    'NMAH' => 'NMAH',
    'NMAAHC' => 'NMAAHCPROD',
    'NMAI' => 'NMAICOLLECTIONS',
    'NPG' => 'NPGOGMT',
    'QUOTIENT' => 'QUOTIENTPROD',
    'SI' => 'OCIO3D',
    'SIEDU' => 'SIEDU',
    'SIMUSIC' => 'SIMUSIC'
  );

  $appId = array_key_exists($unit_code, $uc_a_mapping) ? $uc_a_mapping[$unit_code] : NULL;
  */

  return $app_id;

}