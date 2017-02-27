<?php
function _get_routes() {
  $routes = array(
    // SIANCTAPI
    'sianctapi/getFile' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetFile',
      'method' => '_GET',
      'args' => array('filepath'),
    ),

    'sianctapi/getProjectStructure' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetProjectStructure',
      'method' => '_GET',
      'args' => array('xslt', 'wt'),
    ),

    'sianctapi/getProjectStructureMetadata' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetProjectStructureMetadata',
      'method' => '_GET',
      'args' => array('params'),
    ),

    'sianctapi/getProjectStructureCached' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetProjectStructureCached',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/getSelectedObservations' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetSelectedObservations',
      'method' => '_GET',
      'args' => array('pids', 'species'),
    ),

    'sianctapi/getSelectedObservationsCSV' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetSelectedObservationsCSV',
      'method' => '_POST',
      'args' => array('pids', 'species'),
    ),

    'sianctapi/getSelectedObservationsPost' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetSelectedObservations',
      'method' => '_POST',
      'args' => array('pids', 'species'),
    ),

    'sianctapi/getSpecies' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetSpecies',
      'method' => '_POST',
      'args' => array('pids'),
    ),

    'sianctapi/getSpeciesJSON' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetSpeciesJSON',
      'method' => '_POST',
      'args' => array('pids'),
    ),

    'sianctapi/getAllSpeciesNamesCached' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetAllSpeciesNamesCached',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/getAllSpeciesNamesCachedJSON' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetAllSpeciesNamesCachedJSON',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/getAllObstablePids' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetAllObstablePids',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/runWorkflow' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiRunWorkflow',
      'method' => '_POST',
      'args' => array('workflowName', 'pids', 'species', 'resultFileExt'),
    ),

    'sianctapi/selectObstables' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiSelectObstables',
      'method' => '_GET',
      'args' => array('query', 'xslt'),
    ),

    'sianctapi/cacheRefresh' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiCacheRefresh',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/cacheCheck' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiCacheCheck',
      'method' => '_GET',
      'args' => array(),
    ),

    'sianctapi/getModulePath' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiGetModulePath',
      'method' => '_GET',
      'args' => array('moduleName'),
    ),

    'sianctapi/download' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'sianctapiDownload',
      'method' => '_GET',
      'args' => array('file'),
    ),

    'sianctapi/getManifestIdentifications' => array(
      'library' => 'SIANCTAPI',
      'controller' => 'getManifestIdentifications',
      'method' => '_POST',
      'args' => array('ctPid'),
    ),

    'edan/getImageSequence' => array(
      'library' => 'EDAN_SIANCTAPI',
      'controller' => 'getImageSequence',
      'method' => '_GET',
      'args' => array('type', 'id'),
    ),
  );
  return $routes;
}

//-- "Framework":

/**
 * Returns an object. Includes necessary lib.
 *
 * @return object reference
 */
function &_factory($class_name, $cfg = null, $app_id = null) {
  if (empty($class_name) || !is_string($class_name)) {
    throw new Exception('Invalid class name specified: ' . var_export($class_name, TRUE) . '.');
  }

  require_once($class_name . '.php');

  // TODO: Clean up arg handling
  if (class_exists($class_name)) {
    if (!empty($cfg) && !empty($app_id)) {
      $config = _get_configuration($cfg, $class_name);
      $obj = new $class_name($config, $app_id);
    } else if (!empty($cfg)) {
      $config = _get_configuration($cfg, $class_name);
      $obj =  new $class_name($config);
    } else {
      $obj =  new $class_name;
    }

    return $obj;
  }

  throw new Exception('Could not load class ' . $class_name . '.');
}

function _get_value($element, $method = '_GET') {
  switch ($method) {
    case '_GET':
      return (isset($_GET[$element])) ? $_GET[$element] : NULL;
      break;
    case '_POST':
      return (isset($_POST[$element])) ? $_POST[$element] : NULL;
      break;
    default:
      return NULL;
  }
}

/**
 * Returns a configuration array, empty if there was an error reading JSON or if the
 * specifified lib was invalid.
 *
 * @return array
 */
function _get_configuration($cfg_file = '', $lib = '') {
  $config = array();

  // Read in the config and parse to array
  if (!empty($cfg_file) && is_file($cfg_file)) {
    $config = json_decode(file_get_contents($cfg_file), TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      $config = array();
    } else if (!empty($lib)) {
      $config = (isset($config[$lib])) ? $config[$lib] : array();
    }
  }

  return $config;
}

/**
 * Returns the secret key for an AppID. False on error.
 *
 * @return mixed
 *   String containing the key or FALSE on error.
 */
function _get_secret_key($app_id = '', $key_file = '') {
  $ret = FALSE;

  if (!empty($app_id) && !empty($key_file) && is_file($key_file)) {
    $keys = json_decode(file_get_contents($key_file), TRUE);
    if (JSON_ERROR_NONE === json_last_error()) {
      if (isset($keys[$app_id]) && !empty($keys[$app_id])) {
        $ret = $keys[$app_id];
      }
    }
  }

  return $ret;
}

/**
 * Support function to facilitate comparison and an attempt to mitagate timing attacks.
 *
 * @return bool
 */
function _hash_compare($a, $b) {
  if (!is_string($a) || !is_string($b)) {
    return FALSE;
  }

  $len = strlen($a);
  if ($len !== strlen($b)) {
    return FALSE;
  }

  $status = 0;
  for ($i = 0; $i < $len; $i++) {
    $status |= ord($a[$i]) ^ ord($b[$i]);
  }

  return $status === 0;
}

/**
 * Sets the request header and returns the code as an int. Exits with a 500 on failure.
 *
 * @return int
 */
#if (!function_exists('http_response_code')) {
  function http_response_codeREAL($code = NULL) {
    if ($code !== NULL) {
      switch ($code) {
        case 100: $text = 'Continue'; break;
        case 101: $text = 'Switching Protocols'; break;
        case 200: $text = 'OK'; break;
        case 201: $text = 'Created'; break;
        case 202: $text = 'Accepted'; break;
        case 203: $text = 'Non-Authoritative Information'; break;
        case 204: $text = 'No Content'; break;
        case 205: $text = 'Reset Content'; break;
        case 206: $text = 'Partial Content'; break;
        case 300: $text = 'Multiple Choices'; break;
        case 301: $text = 'Moved Permanently'; break;
        case 302: $text = 'Moved Temporarily'; break;
        case 303: $text = 'See Other'; break;
        case 304: $text = 'Not Modified'; break;
        case 305: $text = 'Use Proxy'; break;
        case 400: $text = 'Bad Request'; break;
        case 401: $text = 'Unauthorized'; break;
        case 402: $text = 'Payment Required'; break;
        case 403: $text = 'Forbidden'; break;
        case 404: $text = 'Not Found'; break;
        case 405: $text = 'Method Not Allowed'; break;
        case 406: $text = 'Not Acceptable'; break;
        case 407: $text = 'Proxy Authentication Required'; break;
        case 408: $text = 'Request Time-out'; break;
        case 409: $text = 'Conflict'; break;
        case 410: $text = 'Gone'; break;
        case 411: $text = 'Length Required'; break;
        case 412: $text = 'Precondition Failed'; break;
        case 413: $text = 'Request Entity Too Large'; break;
        case 414: $text = 'Request-URI Too Large'; break;
        case 415: $text = 'Unsupported Media Type'; break;
        case 500: $text = 'Internal Server Error'; break;
        case 501: $text = 'Not Implemented'; break;
        case 502: $text = 'Bad Gateway'; break;
        case 503: $text = 'Service Unavailable'; break;
        case 504: $text = 'Gateway Time-out'; break;
        case 505: $text = 'HTTP Version not supported'; break;
        default:
          exit( http_response_codeREAL(500) );
        break;
      }

      $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
      header($protocol . ' ' . $code . ' ' . $text);
      $GLOBALS['http_response_code'] = $code;
    } else {
      $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
    }

    return $code;
  }
#}
