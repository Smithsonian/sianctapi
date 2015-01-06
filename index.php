<?php

define('SIANCT_TRUSTED_REQUEST', FALSE);

//-- Configuration:

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
    'args' => array('xslt'),
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

  'sianctapi/getSpecies' => array(
    'library' => 'SIANCTAPI',
    'controller' => 'sianctapiGetSpecies',
    'method' => '_POST',
    'args' => array('pids'),
  ),

  'sianctapi/getAllSpeciesNamesCached' => array(
    'library' => 'SIANCTAPI',
    'controller' => 'sianctapiGetAllSpeciesNamesCached',
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
);



//-- Execution:

// Get the request -- taken from Drupal's request_path, can be replaced with a framework
// router/front contoller
$path = '';
if (isset($_SERVER['REQUEST_URI'])) {
  // This request is either a clean URL, or 'index.php', or nonsense.
  // Extract the path from REQUEST_URI.
  $request_path = strtok($_SERVER['REQUEST_URI'], '?');
  $base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));

  // Unescape and strip $base_path prefix, leaving q without a leading slash.
  $path = substr(urldecode($request_path), $base_path_len + 1);

  // If the path equals the script filename, either because 'index.php' was
  // explicitly provided in the URL, or because the server added it to
  // $_SERVER['REQUEST_URI'] even when it wasn't provided in the URL (some
  // versions of Microsoft IIS do this), the front page should be served.
  if ($path == basename($_SERVER['PHP_SELF'])) {
    $path = '';
  }

  $path = trim($path, '/');
}

// Routes
if (!isset($routes[$path])) {
  // FAIL: 404
  exit( http_response_codeREAL(404) );
} else {
  $route = $routes[$path];
}

// Process request
try {
  // Include the routes library or throw a 500 if the libaray isn't available
  $object = _factory($route['library']);

  // Does the controller work?
  if (!method_exists($object, $route['controller'])) {
    throw new Exception('Invalid controller.');
  }

  // Fail if sha1 is unavailable
  if (!function_exists('sha1')) {
    throw new Exception('hash alg. unavailable.');
  }

  // Default to _GET unless _POST
  if (!isset($route['method']) || $route['method'] != '_POST') {
    $route['method'] = '_GET';
  }

  // Gather the args
  $uri = '';
  $meth = $route['method'];

  // I did not want to refactor the API (SIANCT.php) library so I didn't change the
  // methods to use arrays as params, so this is really low tech, but based on $index in
  // the following loop this will create variables $var{1-10} and pass them in order.
  // TODO: Update the library so this workaround isn't necessary
  $var1 = $var2 = $var3 = $var4 = $var5 = $var6 = $var7 = $var8 = $var9 = $var10 = NULL;

  foreach ($route['args'] as $index => $arg) {
    // Get the variable if it exists
    $value = _get_value($arg, $meth);

    if (isset($value)) {
      $uri .= (!empty($uri)) ? '&' : '';
      $uri .= $arg . '=' . $value;

      // See note above about this:
      $var = 'var'. ($index + 1);
      $$var = $value;
    } else {
      $uri .= (!empty($uri)) ? '&' : '';
      $uri .= $arg . '=';
    }
  }

  // Validate the key

  // We're dealing with signed requests now, check:
  // X-AppId -- assigned by keys.config
  // X-RequestDate -- not used
  // X-Nonce -- string
  // X-AuthContent -- string = nonce + uri + date + secret key
  if (!defined('SIANCT_TRUSTED_REQUEST') || SIANCT_TRUSTED_REQUEST !== TRUE) {
    $auth_content = $_SERVER['HTTP_X_AUTHCONTENT'];

    $ipnonce = $_SERVER['HTTP_X_NONCE'];
    $app_id = $_SERVER['HTTP_X_APPID'];
    $date = $_SERVER['HTTP_X_REQUESTDATE']; // Could check dates too, EDAN does not so we're not
    $secret_key = _get_secret_key($app_id, './keys.config');

    $auth_check1 = "{$ipnonce}\n{$uri}\n{$date}\n{$secret_key}";
    $auth_check = base64_encode(sha1($auth_check1));

    if (TRUE !== _hash_compare($auth_content, $auth_check)) {
      // FAIL: 403
      // Debug
      /**
      echo "\n<p>One: " . var_export($auth_content, TRUE);
      echo "\n<p>Two: " . var_export($auth_check, TRUE);
      echo "\n<p>URI: " . var_export($uri, TRUE);
      echo "\n<p>AC: " . var_export($auth_check1, TRUE);
      echo "\n<p>SK: " . var_export($secret_key, TRUE);
      /**/
      exit( http_response_codeREAL(403) );
    }
  } else {
    $app_id = $_SERVER['HTTP_X_APPID'];
  }

  // Make a new sianct object to perform the request
  #$sianct = new SIANCT('./api.config', $app_id);
  $object = _factory($route['library'], './api.config', $app_id);

  // Perform the request
  $func = $route['controller'];

  // See note above about the args workaround
  exit( $object->$func($var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10) );
} catch (Exception $e) {
  // FAIL: 500
  // Debug:
  /*
  echo "\n<br> Ex: " . $e;
  echo "\n<br> Path: <pre>" . $path . '</pre>';
  echo "\n<br> routes: <pre>" . var_export($routes, TRUE) . '</pre>';
  echo "\n<br> route: <pre>" . var_export($route, TRUE) . '</pre>';
  */
  exit( http_response_codeREAL(500) );
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
