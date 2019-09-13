<?php
  require("sianct_mysql_populator.php");
  require 'vendor/autoload.php';

  /**
   * sianct_mysql_rebuild.php script can be run from the command line with various optional parameters:
   *
   * -d | --database : Specifies database information. Format - host:username:password:databasename
   * -f | --file     : Specifies path to a file containing a list of fedora pids populate in the database
   * -p | --pids     : A list of pids separated by commas. Format - test.pid:1,test.pid:2,test.pid:3 (no spaces)
   * -l | --debug    : Specifies whether a debug file should be output during execution. Format - TRUE:FALSE
   * -r | --rebuild  : If rebuild option flag set to true, the whole database is dropped and rebuilt. Format - TRUE:FALSE
   */

  $shortopts  = "";
  $shortopts .= "d:";
  $shortopts .= "f:";
  $shortopts .= "p:";
  $shortopts .= "l:";
  $shortopts .= "r:";

  $longopts  = array(
      "database:",
      "file:",
      "pids:",
      "debug:",
      "rebuild:"
  );

  //Extract option values (shortopts take prescedent)
  $options = parseOptions(getopt($shortopts, $longopts));

  //Extract database values
  $db = getDatabaseValues($options['database']);

  //parse rebuild and debug flags as booleans
  $rebuild = filter_var($options['rebuild'], FILTER_VALIDATE_BOOLEAN);
  $debug = filter_var($options['debug'], FILTER_VALIDATE_BOOLEAN);

  //extract pids from file and user list and merge into a single array of unique pid values.
  $pids = getPids($options['file'], $options['pids']);

  //initialize new migrator object with $db information
  $populator = new sianct_mysql_populator($db);
  //populate database using options parameters
  $populator->populateDatabase(NULL, $rebuild);

  /**
   * Retrieve user commandline options values
   * @param  array $opts array of options data
   * @return array       array of parsed values
   */
  function parseOptions($opts)
  {
    $params = Array(
      'database' => NULL,
      'file' => NULL,
      'pids' => NULL,
      'debug' => FALSE,
      'rebuild' => FALSE
    );

    if($opts['d'])
    {
      $params['database'] = $opts['d'];
    }
    elseif($opts['database'])
    {
      $params['database'] = $opts['database'];
    }

    if($opts['f'])
    {
      $params['file'] = $opts['f'];
    }
    elseif($opts['file'])
    {
      $params['file'] = $opts['file'];
    }

    if($opts['p'])
    {
      $params['pids'] = $opts['p'];
    }
    elseif($opts['pids'])
    {
      $params['pids'] = $opts['pids'];
    }

    if($opts['l'])
    {
      $params['debug'] = $opts['l'];
    }
    elseif($opts['debug'])
    {
      $params['debug'] = $opts['debug'];
    }

    if($opts['r'])
    {
      $params['rebuild'] = $opts['r'];
    }
    elseif($opts['rebuild'])
    {
      $params['rebuild'] = $opts['rebuild'];
    }

    return $params;
  }

  /**
   * Parse database host, name, and credentials from options data
   * @param  string $str string of database information
   * @return array       array of parsed database information
   */
  function getDatabaseValues($str)
  {
    if($str==NULL)
    {
      return NULL;
    }

    $data = explode(":", $str);

    if(count($data) < 4)
    {
      return NULL;
    }

    $db = Array
    (
      'host'   => $data[0],
      'user'   => $data[1],
      'pass'   => $data[2],
      'dbname' => $data[3]
    );

    return $db;
  }

  /**
   * Get a list of unique pids from file and command line lists
   *
   * NOTE: If a user enters a file path to a list of pids AND a list of
   * pids via the command line, these two lists will be combined with
   * duplicates removed.
   *
   * @param  string $filedata   path to pid list file
   * @param  string $stringdata string of comma separated pids
   * @return array              Array of unique pids to query for MySQL data
   */
  function getPids($filedata, $stringdata)
  {
    $fpids = parsePidsFromFile($filedata);
    $spids = parsePidsFromString($stringdata, ",");

    return array_unique(array_merge($fpids,$spids), SORT_REGULAR);
  }

  /**
   * Parse pids from a string into an array
   * @param  string $list String list of pids separated by comma
   * @return array        Array of pids
   */
  function parsePidsFromString($list, $delimiter)
  {
    if(!$list || $list == '')
    {
      return Array();
    }

    $data = str_replace(' ', '', trim($list));
    $pids = explode($delimiter, $data);

    return $pids;
  }

  /**
   * Parse pids from a list
   * NOTE Assume list of pids features a new pid each line.
   *
   * if errors are encountered, migrator object will output failed pids to a file
   * in this format.
   *
   * @param  string $path File path to list of pids
   * @return array       Array of pids
   */
  function parsePidsFromFile($path)
  {
    if(!$path)
    {
      return Array();
    }

    try
    {
      return parsePidsFromString(file_get_contents($path), "\n");
    }
    catch(Exception $e)
    {
      echo "Error: File could not be read! Message: $e\n";
      return Array();
    }
  }
?>
