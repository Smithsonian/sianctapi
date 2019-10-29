<?php
/**
 * Sidora Analysis API for CameraTrap
 *
 * Modified by MS of Quotient on 2014-07-10:
 * - Added $config to object, read in from __construct
 * - Removed dependcy on Drupal
 * - Removed literal linebreaks from strings in favor of newline chars
 * - Added $user as class property
 * - Renamed $user to $app_id
 *
 * file: SIANCT.inc
 * @author: Gert Schmeltz Pedersen gertsp45@gmail.com
 */

// Date/timezone for portability
date_default_timezone_set(@date_default_timezone_get());

require_once './lib/Logger.php';

// Class definition
class SIANCTAPI
{
  private $config;
  private $app_id;
  private $logger;

  /**
   * Constructor
   * Accepts an array containing configuration details and a string with the app_id.
   */
  function __construct($config = array(), $app_id = '') {
    $this->config = array();
    if (is_array($config)) {
      $this->config = $config;
    }

    // Default the config
    $this->config += array(
      'sianctapi_block_cache' => '',  // Deprecate.
      'sianctapi_block_solr' => 'noSolrUrl',
      'sianctapi_block_solr_max' => 1000,
      'sianctapi_block_solr_xslt_filtered' => 'none',
      'sianctapi_block_solr_xslt_tree' => 'none',
      'sianctapi_block_gsearch' => 'nogflowurl',
      'sianctapi_block_fedora' => 'nofedoraurl',
      'sianctapi_block_fedora_userpass' => 'nofedorauser:password',
      'sianctapi_block_cache_refreshing' => array(),  // Deprecate.
      'sianctapi_log_level' => Logger::LEVEL_INFO,
      'sianctapi_log_path' => '',
      'sianctapi_log_prefix' => '',
      'sianctapi_path' => '',
      "mysql_host" => '',
      "mysql_username" => '',
      "mysql_password" => '',
      "mysql_dbname" => ''
    );

    // Set app_id
    $this->app_id = $app_id;

    // Set up a logger.
    $this->logger = $this->createLogger('sianctapi-' . date('Y-m-d') . '.log');

    return;
  }

  /**
   * Get File Method for API Route
   * @param  string $filepath path to file
   * @return string           HTML string of results
   */
  function sianctapiGetFile($filepath) {
    $this->logger->notice("$this->app_id sianctapiGetFile $filepath ");

    $result = $this->sianctapiGettFile($filepath);
    $out = '';
    if (strpos($filepath, '.html') > -1) {
      $out .= '<div id="sianctapiFileResult">';
    }
    $out .= $result;
    if (strpos($filepath, '.html') > -1) {
      $out .= '</div>';
    }
    return $out;
  }

  /**
   * [sianctapiGettFile description]
   * @param  string $filepath path to file
   * @return string           results of file reaad operation
   */
  function sianctapiGettFile($filepath) {
    #global $user;
    $this->logger->notice("$this->app_id sianctapiGettFile $filepath ");

    if (!is_readable($filepath))
    {
      $result = 'SYSTEM ERROR: file is not readable: ' . $filepath;
    }
    else
    {
      $fp = fopen($filepath, 'r');

      if ($fp === false)
      {
        $result = 'SYSTEM ERROR: fopen failed: ' . $filepath;
      }
      else
      {
        $result = fread($fp, filesize($filepath));

        if ($result === false)
        {
          $result = 'SYSTEM ERROR: fread failed: ' . $filepath;
        }

        fclose($fp);
      }
    }
    $logString = substr($result,0,2000);

    if (strlen($result) > 2000) $logString .= '...';

    $this->logger->info("$this->app_id sianctapiGettFile $filepath result:\n $logString");

    return $result;
  }

  /**
   * Save contents in a file
   * @param  string $filepath Path to file
   * @param  string $contents String contents to save in file
   * @return string           String results of file write operation
   */
  function sianctapiSaveInFile($filepath, $contents)
  {
    $this->logger->notice("$this->app_id sianctapiSaveInFile $filepath");
    $result = 'Saving ' . $filepath . ': ';
    $fp = fopen($filepath, 'w');

    if ($fp)
    {
      $fw = fwrite($fp, $contents);

      if ($fw === false)
      {
        $result .= 'fwrite failed';
      }
      else
      {
        $result .= $fw . ' bytes written';
      }
    }
    else
    {
      $result .= 'fopen failed ('.$filepath . ')';
    }

    $this->logger->info("$this->app_id sianctapiSaveInFile $result");

    fclose($fp);

    return $result;
  }

  /**
   * Get selected obersvations based on a list of obstable pids and a list of
   * species name and save it to a file
   *
   * @param  string $filepath     path to selected observations file
   * @param  array $obstables     array of observation table data strings
   * @param  string $obstablePids comma separated list of obstable pids
   * @param  string $speciesNames comma separated list of species names
   * @return string               result of file write operation
   */
  function sianctapiSaveSelectedObservations($filepath, $obstables, $obstablePids, $speciesNames)
  {
    $this->logger->notice("$this->app_id sianctapiSaveSelectedObservations $filepath");

    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);

    if ($resultingObservations)
    {
      $result = $this->sianctapiSaveInFile($filepath, $resultingObservations);
    }
    else
    {
      $result = 'No resulting observations';
    }

    $this->logger->info("$this->app_id sianctapiSaveSelectedObservations $result");

    return $result;
  }

  /**
   * Streams the content of a file from runtime. Basic rules for limiting to csv.
   * Added by mds
   *
   * @param  string $filename name of file or null
   * @return NULL
   */
  function sianctapiDownload($filename = NULL)
  {
    $this->logger->notice("$this->app_id sianctapiDownload $filename");

    if (FALSE !== strpos($filename, '../'))
    {
      return NULL;
    }

    $ext = substr($filename, -4);

    if (!in_array($ext, array('.csv', 'jpeg', 'json', '.png', '.jpg')))
    {
      return NULL;
    }

    $file = $this->path('runtime/' . $filename);

    if (!is_file($file))
    {
      self::sendHeader(404);
      return NULL;
    }

    echo file_get_contents($file);
    exit();
  }

  /**
   * Function for running an r script using passed parameters
   * @param  string $workflowName  [name of the r script to run
   * @param  string $obstablePids  comma separated list of obstable pids
   * @param  string $speciesNames  comma separated list of species names
   * @param  string $resultFileExt File extension to write results to
   * @return string                Results in HTML string format
   */
  function sianctapiRunWorkflow($workflowName, $obstablePids, $speciesNames, $resultFileExt)
  {
    $this->logger->notice("$this->app_id sianctapiRunWorkflow / $workflowName / $obstablePids / $speciesNames");

    $obstables = $this->sianctapiGetObstablesFromMySQL();

    $UUID = uniqid();

    //$csvfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $csvfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';

    $result = $csvfilepath;

    //update once sianctapiSaveSelectedObservations is updated
    $saveresult = $this->sianctapiSaveSelectedObservations($csvfilepath, $obstables, $obstablePids, $speciesNames);

    $this->logger->info("$this->app_id sianctapiRunWorkflow saving csv: $saveresult");

    $result_worked = FALSE;

    if (!file_exists($csvfilepath))
    {
      $result = 'SYSTEM ERROR: csv file was not created: ' . $saveresult;
    }
    else
    {
      $resultfilepath = $csvfilepath;

      if (strpos($workflowName, '.R') > -1)
      {
        /*
        $resultfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.' . $resultFileExt;
        $workflowfilepath = trim($this->config['sianctapi_path'], '/') . '/' . $workflowName;
        $outfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/' . $workflowName . '-' . $UUID . '.out';
        */
        $resultfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.' . $resultFileExt;

        $workflowfilepath = $this->config['sianctapi_path'] . '/resources/rscripts/' . $workflowName;

        $outfilepath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . '-' . $UUID . '.out';

        if (!is_readable($workflowfilepath))
        {
          $result = 'SYSTEM ERROR: R script file is not readable: ' . $workflowfilepath;
        }
        else
        {
          $command = 'R CMD BATCH --vanilla "--args ' . $csvfilepath . ' ' . $resultfilepath . '" ' . $workflowfilepath . ' ' . $outfilepath . ' 2>&1';

          $this->logger->debug("$this->app_id sianctapiRunWorkflow command: $command");

          $rOut = shell_exec($command);

          $this->logger->debug("$this->app_id sianctapiRunWorkflow R out:\n $rOut");

          if (!is_readable($resultfilepath))
          {
            $result = 'SYSTEM ERROR: result file was not created: ' . $resultfilepath;
            //$routfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/' . $workflowName . 'out';

            $routfilepath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . 'out';

            if (!is_readable($routfilepath))
            {
              $result .= '\n diagnosis file ' . $routfilepath . ' not found';
            }
            else
            {
              $result .= '\n diagnosis file ' . $routfilepath . ' contains\n' . $this->sianctapiGettFile($routfilepath);
            }
          }
          else
          {
            $result = $resultfilepath;
            $result_worked = TRUE;
          }
        }
      }
      else
      {
        // Assume this works for CSVs...
        $result_worked = TRUE;
      }
    }

    $this->logger->info("$this->app_id sianctapiRunWorkflow result: $result");

    if (!$result_worked)
    {
      //self::sendHeader(500);
      //return '';
      return $result;
    }

    $out = "\n" . '<div id="sianctapiRunWorkflowResult">';

    if ($result_worked == TRUE && strpos($result, '/') !== FALSE)
    {
      $foo = explode('/', $result);
      $out .= end($foo);
    }
    else
    {
      $out .= $result; //basename($result);
    }

    $out .= '</div>' . "\n";
    return $out;
  }

  /**
   * Run R Script workflow for Occupany.
   *
   * NOTE Separate from sianctapiRunWorkflow because extra arguments are required.
   *
   * NO UPDATE NECESSARY
   *
   * @param  string $projectCsvFile   name of project data csv file
   * @param  string $deploymentCsv  csv deployment data in string format
   * @param  int    $clumpInterval     clump interval provided by user
   * @return string                 html string of R script results
   */
  function sianctapiRunOccupancyWorkflow($projectCsvFile, $deploymentCsv, $clumpInterval)
  {
    /**
     * No changes should be necessary. Rather, I should build out a way for project and
     * deployment data from mysql to be put in csv string format (do I?)
     */
    $logFunc = __function__;

    // Hardcoded the R script filename.
    $workflowName = 'Occupancy.R';

    $this->logger->notice("$this->app_id $logFunc / $projectCsvFile / $clumpInterval");

    $UUID = uniqid();

    $projectCsvFilePath = $this->config['sianctapi_path'] . '/runtime/' . $projectCsvFile;
    $workflowFilePath = $this->config['sianctapi_path'] . '/resources/rscripts/' . $workflowName;

    $deploymentCsvFilePath = $this->config['sianctapi_path'] . '/runtime/sianctapi-occupancy-deployments-' . $UUID . '.csv';
    $deploymentCsvFile = fopen($deploymentCsvFilePath, 'w');

    fwrite($deploymentCsvFile, $deploymentCsv);
    fclose($deploymentCsvFile);

    if (!file_exists($projectCsvFilePath))
    {
      $result = 'SYSTEM ERROR: project csv file is not available not created.';
    }
    else if (!file_exists($deploymentCsvFilePath))
    {
      $result = 'SYSTEM ERROR: deployment csv file is not available not created.';
    }
    else if (!is_readable($workflowFilePath))
    {
      $result = 'SYSTEM ERROR: Occupancy R script file is not readable';
    }
    else if ($clumpInterval <= 0)
    {
      $result = 'SYSTEM ERROR: invalid clump interval: ' . $clumpInterval;
    }
    else
    {
      $resultFilePath = $this->config['sianctapi_path'] . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.csv';
      $outFilePath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . '-' . $UUID . '.out';

      $command = sprintf('R CMD BATCH --vanilla "--args %s %s %s %s" %s %s 2>&1', $projectCsvFilePath, $deploymentCsvFilePath, $clumpInterval, $resultFilePath, $workflowFilePath, $outFilePath );

      $this->logger->debug("$this->app_id $logFunc command: $command");

      $rOut = shell_exec($command);

      $this->logger->debug("$this->app_id $logFunc R out:\n $rOut");

      if (!is_readable($resultFilePath))
      {
        $result = 'SYSTEM ERROR: result file was not created: ' . $resultFilePath;

        if (!is_readable($outFilePath))
        {
          $result .= '\n diagnosis file ' . $outFilePath . ' not found';
        }
        else
        {
          $result .= '\n diagnosis file ' . $outFilePath . ' contains\n' . $this->sianctapiGettFile($outFilePath);
        }
      }
      else
      {
        $result = $resultFilePath;
        $result_worked = TRUE;
      }
    }

    $this->logger->info("$this->app_id $logFunc result: $result");

    if (!$result_worked)
    {
      //self::sendHeader(500);
      //return '';
      return $result;
    }

    $out = "\n" . '<div id="sianctapiRunWorkflowResult">';

    if ($result_worked == TRUE && strpos($result, '/') !== FALSE)
    {
      $foo = explode('/', $result);
      $out .= end($foo);
    }
    else
    {
      $out .= $result; //basename($result);
    }

    $out .= '</div>' . "\n";

    return $out;
  }

  /**
   * Get project structure
   * @param  [type] $xslt
   * @param  string $wt
   */
  function sianctapiGetProjectStructure($xslt, $wt='xslt')
  {
    $solrResult = $this->sianctapiGetProjectStructureFromSolr($xslt, $wt);
    return $solrResult;
  }

  /*
      NO UPDATE NECESSARY
   */
  function sianctapiGetProjectStructureMetadata($params)
  {
    $paramtxt = dt($params);
    $this->logger->notice("$this->app_id sianctapiGetProjectStructureMetadata\n $paramtxt");
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  /*
      UPDATED
   */
  function sianctapiGetProjectStructureCached()
  {
    $result = $this->sianctapiGetProjectStructureFromSolr('default');
    $logString = substr($result,0,300);
    if (strlen($result) > 300) $logString .= '...';
    $this->logger->info("$this->app_id sianctapiGetProjectStructureCached\n $logString");
    return $result;
  }

  /*
      NO UPDATE NECESSARY
   */
  function sianctapiGetProjectStructureFromSolr($xslt, $wt='xslt')
  {
    $solrXslt = $xslt;

    if ($xslt == '' || $xslt == 'default')
    {
      $solrXslt = $this->config['sianctapi_block_solr_xslt_tree']; #FIX
    }

    $xsltParams =  '';

    if ($solrXslt != 'none')
    {
      $xsltParams = '&tr=' . $solrXslt;
    }

    $params = 'q=PID:(si*%20OR%20ct*)+OR+projectPID:(si*%20OR%20ct*)+OR+ctPID:(si%20OR%20ct*)&sort=projectPID+asc,parkPID+asc,sitePID+asc,ctPID+asc,PID+asc&rows=99999&wt=' . $wt . $xsltParams;

    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);

    return $solrResult;
  }

  /*
      NO UPDATE NECESSARY
   */
  function sianctapiGetProjectStructureMetadataFromSolr($params)
  {
    $projectStructureLogger = $this->createLogger('sianctapi-project-structure-' . date('Y-m-d') . '.log');
    $projectStructureLogger->notice("$this->app_id sianctapiGetProjectStructureMetadataFromSolr: params=$params ");
    $solrUrl = $this->config['sianctapi_block_solr'] . '/gsearch_sianct/select?' . $params . '&version=2.2&indent=on';
    $solrResults = $this->curlWithRetries($solrUrl);
    $projectStructureLogger->info("$this->app_id solrResult: \n" . $solrResults['log']);
    return $solrResults['results'];
  }

  /*
      NO UPDATE NECESSARY
   */
  function sianctapiGetAllObstablePids()
  {
    $this->logger->notice("$this->app_id sianctapiGetAllObstablePids: noparams ");
    $solrResult = $this->sianctapiGetAllObstablePidsFromSolr();
    return $solrResult;
  }

  /*
      NO UPDATE NECESSARY
   */
  function sianctapiGetAllObstablePidsFromSolr()
  {
    $this->logger->notice("$this->app_id sianctapiGetAllObstablePidsFromSolr: noparams ");
    $params = 'q=PID:(si*%20OR%20ct*)+OR+projectPID:(si*%20OR%20ct*)+OR+ctPID:(si*%20OR%20ct*)&rows=99999&wt=xslt&tr=sianctapiGetObstablePids.xslt';
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  /*
    UPDATED
   */
  function sianctapiGetSelectedObservationsCSV($obstablePids, $speciesNames)
  {
    $countobstables = 0;
    $res = $this->sianctapiQueryMySQLDatabase("SELECT oobservation_id FROM observations");
    if($res)
    {
        $countobstables = $res->num_rows;
    }

    $this->logger->notice("$this->app_id sianctapiGetSelectedObservationsCSV: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");

    if ($obstablePids == 'ALL')
    {
      $obstablePids = $this->sianctapiGetObstablePidsStringFromMySQL();
    }

    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);

    $UUID = uniqid();
    //$csvfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $csvfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';

    //write csv data to file
    $fp = fopen($csvfilepath, 'w');
    fwrite($fp, $resultingObservations);
    fclose($fp);

    //return the file path
    return $csvfilepath;
  }

  /*
    UPDATED
   */
  function sianctapiGetSelectedObservations($obstablePids, $speciesNames)
  {
    $countobstables = 0;
    $res = $this->sianctapiQueryMySQLDatabase("SELECT oobservation_id FROM observations");
    if($res)
    {
        $countobstables = $res->num_rows;
    }

    $this->logger->notice("$this->app_id sianctapiGetSelectedObservations: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");

    if ($obstablePids == 'ALL')
    {
      $obstablePids = $this->sianctapiGetObstablePidsStringFromMySQL();
    }

    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);

    $out = '<div id="sianctapiGetObservationsResult">' . "\n";
    $out .= $resultingObservations;
    $out .= "\n" . '</div>' . "\n";
    return $out;
  }

  /*
    UPDATED
   */
  function sianctapiGettSelectedObservations(&$obstables, $obstablePids, $speciesNames)
  {
    //count number of passed obstables (obstables passed by reference)
    $countobstables = count($obstables);

    //log
    $this->logger->notice("$this->app_id sianctapiGettSelectedObservations: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");

    //get csv headers
    $csvHeaders = array(
      'Project',
      'Subproject',
      'Treatment',
      'Deployment Name',
      'ID Type',
      'Deploy ID',
      'Sequence ID',
      'Begin Time',
      'End Time',
      'Species Name',
      'Common Name',
      'Age',
      'Sex',
      'Individually Identifiable',
      'Count',
      'Actual Lat',
      'Actual Lon',
      'Feature type',
      'Publish Date',
      'Project Lat',
      'Project Lon',
      'Access Constraints'
    );

    //implode into a csv string
    $resultingObservations = implode($csvHeaders, ', ');

    //turn species csv string into an array
    $speciesnamesArray = str_getcsv($speciesNames);

    //get number of species entries
    $countSpeciesnames=count($speciesnamesArray);

    //check if null or empty
    if ($countSpeciesnames == 1 && !$speciesnamesArray[0])
    {
      $countSpeciesnames = 0;
    }

    //get array of obstable pids
    $obstablePidArray = str_getcsv($obstablePids);

    //get count of obstable pids
    $countPids=count($obstablePidArray);

    //if null or empty
    if ($countPids == 1 && !$obstablePidArray[0])
    {
      $countPids = 0;
    }

    $countLinesSum = 0;
    $countSelectedLinesSum = 0;

    for($i=0;$i<$countPids;$i++)
    {
      $obstablePid = trim($obstablePidArray[$i]);

      $obstable = $this->sianctapiGetObstable($obstables, $obstablePid);
      $resultingObservationsForPid = $obstable;

      $lines = explode("\n", $obstable);

      $countLines = count($lines);

      if ($countLines == 1 && !$lines[0])
      {
        $countLines = 0;
      }

      $countSelectedLines = $countLines;

      if ($countSpeciesnames>0 && trim($speciesnamesArray[0]))
      {
        $countSelectedLines = 0;
        $resultingObservationsForPid = '';

        for($j=0;$j<=$countLines;$j++)
        {
          $line = trim($lines[$j]);

          $speciesFound = false;

          if ($line)
          {
            for($k=0;$k<$countSpeciesnames;$k++)
            {
              $speciesName = trim($speciesnamesArray[$k]);

              if($speciesName && stripos($line, $speciesName))
              {
                $speciesFound = true;
                break;
              }
            }
          }
          if ($speciesFound)
          {
            if ($countSelectedLines > 0)
            {
              $resultingObservationsForPid .= "\n";
            }

            $resultingObservationsForPid .= $line;
            $countSelectedLines++;
          }
        }
      }
      if ($resultingObservationsForPid)
      {
        $resultingObservations .= "\n" . $resultingObservationsForPid; //add resultingObservationsForPid to total resultingObservations
      }

      $countSelectedLinesSum += $countSelectedLines;
      $countLinesSum += $countLines;
      $lenselectedObservations = strlen($resultingObservations);
      $n = $i + 1;
      $this->logger->info("$this->app_id sianctapiGettSelectedObservations: #obstables: $n of $countPids obstablePid= $obstablePid #lines: $countLines #selectedLines: $countSelectedLines #selectedLinesSum: $countSelectedLinesSum #linesSum: $countLinesSum #lenselectedObservations: $lenselectedObservations");
    }
    return $resultingObservations;
    //return str_replace('"', '', $resultingObservations);
  }

  /*
    UPDATED
   */
  function sianctapiGetObstable(&$obstables, $obstablePid)
  {
    //query mysql db for obstable data associated with pid
    $obstable = $this->sianctapiGetObstableFromMysql($obstablePid);

    //if obstable data associated with passed PID is not cached, query solr
    if (!$obstable)
    {
      $obstable = $this->sianctapiGetObstableForSianct($obstables, $obstablePid);
    }

    return $obstable;
  }

  /*
    NO UPDATE NECESSARY
   */
  function sianctapiGetObstableForSianct(&$obstables, $obstablePid)
  {
    $obstable = '';

    $this->logger->debug("TESTING NEW WCS FIELDS...... $obstablePid");

    #$solrResult = $this->sianctapiGetProjectHierarchyLabelsFromSolr($obstablePid);

    // Additional column change
    $solrResult = $this->sianctapiGetFieldsAddedToCsvFromSolr($obstablePid);
    $splitSolrResult = strpos($solrResult, '###');
    $projectHierarchyLabels = substr($solrResult, 0, $splitSolrResult);
    $actualLatLongFeaturetype = substr($solrResult, $splitSolrResult+3);
    // End Additional column change

    $params = 'objects/' . $obstablePid . '/datastreams/CSV/content';
    $fedoraResult = $this->sianctapiGetDataFromFedora($params);
    $this->logger->debug("fedoraResult (CSV): " . print_r($fedoraResult, true));
    $lines = explode("\n", trim($fedoraResult));
    $countLines=count($lines);
    if ($countLines == 1 && !$lines[0]) {
      $countLines = 0;
    }
    $countObsLines = 0;
    $currentlyLoadedCtPid = '';
    $loadedCtInfo = '';
    $currentlyLoadedProjectPid = '';
    $loadedProjectInfo = '';

    $solrData = $this->sianctapiSelectObstables("PID:" . str_replace(':', '\:', $obstablePid), 'none');
    $xmlDoc = @simplexml_load_string($solrData);

    for($j=0;$j<$countLines;$j++) {
      $line = trim($lines[$j]);
      if ($line) {
        if ($countObsLines > 0) {
          $obstable .= "\n";
        }

        // Restrict the Fedora CSV to the first X fields
        $stream = fopen('php://temp', 'r+');

        // For CSV columns, see https://confluence.si.edu/pages/viewpage.action?spaceKey=CT&title=Researcher+Observation
        $lineArray = str_getcsv($line);
        $values = array_slice($lineArray, 0, 9);
        // Individually Identifiable
        $values[] = !empty($lineArray[11]) ? $lineArray[11] : 'N';
        // Count
        $values[] = !empty($lineArray[10]) ? $lineArray[10] : 0;
        fputcsv($stream,  $values);
        rewind($stream);
        $abbrline = trim(stream_get_contents($stream), "\r\n");
        $obstable .= implode(',', array($projectHierarchyLabels, $abbrline, $actualLatLongFeaturetype));

        // Step 1 - Grab Solr result from Resource Object PID
        // This will give us projectPID & ctPID
        $projectPID = '';
        $ctPID = '';
        if ($xmlDoc !== FALSE) {
          $projectPID = $xmlDoc->result->doc->str[8];
          $ctPID = $xmlDoc->result->doc->str[2];
        }

        // Step 2 - for the projectPID, execute call against EAC-CPF stream
        // Publish Date, Project Lat, Project Lon
        if ((string)$projectPID !== (string)$currentlyLoadedProjectPid) {

          $params = 'objects/' . $projectPID . '/datastreams/EAC-CPF/content';
          $loadedProjectInfo = $this->sianctapiGetDataFromFedora($params);
          $currentlyLoadedProjectPid = $projectPID;
        }
        $pubdate = $this->getPubDate($loadedProjectInfo);
        $latlon = $this->getLatLon($loadedProjectInfo);
        $obstable .= ',' . $pubdate . ',' . $latlon['lat'] . ',' . $latlon['lon'];

        // Step 3 - for ctPID, execute call against FGDC stream
        if ((string)$ctPID !== (string)$currentlyLoadedCtPid) {
          $params = 'objects/' . $ctPID . '/datastreams/FGDC/content';
          $loadedCtInfo = $this->sianctapiGetDataFromFedora($params);
          $currentlyLoadedCtPid = $ctPID;
        }
        $accconst = $this->getAccessConstraints($loadedCtInfo);
        $obstable .= ',' . $accconst;
        $countObsLines++;
      }
    }
    $obstables[$obstablePid] = $obstable;
    //$this->sianctapiCacheSet('sianctapi_block_obstables', $obstables);
    $countobstables = count($obstables);
    $this->logger->info("sianctapiGetObstableForSianct: obstablePid: $obstablePid #csvLines: $countLines #obsLines: $countObsLines #obstables: $countobstables");
    //echo $obstable . "\n";
    return $obstable;
  }

  /*
    NO UPDATE NECESSARY
   */
  function sianctapiGetFieldsAddedToCsvFromSolr($obstablePid)
  {
    $params = 'q=PID:%22' . $obstablePid . '%22&rows=1&wt=xslt&tr=sianctapiFieldsAddedToCsv-CT3.xslt';
    $solrResult =$this->sianctapiGetProjectStructureMetadataFromSolr($params);
    $solrResult = trim($solrResult);
    return $solrResult;
  }

  /*
    NO UPDATE NECESSARY
   */
  function sianctapiGetProjectHierarchyLabelsFromSolr($obstablePid) {
    $params = 'q=PID:%22' . $obstablePid . '%22&rows=1&wt=xslt&tr=sianctapiProjectStructureToCsv.xslt';
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  /*
    NO UPDATE NECESSARY
   */
  function sianctapiGetDataFromFedora($params)
  {
    $this->logger->notice("$this->app_id sianctapiGetDataFromFedora: params=$params ");
    $fedoraUrl = $this->config['sianctapi_block_fedora'];
    $fedoraUserPass = $this->config['sianctapi_block_fedora_userpass'];

    $curlOptions = array(
      CURLOPT_USERPWD => $fedoraUserPass,
    );
    $fedoraResults = $this->curlWithRetries($fedoraUrl . '/' . $params, $curlOptions);
    $this->logger->info("$this->app_id fedoraResult: \n" + $fedoraResults['log']);
    return $fedoraResults['results'];
  }

  /*
    UPDATED
   */
  function sianctapiGetSpecies($obstablePids)
  {
    $obstables = $this->sianctapiGetObstablesFromMySQL();
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSpecies: obstablePids= $obstablePids countobstables= $countobstables");
    $result = $this->sianctapiGetSpeciesOptions($obstables, $obstablePids);
    return $result;
  }

  /*
    UPDATED
   */
  function sianctapiGetSpeciesJSON($obstablePids)
  {
    $obstables = $this->sianctapiGetObstablesFromMySQL();
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSpeciesJSON: obstablePids= $obstablePids countobstables= $countobstables");
    $result = $this->sianctapiGetSpeciesOptionsJSON($obstables, $obstablePids);
    return $result;
  }

  /*
    UPDATED
   */
  function sianctapiGetAllSpeciesNamesCached()
  {
    $obstables = $this->sianctapiGetObstablesFromMySQL();
    $result = $this->sianctapiGetSpeciesOptions($obstables, 'ALL');
    $this->logger->info("$this->app_id sianctapiGetAllSpeciesNamesCached\n $result");
    return $result;
  }

  /*
    UPDATED
   */
  function sianctapiGetAllSpeciesNamesCachedJSON()
  {
    #global $user;
    $obstables = $this->sianctapiGetObstablesFromMySQL();
    $result = $this->sianctapiGetSpeciesOptionsJSON($obstables, 'ALL');
    $this->logger->info("$this->app_id sianctapiGetAllSpeciesNamesCachedJSON\n $result");
    return $result;
  }

  /*
    UPDATED
   */
  function sianctapiGetSpeciesOptions($obstables, $obstablePids)
  {
    $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids countobstables= " . count($obstables));

    if ($obstablePids == 'ALL')
    {
      $obstablePids = $this->sianctapiGetObstablePidsStringFromMySQL();
      $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids");
    }

    $speciesnames = array();
    $obstablePidArray = str_getcsv($obstablePids);
    $countPids=count($obstablePidArray);

    if ($countPids == 1 && !$obstablePidArray[0])
    {
      $countPids = 0;
    }

    $countObsLines = 0;

    for($i=0;$i<$countPids;$i++)
    {
      $obstablePid = trim($obstablePidArray[$i]);
      $obstable = $this->sianctapiGetObstable($obstables, $obstablePid);

      $lines = explode("\n", $obstable);

      $countLines = count($lines);

      if ($countLines == 1 && !$lines[0])
      {
        $countLines = 0;
      }

      for($j=0;$j<=$countLines;$j++) {
        if (!isset($lines[$j]) || empty($lines[$j])) { // Added by mds
          continue;
        }
        $line = $lines[$j];
        $columns = str_getcsv($line);
        // There is bug here.  Mostly the number of columns is 17 or 18 but a few of them are 7. DWD 1/13/2015
        //echo count($columns);
        //echo '\n';
        $begintime = trim($columns[7]);
        $speciesname = trim($columns[9]);
        $speciesname = trim($speciesname, '"');
        if ($speciesname and $begintime) {
          $commonname = trim($columns[10]);
          $commonname = trim($commonname, '"');
          if ( array_key_exists($speciesname, $speciesnames) ) {
            $countObs = $speciesnames[$speciesname][1];
            $speciesnames[$speciesname] = array($commonname, $countObs + 1);
          } else {
            $speciesnames[$speciesname] = array($commonname, 1);
          }
          $countObsLines++;
          $count = $speciesnames[$speciesname][1];
        }
      }
    }

    $countSpeciesNames = count($speciesnames);

    ksort($speciesnames);

    $result = "\n" . '<div id="sianctapiGetSpeciesResult">';
    $result .= "\n" . '<option value=" ">' . $countSpeciesNames . ' names ( ' . $countObsLines . ' observations )</option>';
    foreach ($speciesnames as $key => $value) {
      $result .= "\n" . '<option value="' . $key . '">' . $key . ' (' . $value[0] . ') (' . $value[1] . ')</option>';
    }
    $result .= "\n" . '</div>' . "\n";
    $this->logger->info("$this->app_id result: $result");

    return $result;
  }

  /*
    UPDATED
   */
  function sianctapiGetSpeciesOptionsJSON($obstables, $obstablePids)
  {
    $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids countobstables= " . count($obstables));

    //if obstablePids is all -> use MySQL db
    if ($obstablePids == 'ALL')
    {
      $obstablePids = $this->sianctapiGetObstablePidsStringFromMySQL();
      $this->logger->notice("$this->app_id sianctapiGetSpeciesOptionsJSON: obstablePids= $obstablePids");
    }

    $speciesnames = array();
    $obstablePidArray = str_getcsv($obstablePids);
    $countPids=count($obstablePidArray);

    //if ($countPids == 1 && !$obstablePidArray[0]) {
    if (!$obstablePidArray[0]) {
      $countPids = 0;
    }

    $this->logger->info("$this->app_id sianctapiGetSpeciesOptionsJSON: countPids= $countPids");
    $countObsLines = 0;

    for($i=0;$i<$countPids;$i++)
    {
      $obstablePid = trim($obstablePidArray[$i]);
      $obstable = $this->sianctapiGetObstable($obstables, $obstablePid);
      $this->logger->debug("$this->app_id sianctapiGetSpeciesOptionsJSON: obstable= " . print_r($obstable, true));
      $lines = explode("\n", $obstable);
      $countLines = count($lines);
      //if ($countLines == 1 && !$lines[0]) {
      if (!$lines[0]) {
        $countLines = 0;
      }

      $this->logger->info("$this->app_id sianctapiGetSpeciesOptionsJSON: countLines= $countLines");

      for($j=0;$j<=$countLines;$j++)
      {
        if (!isset($lines[$j]) || empty($lines[$j])) { // Added by mds
          continue;
        }

        $line = $lines[$j];
        $columns = str_getcsv($line);
        $this->logger->debug("$this->app_id sianctapiGetSpeciesOptionsJSON: obstable= " . count($columns));
        // There is bug here.  Mostly the number of columns is 17 or 18 but a few of them are 7. DWD 1/13/2015
        //echo count($columns);
        //echo '\n';
        $begintime = trim($columns[7]);
        $speciesname = trim($columns[9]);
        $speciesname = trim($speciesname, '"');

        $this->logger->debug("$this->app_id sianctapiGetSpeciesOptionsJSON: speciesname= $speciesname      begintime= $begintime");

        if ($speciesname and $begintime)
        {
          $commonname = trim($columns[10]);
          $commonname = trim($commonname, '"');

          if ( array_key_exists($speciesname, $speciesnames) )
          {
            $countObs = $speciesnames[$speciesname][1];
            $speciesnames[$speciesname] = array($commonname, $countObs + 1);
          } else {
            $speciesnames[$speciesname] = array($commonname, 1);
          }
          $countObsLines++;
          $count = $speciesnames[$speciesname][1];
        }
      }
    }
    $countSpeciesNames = count($speciesnames);

    ksort($speciesnames);

    return json_encode($speciesnames);
  }

  /*
    NO UPDATE NECESSARY
   */
  function sianctapiSelectObstables($query, $xslt)
  {
    $solrXslt = $xslt;
    if ($xslt == '' || $xslt == 'default') {
      $solrXslt = $this->config['sianctapi_block_solr_xslt_filtered'];
    }
    $xsltParams =  '';
    if ($solrXslt != 'none') {
      $xsltParams = '&wt=xslt&tr=' . $solrXslt;
    }
    $params = 'q=' . urlencode($query) . '&rows=9999&sort=PID+asc' . $xsltParams;
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  /**
   * This isn't called within the API, but called from the .sh script.
   */
  /*function sianctapiCacheRefresh()
  {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error)
    {
      die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM project_structure";
    $result = $this->sianctapiQueryMySQLDatabase($sql);

    if($result->num_rows > 0)
    {
      //clear existing cache data
      $sql = "DELETE FROM project_structure";
      $result = $conn->query($sql);

      //kill script if there are rows in the data table and you cannot remove them
      if (!$result)
      {
        echo "ERROR: Could not remove existing cache data from project_structure table\n";
        die("MySQL ERROR: $conn->error");
      }
      else
      {
        echo "Existing cache data cleared\n";
      }
    }

    //get the project structure html string
    $projectStructure = $this->sianctapiGetProjectStructureFromSolr('default');

    //query to insert data
    $sql = "INSERT INTO project_structure (project_structure_html) VALUES ('$projectStructure')";

    if ($conn->query($sql) === TRUE) //on success
    {
      echo "New record created successfully in project_structure\n";
    }
    else //on failure
    {
      echo "Error: in sql syntax\n" . $conn->error;
    }

    $conn->close();
    exit();
  }*/

  /*
     UPDATED
   */
  function sianctapiCacheCheck() {
    $this->logger->notice("$this->app_id sianctapiCacheCheck ");

    $cacheCheckLine = "";

    $conn = new mysqli($this->config['mysql_host'], $this->config['mysql_username'], $this->config['mysql_password'], $this->config['mysql_dbname']);

    // Check connection
    if ($conn->connect_error)
    {
      $cacheCheckLine .= "MYSQL_HOST: " .  $this->config['mysql_host'] . "\n";
      $cacheCheckLine .= "MYSQL_USER: " . $this->config['mysql_username'] . "\n";
      $cacheCheckLine .= "MYSQL_PASS: " . $this->config['mysql_password'] . "\n";
      $cacheCheckLine .= "MYSQL_DBNAME: " . $this->config['mysql_dbname'] . "\n";
      $cacheCheckLine .= "Connection to MySQL database failed - $conn->connect_error";
    }
    else
    {
      $cacheCheckLine = "$this->app_id sianctapiCacheCheck\n";

      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("projects");
      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("subprojects");
      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("plots");
      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("deployments");
      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("species");
      $cacheCheckLine .= $this->sianctapiGetMySQLTableStatus("observations");
    }

    $this->logger->notice($cacheCheckLine);
    return "$cacheCheckLine \n";
  }

  /**
   * TODO: This isn't really relevant to the API and should be deprecated. It is not used now
   */
  function sianctapiGetModulePath($moduleName) {
    $result = $this->config['sianctapi_path'];
    $this->logger->info("$this->app_id sianctapiGetModulePath $result");
    return "\n" . '<div id="sianctapiModulePathResult">' . $result . '</div>' . "\n";
  }

  /*
      NO UPDATE NECESSARY
   */
  function path($path, $cron=true) {
    if ($cron) {
      return $this->config['sianctapi_path'] . '/' . $path;
    } else {
      return $path;
    }
  }

  /*
      NO UPDATE NECESSARY
   */
  function sendHeader($code = NULL) {
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
      default: return;
    }

    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    header($protocol . ' ' . $code . ' ' . $text);
    return;
  }

  ///////
  // Gets additional fields from Fedora EAC-CPF datastream
  //
  // NO UPDATE NECESSARY
  //
  // Author: Paul Day
  // Date: 1/22/2016
  /////
  function getLatLon($fedoraData) {
    $data = array(
      'lat' => '',
      'lon' => '',
    );

    if ($xmlDoc = @simplexml_load_string($fedoraData)) {
      $latlon = $xmlDoc->cpfDescription->description->place->placeEntry[2]->attributes();
      $data['lat'] = $latlon['latitude'];
      $data['lon'] = $latlon['longitude'];
    }

    return $data;
  }

  ///////
  // Gets additional fields from Fedora EAC-CPF datastream
  //
  // NO UPDATE NECESSARY
  //
  // Author: Paul Day
  // Date: 1/22/2016
  /////
  function getPubDate($fedoraData) {
    $pubDate = '';

    if ($xmlDoc = @simplexml_load_string($fedoraData)) {
      $pubDate = $xmlDoc->control->localControl->date;
    }

    return $pubDate;
  }

  ///////
  // Get Access Constraints from Fedora FGDC datastream
  //
  // NO UPDATE NECESSARY
  //
  // Author: Paul Day
  // Date: 1/22/2016
  ///////
  function getAccessConstraints($fedoraData) {
    $accessConstraints = '';

    if ($xmlDoc = @simplexml_load_string($fedoraData)) {
      $accessConstraints = $xmlDoc->idinfo->accconst;
    }

    return $accessConstraints;
  }

  /**
   * Get volunteer and researcher identifications for a deployment
   *
   * NO UPDATE NECESSARY
   *
   * @param $ctPid
   *   The deployment's ctPID.
   *
   * @return
   *   A JSON-encoded object of identifications, grouped by image sequence.
   */
  function getManifestIdentifications($ctPid) {
    $params = 'objects/' . $ctPid . '/datastreams/MANIFEST/content';
    $results = array();
    $fedoraResult = $this->sianctapiGetDataFromFedora($params);

    $ids = array('VolunteerIdentifications', 'ResearcherIdentifications');
    if ($xml = @simplexml_load_string($fedoraResult)) {
      foreach ($xml->ImageSequence as $seq) {
        foreach ($ids as $id) {
          if (!property_exists($seq, $id)) {
            continue 2;
          }
        }

        foreach ($ids as $id) {
          $sid = $seq->ImageSequenceId->__toString();
          $results[$sid][$id] = $seq->{$id};
        }
      }
    }

    return json_encode($results);
  }

  /**
   * Curl wrapper with a fixed number of retries.
   *
   * The SIANCT API cache generation can DDOS systems with rapid data requests.
   * Curl returns a CURLE_COULDNT_CONNECT (7) error when this happens. A lack
   * of fault tolerance also means cached data is invalid. This Curl wrappers
   * will wait and retry requests in an attempt to alleviate load.
   *
   * @param $url
   * @param $curlOpts
   *
   * @return Array
   */
  private function curlWithRetries($url, $curlOpts = array()) {
    $maxRetries = 5;
    $retrySleep = 5;
    $return = array(
      'log' => '',
      'results' => FALSE,
    );

    $ch = curl_init();
    if (!empty($curlOpts)) {
      foreach ($curlOpts as $option => $value) {
        curl_setopt($ch, $option, $value);
      }
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    for ($i = $maxRetries; $i >= 0; $i--) {
      $results = curl_exec($ch);

      // Successful request
      if (!curl_errno($ch)) {
        $return['results'] = $results;

        $info = curl_getinfo($ch);
        if (!empty($return['log'])) {
          $return['log'] .= 'Successful request after Curl errors.' . "\n";
        }
        $return['log'] .= sprintf('HTTP code %s: %s',  $info['http_code'], substr($results, 0, 300));
        if (strlen($results) > 300) {
          $return['log'] .= '...';
        }
        break;
      }

      // Curl error with request
      $return['log'] .= sprintf('Curl error (%d): %s.', curl_errno($ch), curl_error($ch));

      if ($i > 1) {
        $message = ' %d retries left.';
      }
      elseif ($i == 1) {
        $message = ' %d retry left.';
      }
      else {
        $message = ' No retries left, request aborted.';
      }
      $return['log'] .= sprintf($message, $i);
      $return['log'] .= "\n";

      if ($i > 0) {
        sleep($retrySleep);
      }
    }

    curl_close($ch);
    return $return;
  }

  /**
   * Set up a logger instance.
   *
   * @param $file String The file name to use for logging.
   *
   * @return \Logger
   */
  private function createLogger($file) {
    if ($this->config['sianctapi_log_prefix'] != '') {
      $file = $this->config['sianctapi_log_prefix'] . '.' . $file;
    }
    $logger = new Logger($this->config['sianctapi_log_path'], $file);
    $logger->setLevel($this->config['sianctapi_log_level']);

    return $logger;
  }

  /***MySQL FUNCTIONS***/

  private function sianctapiQueryMySQLDatabase($sql)
  {
    // Create connection
    $conn = new mysqli($this->config['mysql_host'], $this->config['mysql_username'], $this->config['mysql_password'], $this->config['mysql_dbname']);

    // Check connection
    if ($conn->connect_error)
    {
      $this->logger->debug("Connection to MySQL database failed - $conn->connect_error");
    }

    //$sql = "SELECT id, firstname, lastname FROM MyGuests";
    $result = $conn->query($sql);

    if ($result->num_rows > 0)
    {
      return $result;
    }

    $conn->close();
    return NULL;
  }

  private function sianctapiGetObstablePidsStringFromMySQL()
  {
    $pidsArray = $this->sianctapiGetObstablePidsArrayFromMySQL();
    return implode(",", $pidsArray);
  }

  private function sianctapiGetObstablePidsArrayFromMySQL()
  {
    $obstablePids = [];

    $sql = "SELECT DISTINCT obstable_id FROM observations";
    $result = $this->sianctapiQueryMySQLDatabase($sql);

    if($result)
    {
      while($row = $result->fetch_assoc())
      {
        array_push($obstablePids, $row['obstable_id']);
      }
    }

    return $obstablePids;
  }

  private function sianctapiGetObstablesFromMySQL($obstablePids=NULL)
  {
    try
    {
      $prefix = "./mysql/sql/query";

      $obstable_query = file_get_contents("$prefix/obstable.sql");
      $plots_query = file_get_contents("$prefix/plots.sql");
      $plots_res = $this->sianctapiQueryMySQLDatabase($plots_query);

      $plots = array();

      if($plots_res != NULL)
      {
        while($row = $plots_res->fetch_assoc())
        {
          $plots[$row['id']] = $row['treatment'];
        }
      }

      if($obstablePids && sizeof($obstablePids) > 0)
      {
        $query_string = "observations.obstable_id IN (";
        $count = 1;
        foreach($obstablePids as $pid)
        {
          $query_string .= "\"$pid\"";
          if($count < sizeof($obstablePids))
          {
            $query_string .= ",";
            $count++;
          }
        }

        $query_string .= ")";
        $obstable_query .= " AND " . $query_string;
      }

      $obstable_res = $this->sianctapiQueryMySQLDatabase($obstable_query);
      $plot_check = (sizeof($plots) > 0);

      if($obstable_res)
      {
        //$obstables = array();
        $obstables = "";
        while($row = $obstable_res->fetch_assoc())
        {
          //$ob = [];
          /*$project = ($row["project"] != "" && $row["project"] != NULL)? $row["project"] : "\"\"";
          $subproject = ($row["subproject"] != "" && $row["subproject"] != NULL)? $row["subproject"] : "\"\"";*/
          $project = "\"" . $row["project"] . "\"";
          $subproject = "\"" . $row["subproject"] . "\"";

          $treatment = "\"\"";
          if($row["plot"] != NULL && $row["plot"] != "" && $plot_check)
          {
            //$treatment = ($row["plot"] != "" && $row["plot"] != NULL)? $plots[$row["plot"]] : "\"\"";
            $treatment = "\"" . $plots[$row["plot"]] . "\"";
          }

          /*$deploymentName = ($row["deploymentName"] != "" && $row["deploymentName"] != NULL)? $row["deploymentName"] : "\"\"";
          $idType = ($row["idType"] != "" && $row["idType"] != NULL)? $row["idType"] : "\"\"";
          $deployId = ($row["deployId"] != "" && $row["deployId"] != NULL)? $row["deployId"] : "\"\"";
          $sequenceId = ($row["sequenceId"] != "" && $row["sequenceId"] != NULL)? $row["sequenceId"] : "\"\"";
          $beginTime = ($row["beginTime"] != "" && $row["beginTime"] != NULL)? $row["beginTime"] : "\"\"";
          $endTime = ($row["endTime"] != "" && $row["endTime"] != NULL)? $row["endTime"] : "\"\"";
          $speciesName = ($row["speciesName"] != "" && $row["speciesName"] != NULL)? $row["speciesName"] : "\"\"";
          $commonName = ($row["commonName"] != "" && $row["commonName"] != NULL)? $row["commonName"] : "\"\"";
          $age = ($row["age"] != "" && $row["age"] != NULL)? $row["age"] : "\"\"";
          $sex = ($row["sex"] != "" && $row["sex"] != NULL)? $row["sex"] : "\"\"";
          $individual = ($row["individual"] != "" && $row["individual"] != NULL)? $row["individual"] : "\"\"";
          $count = ($row["count"] != "" && $row["count"] != NULL)? $row["count"] : "\"\"";
          $actualLat = ($row["actualLat"] != "" && $row["actualLat"] != NULL)? $row["actualLat"] : "\"\"";
          $actualLon = ($row["actualLon"] != "" && $row["actualLon"] != NULL)? $row["actualLon"] : "\"\"";
          $featureType = ($row["featureType"] != "" && $row["featureType"] != NULL)? $row["featureType"] : "\"\"";
          $publishDate = ($row["publishDate"] != "" && $row["publishDate"] != NULL)? $row["publishDate"] : "\"\"";
          $projectLat = ($row["projectLat"] != "" && $row["projectLat"] != NULL)? $row["projectLat"] : "\"\"";
          $projectLon = ($row["projectLon"] != "" && $row["projectLon"] != NULL)? $row["projectLon"] : "\"\"";
          $accessConstraints = ($row["deploymentName"] != "" && $row["deploymentName"] != NULL)? $row["accessConstraints"] : "\"\"";*/

          $deploymentName = "\"" . $row["deploymentName"] . "\"";
          $idType = "\"" . $row["idType"] . "\"";
          $deployId = "\"" . $row["deployId"] . "\"";
          $sequenceId = "\"" . $row["sequenceId"] . "\"";
          $beginTime = "\"". $row["beginTime"] . "\"";
          $endTime = "\"" . $row["endTime"] . "\"";
          $speciesName = "\"" . $row["speciesName"] . "\"";
          $commonName = "\"" . $row["commonName"] . "\"";
          $age = "\"" . $row["age"] . "\"";
          $sex = "\"" . $row["sex"] . "\"";
          $individual = "\"" . $row["individual"] . "\"";
          $count = $row["count"];
          $actualLat = $row["actualLat"];
          $actualLon = $row["actualLon"];
          $featureType = $row["featureType"];
          $publishDate = $row["publishDate"];
          $projectLat = $row["projectLat"];
          $projectLon = $row["projectLon"];
          $accessConstraints = $row["accessConstraints"];

          $ob = [
            $project,
            $subproject,
            $treatment,
            $deploymentName,
            $idType,
            $deployId,
            $sequenceId,
            $beginTime,
            $endTime,
            $speciesName,
            $commonName,
            $age,
            $sex,
            $individual,
            $count,
            $actualLat,
            $actualLon,
            $featureType,
            $publishDate,
            $projectLat,
            $projectLon,
            $accessConstraints
          ];

          $obstables .= implode(",", $ob) . "\n";

          /*$deploymentName = $row["deploymentName"];
          $idType = $row["idType"];
          $deployId = $row["deployId"];
          $sequenceId = $row["sequenceId"];
          $beginTime = $row["beginTime"];
          $endTime = $row["endTime"];
          $speciesName = $row["speciesName"];
          $commonName = $row["commonName"];
          $age = $row["age"];
          $sex = $row["sex"];
          $individual = $row["individual"];
          $count = $row["count"];
          $actualLat = $row["actualLat"];
          $actualLon = $row["actualLon"];
          $featureType = $row["featureType"];
          $publishDate = $row["publishDate"];
          $projectLat = $row["projectLat"];
          $projectLon = $row["projectLon"];
          $accessConstraints = $row["accessConstraints"];*/

          //$obstables .= "$project, $subproject, $treatment, $deploymentName, $idType, $deployId, $sequenceId, $beginTime, $endTime, $speciesName, $commonName, $age, $sex, $individual, $count, $actualLat, $actualLon, $featureType, $publishDate, $projectLat, $projectLon, $accessConstraints\n";
          //$obstables .= "$obstable_line\n";
          //array_push($obstables, $obstable_line);
          /*$observation = [
            $project,
            $subproject,
            $treatment,
            $deploymentName,
            $idType,
            $deployId,
            $sequenceId,
            $beginTime,
            $endTime,
            $speciesName,
            $commonName,
            $age,
            $sex,
            $individual,
            $count,
            $actualLat,
            $actualLon,
            $featureType,
            $publishDate,
            $projectLat,
            $projectLon,
            $accessConstraints
          ];

          if(trim(str_replace(',', '', implode(",", $observation))) != "")
          {
            array_push($obstables, implode(",", $observation));
          }*/
        }
        return $obstables;
      }
      //return implode("\n", $obstables);
      return NULL;
    }
    catch(Exception $e)
    {
      return NULL;
    }
  }

  private function sianctapiGetObstablesFromMySQLDeprecated($obstablePids=NULL)
  {
    if(!$obstablePids)
    {
      $obstablePids = $this->sianctapiGetObstablePidsArrayFromMySQL();
    }

    $obstables = Array();

    foreach($obstablePids as $pid)
    {
      $sql = "SELECT * FROM observations WHERE obstable_id=\"$pid\"";
      $observations = $this->sianctapiQueryMySQLDatabase($sql);

      $obstable = Array();

      if($observations)
      {
        //while we still have rows in our results
        while($vals = $observations->fetch_assoc())
        {
          //initialize observation table row values
          //project
          $project = "";
          $subproject = "";
          $treatment = "";
          $deploymentName = "";
          $idType = "";
          $deployId = "";
          $sequenceId = "";
          $beginTime = "";
          $endTime = "";
          $speciesName = "";
          $commonName = "";
          $age = "";
          $sex = "";
          $individual = "";
          $count = "";
          $actualLat = "";
          $actualLon = "";
          $featureType = "";
          $publishDate = "";
          $projectLat = "";
          $projectLon = "";
          $accessConstraints = "";

          $idType = $vals['id_type'];
          $sequenceId = $vals['sequence_id'];
          $beginTime = $vals['begin_time'];
          $endTime = $vals['end_time'];
          $age = $vals['age'];
          $sex = $vals['sex'];
          $count = $vals['count'];
          $individual = $vals['individual'];

          $iucn_id = $vals['iucn_id'];

          $sql = "SELECT scientific_name, common_name FROM species WHERE iucn_id=\"$iucn_id\"";
          $species_result = $this->sianctapiQueryMySQLDatabase($sql);

          $species_vals = $species_result->fetch_assoc();

          $speciesName = $species_vals['scientific_name'];
          $commonName = $species_vals['common_name'];

          $deploymentPID = $vals['sidora_deployment_id'];
          $sql = "SELECT name, ct_deployment_id, feature_type, actual_lat, actual_lon, access_constraints, sidora_plot_id, sidora_subproject_id FROM deployments WHERE sidora_deployment_id=\"$deploymentPID\"";

          $deployment_result = $this->sianctapiQueryMySQLDatabase($sql);

          if($deployment_result)
          {
            $deployment_vals = $deployment_result->fetch_assoc();

            $deploymentName = $deployment_vals['name'];
            $deployId = $deployment_vals['ct_deployment_id'];
            $featureType = $deployment_vals['feature_type'];
            $actualLat = $deployment_vals['actual_lat'];
            $actualLon = $deployment_vals['actual_lon'];
            $accessConstraints = $deployment_vals['access_constraints'];

            $plot_pid = $deployment_vals['sidora_plot_id'];

            $sql = "SELECT treatment FROM plots WHERE sidora_plot_id=\"$plot_pid\"";
            $plot_result = $this->sianctapiQueryMySQLDatabase($sql);

            if($plot_result)
            {
              $plot_vals = $plot_result->fetch_assoc();
              $treatment = $plot_vals['treatment'];
            }

            $subproject_pid = $deployment_vals['sidora_subproject_id'];

            $sql = "SELECT name, sidora_project_id FROM subprojects WHERE sidora_subproject_id=\"$subproject_pid\"";
            $sub_proj_results = $this->sianctapiQueryMySQLDatabase($sql);

            if($sub_proj_results)
            {
              $sub_proj_vals = $sub_proj_results->fetch_assoc();

              $subproject = $sub_proj_vals['name'];

              $project_pid = $sub_proj_vals['sidora_project_id'];

              $sql = "SELECT name, publish_date, lat, lon FROM projects WHERE sidora_project_id=\"$project_pid\"";
              $proj_results = $this->sianctapiQueryMySQLDatabase($sql);

              if($proj_results)
              {
                $proj_vals = $proj_results->fetch_assoc();

                $project = $proj_vals['name'];
                $publishDate = $proj_vals['publish_date'];
                $projectLat = $proj_vals['lat'];
                $projectLon = $proj_vals['lon'];
              }
            }
          }

          $observation = [
            $project,
            $subproject,
            $treatment,
            $deploymentName,
            $idType,
            $deployId,
            $sequenceId,
            $beginTime,
            $endTime,
            $speciesName,
            $commonName,
            $age,
            $sex,
            $individual,
            $count,
            $actualLat,
            $actualLon,
            $featureType,
            $publishDate,
            $projectLat,
            $projectLon,
            $accessConstraints
          ];

          if(trim(str_replace(',', '', implode(",", $observation))) != "")
          {
            array_push($obstable, implode(",", $observation));
          }
        }
      }

      array_push($obstables, implode("\n", $obstable));
    }

    if(count($obstables) > 0)
    {
      return $obstables;
    }
    else
    {
      return NULL;
    }
  }

  function sianctapiGetObstableFromMysql($PID)
  {
    $result = $this->sianctapiGetObstablesFromMySQL([$PID]);

    if($result && count($result) == 1)
    {
      return $result[0];
    }
    else
    {
      return NULL;
    }
  }

  private function sianctapiGetMySQLTableStatus($tableName)
  {
    $tableCheck = $this->sianctapiQueryMySQLDatabase("SHOW TABLES LIKE '$tableName'");
    $tableExists = ($tableCheck->num_rows == 1);
    if($tableExists)
    {
      $tableQuery = $this->sianctapiQueryMySQLDatabase("SELECT * FROM $tableName");

      if($tableQuery)
      {
        $tableLen = $tableQuery->num_rows;
      }
      else
      {
        $tableLen = 0;
      }

      $statusString = $tableName . "TableLen: $tableLen\n";
    }
    else
    {
      $statusString = $tableName . "Table: Table Not Found\n";
    }

    return $statusString;
  }
}
