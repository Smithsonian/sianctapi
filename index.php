<?php
require_once('./util.php');
define('SIANCT_TRUSTED_REQUEST', FALSE);
//define('SIANCT_TRUSTED_REQUEST', TRUE);

//-- Configuration:

$routes = _get_routes();

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

    $datestamp = date('r');
    $logfp = fopen('/tmp/sianctapi-hp.log', 'a');
    $output = "========== $datestamp ==========
    APPID: $app_id
    SECRET_KEY: $secret_key
    IPNONCE: $ipnonce
    URI: $uri
    AUTH_CONTENT: $auth_content

    ***** \$_SERVER *****\n" .
    print_r($_SERVER, true) . "\n
    ====================
    ====================\n\n
    ";
    fwrite($logfp, $output);
    //fwrite($logfp, "\n[$datestamp] $app_id $ipnonce $secret_key $date $uri $auth_content");
    fclose($logfp);
    
    // We have to build the hash differently, accepting the parameters passed to the
    // API in the order they were passed.
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      if (stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === FALSE) {
        $qry = file_get_contents("php://input");
      } else {
        // Flatten $_POST because php://input does not support muti-part enctype
        $qry = urldecode(http_build_query($_POST));
      }
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $qry = $_SERVER['QUERY_STRING'];
    } else {
      $qry = FALSE;
    } 

    $datestamp = date('r');
    $logfp = fopen('/tmp/sianctapi-hp.log', 'a');
    $output = "\$qry:\n$qry\n";
    fwrite($logfp, $output);
    fclose($logfp);

    $auth_check1 = "{$ipnonce}\n{$qry}\n{$date}\n{$secret_key}";
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


