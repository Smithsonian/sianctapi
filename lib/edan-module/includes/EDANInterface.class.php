<?php

/**
 * EDANInterface
 *
 * @version 1.0 MDS original version minus Drupal-centric code.
 */

  namespace EDAN {

    class EDANInterface {
      // this class actually generates the call to the API

      private $server;
      private $app_id;
      private $edan_key;

      /**
       * int
       * 0 for unsigned/trusted/T1 requests;
       * 1 for signed/T2 requests;
       * 2 for password based (unused)
       */
      private $auth_type = 1;
      /**
       * Bool tracks whether the request was successful based on response header (200 = success)
       */
      private $valid_request = FALSE;
      public $result_format = 'json';
      private $results;

      /**
       * Constructor
       */
      public function __construct($server, $app_id, $edan_key, $auth_type = 1) {
        $this->server = $server;
        $this->app_id = $app_id;
        $this->edan_key = $edan_key;

        // Normalize, but don't cast
        if ($auth_type == 0 || $auth_type == '0') {
          $this->auth_type = 0;
        }

        $this->result_format = 'json';
        $this->valid_request = FALSE;
      }

      /**
       * Creates the header for the request to EDAN. Takes $uri, prepends a nonce, and appends
       * the date and appID key. Hashes as sha1() and base64_encode() the result.
       * @param uri The URI (string) to be hashed and encoded.
       * @returns Array containing all the elements and signed header value
       */
      private function encodeHeader($uri) {
        $ipnonce = $this->get_nonce(); // Alternatively you could do: get_nonce(8, '-'.get_nonce(8));
        $date = date('Y-m-d H:i:s');

        $return = array(
          'X-AppId: ' . $this->app_id,
          'X-RequestDate: ' . $date,
          'X-AppVersion: ' . 'EDANInterface-0.10.1'
        );

        // For signed/T2 requests
        if ($this->auth_type === 1) {
          $auth = "{$ipnonce}\n{$uri}\n{$date}\n{$this->edan_key}";
          $content = base64_encode(sha1($auth));
          $return[] = 'X-Nonce: ' . $ipnonce;
          $return[] = 'X-AuthContent: ' . $content;
        }

        return $return;
      }

      /**
       * Perform a curl request
       * @param args An associative array that can contain {q,fq,rows,start}
       * @param service The service name you are curling {metadataService,tagService,collectService}
       * @param POST boolean, defaults to false; on true $uri sent CURLOPT_POSTFIELDS
       * @param info reference, if passed will be set with the output of curl_getinfo
     */
      public function sendRequest($uri, $service, $POST = FALSE, &$info) {

        // Hash the request for tracking/profiling/caching
        $hash = md5($uri . $service . $POST);

        /*
        if (isset($GLOBALS['edan_hashes'][$hash])) {
          if ('EDAN_CONNECTION_PROFILE' == TRUE)  {
            $GLOBALS['edan_connections'][] = array(
              'request' => $service . '?' . $uri,
              'info' => array(
                'total_time' => 'cached',
                'namelookup_time' => 0,
                'connect_time' => 0,
                'pretransfer_time' => 0,
              )
            );
          }
          return $GLOBALS['edan_hashes'][$hash];
        }
        */
//dpm($this->server . $service . '?' . $uri);
//return array();
      $ch = curl_init();
      if ($POST === TRUE) {
        curl_setopt($ch, CURLOPT_URL, $this->server . $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $uri);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "encodedRequest=" . base64_encode($uri));
      } else {
        curl_setopt($ch, CURLOPT_URL, $this->server . $service . '?' . $uri);
      }

      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $this->encodeHeader($uri));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

      $response = curl_exec($ch);
      $info = curl_getinfo($ch);

    // Record this request for analysis
        /*
    if (EDAN_CONNECTION_PROFILE == TRUE)  {
      $GLOBALS['edan_connections'][] = array(
        'request' => $service . '?' . $uri,
        'info' => $info
      );
    }
        */

        if ($info['http_code'] == 200) {
          $this->valid_request = TRUE;
        } else {
          $this->valid_request = FALSE;
        }

        curl_close($ch);

        $GLOBALS['edan_hashes'][$hash] = $response;

        return $response;
      }

      /**
       * Generates a nonce.
       *
       * @param int $length
       *   (optional) Int representing the length of the random string.
       * @param string $prefix
       *   (optional) String containing a prefix to be prepended to the random string.
       *
       * @return string
       *   Returns a string containing a randomized set of letters and numbers $length long
       *   with $prefix prepended.
       */
      private function get_nonce($length = 15, $prefix = '') {
        $password = "";
        $possible = "0123456789abcdefghijklmnopqrstuvwxyz";

        $i = 0;

        while ($i < $length) {
          $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

          if (!strstr($password, $char)) {
            $password .= $char;
            $i++;
          }
        }

        return $prefix.$password;
      }

    }

    abstract class EDANBase {
      // basic properties, extended by OGMT and other classes

      protected $edan_connection;
      protected $errors;
      protected $results_raw;
      protected $results_json;
      protected $results_info;

      public function setConnection(Connection &$edan_connection) {
        $this->edan_connection = $edan_connection;
      }

      public function getResultsRaw() {
        return $this->results_raw;
      }

      public function getResultsJSON() {
        return $this->results_json;
      }

      public function getResultsInfo() {
        return $this->results_info;
      }

      public function getErrors() {
        return $this->errors;
      }

    } // EDANBase

    class Connection extends EDANBase {
      // so we don't have to continually pass connection params to EDANObjectGroup objects-
      // we can just create one EDANConnection and pass this connection object when making calls

      private $edan_app_id;
      private $edan_auth_key;
      private $edan_server;
      private $edan_tier_type;

      private $is_valid = FALSE;

      private function _check_server($server) {
        $ok = true;
        //@todo check format of $server

        return $ok;
      }

      private function _check_tier_type($tier_type) {
        $ok = false;

        if(NULL !== $tier_type && ($tier_type == 1 || $tier_type == 2)) {
          $ok = true;
        }

        return $ok;
      }

      private function _check_app_id($app_id) {
        $ok = true;
        //@todo check app_id for format?

        return $ok;
      }

      private function _check_auth_key($auth_key) {
        $ok = true;
        //@todo - any regex we can use to check auth key?

        return $ok;
      }

      public function __construct( $edan_server, $edan_tier_type, $edan_app_id, $edan_auth_key) {

        $this->edan_connection = NULL;
        $this->edan_server = NULL;
        $this->edan_app_id = NULL;
        $this->edan_auth_key = NULL;
        $this->edan_tier_type = NULL;
        $this->errors = array();

        $this->results_raw = NULL;
        $this->results_json = NULL;
        $this->results_info = NULL;

        // check each incoming to make sure it is acceptable
        if($this->_check_server($edan_server)) {
          $this->edan_server = $edan_server;
        }
        else {
          $this->errors[] = "Format is invalid for EDAN Server: '" . $edan_server. "'." ;
        }

        if($this->_check_tier_type($edan_tier_type)) {
          $this->edan_tier_type = $edan_tier_type;
        }
        else {
          $this->errors[] = "EDAN Tier Type is invalid: '" . $edan_tier_type. "'." ;
        }

        if($this->_check_app_id($edan_app_id)) {
          $this->edan_app_id = $edan_app_id;
        }
        else {
          $this->errors[] = "EDAN App ID is invalid: '" . $edan_app_id. "'." ;
        }

        if($this->_check_auth_key($edan_auth_key)) {
          $this->edan_auth_key = $edan_auth_key;
        }
        else {
          $this->errors[] = "EDAN Auth Key is invalid: '" . $edan_auth_key. "'." ;
        }

        if(NULL == $this->errors || count($this->errors) == 0) {
          $this->is_valid = TRUE;
        }

      }

      /**
       * Wrapper function to get data from EDAN.
       * Returns an array containing JSON data. False on error.
       */
      public function callEDAN($service, $params = NULL, $post = FALSE) {

        $this->results_raw = NULL;
        $this->results_json = NULL;
        $this->results_info = NULL;
        $this->errors = array();

        if(!$this->is_valid) {
          $this->errors[] = "Can't call EDAN. EDAN connection is not valid.";
          return FALSE;
        }

        if(NULL !== $params) {
          // RB 20161111 - AG says do not pass applicationId
          /*
          if(!array_key_exists('applicationId', $params)) {
            array_unshift($params, array('applicationId' => $this->edan_app_id));
          }
          */
          $uri = local_http_build_query($params);
        }
        /*elseif(count($params) == 0) {
          $params = array('applicationId' => $this->edan_app_id);
          $uri = local_http_build_query($params);
        }*/
        else {
          $uri = '';
        }

        $hash = md5($uri . $service);

        $edan = new EDANInterface($this->edan_server, $this->edan_app_id, $this->edan_auth_key, $this->edan_tier_type);

        $info = '';
        $results = $edan->sendRequest($uri, $service, $post, $info);
        $this->results_raw = $results;
        $this->results_info = $info;

//dpm($service . '?' . $uri);
//dpm($results);
/*
dpm("---------------");
if($service != 'ogmt/v1.1/ogmt/objectgroups.htm'
  && $service != 'ogmt/v1.1/adminogmt/getObjectGroups.htm'
  && $service != 'ogmt/v1.1/adminogmt/objectListingMetadata.htm'
  && $service != 'ogmt/v1.1/adminogmt/objectgroups.htm') {
    dpm($results);
}
        dpm("INFO:");
        dpm($info);
*/

        if ((!is_array($info) && strlen(trim($info)) == 0) || !array_key_exists('http_code', $info)) {
          $this->errors[] = "EDAN did not return a HTTP code.";
          return FALSE;
        }
        elseif ($info['http_code'] != 200) {
          $this->errors[] = "EDAN returned an unsuccessful HTTP code: " . $info['http_code'];
          return FALSE;
        }

        if (strlen($results) > 0) {
          try {
            $return = json_decode($results, TRUE);
            $this->results_json = $return;
//dpm($return);

            $GLOBALS['ogmt_admin']['edan_request_cache'][$hash] = $return;
            return TRUE;
          }
          catch(Exception $ex) {
            $this->errors[] = "An error occurred decoding results: " . $ex->getMessage();
            return FALSE;
          }
        }
        else {
          $this->errors[] = "EDAN returned no results.";
          return FALSE;
        }

      } // callEDAN

      public function getAppId() {
        $app_id = '';
        if(NULL !== $this->edan_app_id) {
          $app_id = $this->edan_app_id;
        }

        return $app_id;
      }

      public function isValid() {
        return $this->is_valid;
      }

    } // Connection

    // thank you Drupal
    // drupal_http_build_query()
    // https://api.drupal.org/api/drupal/includes!common.inc/function/drupal_http_build_query/7
    function local_http_build_query(array $query, $parent = '') {
      $params = array();

      foreach ($query as $key => $value) {
        $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));

        // Recurse into children.
        if (is_array($value)) {
          $params[] = local_http_build_query($value, $key);
        }
        // If a query parameter value is NULL, only append its key.
        elseif (!isset($value)) {
          $params[] = $key;
        }
        else {
          // For better readability of paths in query strings, we decode slashes.
          $params[] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
        }
      }

      return implode('&', $params);
    }

  } // namespace EDAN

?>