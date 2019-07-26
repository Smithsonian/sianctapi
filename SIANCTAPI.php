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
class SIANCTAPI {
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
    );

    // Set app_id
    $this->app_id = $app_id;

    // Set up a logger.
    $this->logger = $this->createLogger('sianctapi-' . date('Y-m-d') . '.log');

    return;
  }

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

  function sianctapiGettFile($filepath) {
    #global $user;
    $this->logger->notice("$this->app_id sianctapiGettFile $filepath ");
    if (!is_readable($filepath)) {
      $result = 'SYSTEM ERROR: file is not readable: ' . $filepath;
    } else {
      $fp = fopen($filepath, 'r');
      if ($fp === false) {
        $result = 'SYSTEM ERROR: fopen failed: ' . $filepath;
      } else {
        $result = fread($fp, filesize($filepath));
        if ($result === false) {
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

  function sianctapiSaveInFile($filepath, $contents) {
    $this->logger->notice("$this->app_id sianctapiSaveInFile $filepath");
    $result = 'Saving ' . $filepath . ': ';
    $fp = fopen($filepath, 'w');
    if ($fp) {
      $fw = fwrite($fp, $contents);
      if ($fw === false) {
        $result .= 'fwrite failed';
      } else {
        $result .= $fw . ' bytes written';
      }
    } else {
      $result .= 'fopen failed ('.$filepath . ')';
    }
    $this->logger->info("$this->app_id sianctapiSaveInFile $result");
    fclose($fp);
    return $result;
  }

  function sianctapiSaveSelectedObservations($filepath, $obstables, $obstablePids, $speciesNames) {
    $this->logger->notice("$this->app_id sianctapiSaveSelectedObservations $filepath");
    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);
    if ($resultingObservations) {
      $result = $this->sianctapiSaveInFile($filepath, $resultingObservations);
    } else {
      $result = 'No resulting observations';
    }
    $this->logger->info("$this->app_id sianctapiSaveSelectedObservations $result");
    return $result;
  }

  /**
   * Streams the content of a file from runtime. Basic rules for limiting to csv.
   * Added by mds
   */
  function sianctapiDownload($filename = NULL) {
    $this->logger->notice("$this->app_id sianctapiDownload $filename");

    if (FALSE !== strpos($filename, '../')) {
      return NULL;
    }

    $ext = substr($filename, -4);
    if (!in_array($ext, array('.csv', 'jpeg', 'json', '.png', '.jpg'))) {
      return NULL;
    }

    $file = $this->path('runtime/' . $filename);
    if (!is_file($file)) {
      self::sendHeader(404);
      return NULL;
    }

    echo file_get_contents($file);
    exit();
  }

  function sianctapiRunWorkflow($workflowName, $obstablePids, $speciesNames, $resultFileExt) {
    $this->logger->notice("$this->app_id sianctapiRunWorkflow / $workflowName / $obstablePids / $speciesNames");
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstables = $sianctapiCache['obstables'];
    $UUID = uniqid();
    //$csvfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $csvfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $result = $csvfilepath;
    $saveresult = $this->sianctapiSaveSelectedObservations($csvfilepath, $obstables, $obstablePids, $speciesNames);
    $this->logger->info("$this->app_id sianctapiRunWorkflow saving csv: $saveresult");
    $result_worked = FALSE;

    if (!file_exists($csvfilepath)) {
      $result = 'SYSTEM ERROR: csv file was not created: ' . $saveresult;
    } else {
      $resultfilepath = $csvfilepath;

      if (strpos($workflowName, '.R') > -1) {
        /*
        $resultfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.' . $resultFileExt;
        $workflowfilepath = trim($this->config['sianctapi_path'], '/') . '/' . $workflowName;
        $outfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/' . $workflowName . '-' . $UUID . '.out';
        */
        $resultfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.' . $resultFileExt;
        $workflowfilepath = $this->config['sianctapi_path'] . '/resources/rscripts/' . $workflowName;
        $outfilepath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . '-' . $UUID . '.out';
        if (!is_readable($workflowfilepath)) {
          $result = 'SYSTEM ERROR: R script file is not readable: ' . $workflowfilepath;
        } else {
          $command = 'R CMD BATCH --vanilla "--args ' . $csvfilepath . ' ' . $resultfilepath . '" ' . $workflowfilepath . ' ' . $outfilepath . ' 2>&1';
          $this->logger->debug("$this->app_id sianctapiRunWorkflow command: $command");
          $rOut = shell_exec($command);
          $this->logger->debug("$this->app_id sianctapiRunWorkflow R out:\n $rOut");
          if (!is_readable($resultfilepath)) {
            $result = 'SYSTEM ERROR: result file was not created: ' . $resultfilepath;
            //$routfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/' . $workflowName . 'out';
            $routfilepath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . 'out';
            if (!is_readable($routfilepath)) {
              $result .= '\n diagnosis file ' . $routfilepath . ' not found';
            } else {
              $result .= '\n diagnosis file ' . $routfilepath . ' contains\n' . $this->sianctapiGettFile($routfilepath);
            }
          } else {
            $result = $resultfilepath;
            $result_worked = TRUE;
          }
        }
      } else {
        // Assume this works for CSVs...
        $result_worked = TRUE;
      }
    }

    $this->logger->info("$this->app_id sianctapiRunWorkflow result: $result");

    if (!$result_worked) {
      //self::sendHeader(500);
      //return '';
      return $result;
    }

    $out = "\n" . '<div id="sianctapiRunWorkflowResult">';
    if ($result_worked == TRUE && strpos($result, '/') !== FALSE) {
      $foo = explode('/', $result);
      $out .= end($foo);
    } else {
      $out .= $result; //basename($result);
    }
    $out .= '</div>' . "\n";
    return $out;
  }

  /**
   * Run R Script workflow for Occupany.
   *
   * Separate from sianctapiRunWorkflow because extra arguments are required.
   *
   * @param $workflowName
   * @param $obstablePids
   * @param $speciesNames
   * @param $resultFileExt
   *
   * @return string
   */
  function sianctapiRunOccupancyWorkflow($projectCsvFile, $deploymentCsv, $clumpInterval) {
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

    if (!file_exists($projectCsvFilePath)) {
      $result = 'SYSTEM ERROR: project csv file is not available not created.';
    }
    else if (!file_exists($deploymentCsvFilePath)) {
      $result = 'SYSTEM ERROR: deployment csv file is not available not created.';
    }
    else if (!is_readable($workflowFilePath)) {
      $result = 'SYSTEM ERROR: Occupancy R script file is not readable';
    }
    else if ($clumpInterval <= 0) {
      $result = 'SYSTEM ERROR: invalid clump interval: ' . $clumpInterval;
    }
    else {
      $resultFilePath = $this->config['sianctapi_path'] . '/runtime/sianctapi-result-' . $workflowName . '-' . $UUID . '.csv';
      $outFilePath = $this->config['sianctapi_path'] . '/runtime/' . $workflowName . '-' . $UUID . '.out';

      $command = sprintf('R CMD BATCH --vanilla "--args %s %s %s %s" %s %s 2>&1', $projectCsvFilePath, $deploymentCsvFilePath, $clumpInterval, $resultFilePath, $workflowFilePath, $outFilePath );

      $this->logger->debug("$this->app_id $logFunc command: $command");
      $rOut = shell_exec($command);
      $this->logger->debug("$this->app_id $logFunc R out:\n $rOut");

      if (!is_readable($resultFilePath)) {
        $result = 'SYSTEM ERROR: result file was not created: ' . $resultFilePath;
        if (!is_readable($outFilePath)) {
          $result .= '\n diagnosis file ' . $outFilePath . ' not found';
        } else {
          $result .= '\n diagnosis file ' . $outFilePath . ' contains\n' . $this->sianctapiGettFile($outFilePath);
        }
      } else {
        $result = $resultFilePath;
        $result_worked = TRUE;
      }
    }

    $this->logger->info("$this->app_id $logFunc result: $result");

    if (!$result_worked) {
      //self::sendHeader(500);
      //return '';
      return $result;
    }

    $out = "\n" . '<div id="sianctapiRunWorkflowResult">';
    if ($result_worked == TRUE && strpos($result, '/') !== FALSE) {
      $foo = explode('/', $result);
      $out .= end($foo);
    } else {
      $out .= $result; //basename($result);
    }
    $out .= '</div>' . "\n";
    return $out;
  }

  function sianctapiGetProjectStructure($xslt, $wt='xslt') {
    $solrResult = $this->sianctapiGetProjectStructureFromSolr($xslt, $wt);
    return $solrResult;
  }

  function sianctapiGetProjectStructureMetadata($params) {
    $paramtxt = dt($params);
    $this->logger->notice("$this->app_id sianctapiGetProjectStructureMetadata\n $paramtxt");
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  function sianctapiGetProjectStructureCached() {
    $sianctapiCache = $this->sianctapiCacheGet(); #FIX
    $result = $sianctapiCache['projectStructure'];
    $logString = substr($result,0,300);
    if (strlen($result) > 300) $logString .= '...';
    $this->logger->info("$this->app_id sianctapiGetProjectStructureCached\n $logString");
    return $result;
  }

  function sianctapiGetProjectStructureFromSolr($xslt, $wt='xslt') {
    $solrXslt = $xslt;
    if ($xslt == '' || $xslt == 'default') {
      $solrXslt = $this->config['sianctapi_block_solr_xslt_tree']; #FIX
    }
    $xsltParams =  '';
    if ($solrXslt != 'none') {
      $xsltParams = '&tr=' . $solrXslt;
    }

    $params = 'q=PID:(si*%20OR%20ct*)+OR+projectPID:(si*%20OR%20ct*)+OR+ctPID:(si%20OR%20ct*)&sort=projectPID+asc,parkPID+asc,sitePID+asc,ctPID+asc,PID+asc&rows=99999&wt=' . $wt . $xsltParams;
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  function sianctapiGetProjectStructureMetadataFromSolr($params) {
    $projectStructureLogger = $this->createLogger('sianctapi-project-structure-' . date('Y-m-d') . '.log');

    $projectStructureLogger->notice("$this->app_id sianctapiGetProjectStructureMetadataFromSolr: params=$params ");
    $solrUrl = $this->config['sianctapi_block_solr'] . '/gsearch_sianct/select?' . $params . '&version=2.2&indent=on';
    $solrResults = $this->curlWithRetries($solrUrl);
    $projectStructureLogger->info("$this->app_id solrResult: \n" . $solrResults['log']);
    return $solrResults['results'];
  }

  function sianctapiGetAllObstablePids() {
    $this->logger->notice("$this->app_id sianctapiGetAllObstablePids: noparams ");
    $solrResult = $this->sianctapiGetAllObstablePidsFromSolr();
    return $solrResult;
  }

  function sianctapiGetAllObstablePidsFromSolr() {
    $this->logger->notice("$this->app_id sianctapiGetAllObstablePidsFromSolr: noparams ");
    $params = 'q=PID:(si*%20OR%20ct*)+OR+projectPID:(si*%20OR%20ct*)+OR+ctPID:(si*%20OR%20ct*)&rows=99999&wt=xslt&tr=sianctapiGetObstablePids.xslt';
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  function sianctapiGetSelectedObservationsCSV($obstablePids, $speciesNames) {
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstables = $sianctapiCache['obstables'];
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSelectedObservationsCSV: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");
    if ($obstablePids == 'ALL') {
      //$obstablePids = $this->sianctapiGetAllObstablePidsFromSolr();
      $obstablePids = $sianctapiCache['obstablePids'];
    }
    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);
    $UUID = uniqid();
    //$csvfilepath = trim($this->config['sianctapi_path'], '/') . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $csvfilepath = $this->config['sianctapi_path'] . '/runtime/sianctapi-selected-observations-' . $UUID . '.csv';
    $fp = fopen($csvfilepath, 'w');
    fwrite($fp, $resultingObservations);
    fclose($fp);
    return $csvfilepath;
  }

  function sianctapiGetSelectedObservations($obstablePids, $speciesNames) {
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstables = $sianctapiCache['obstables'];
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSelectedObservations: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");
    if ($obstablePids == 'ALL') {
      $obstablePids = $sianctapiCache['obstablePids'];
    }
    $resultingObservations = $this->sianctapiGettSelectedObservations($obstables, $obstablePids, $speciesNames);
    $out = '<div id="sianctapiGetObservationsResult">' . "\n";
    $out .= $resultingObservations;
    $out .= "\n" . '</div>' . "\n";
    return $out;
  }

  function sianctapiGettSelectedObservations(&$obstables, $obstablePids, $speciesNames) {
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGettSelectedObservations: obstablePids= $obstablePids speciesNames= $speciesNames countobstables= $countobstables");

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
    $resultingObservations = implode($csvHeaders, ', ');
    $speciesnamesArray = str_getcsv($speciesNames);
    $countSpeciesnames=count($speciesnamesArray);
    if ($countSpeciesnames == 1 && !$speciesnamesArray[0]) {
      $countSpeciesnames = 0;
    }
    $obstablePidArray = str_getcsv($obstablePids);
    $countPids=count($obstablePidArray);
    if ($countPids == 1 && !$obstablePidArray[0]) {
      $countPids = 0;
    }
    $countLinesSum = 0;
    $countSelectedLinesSum = 0;
    for($i=0;$i<$countPids;$i++) {
      $obstablePid = trim($obstablePidArray[$i]);
      $obstable = $this->sianctapiGetObstable($obstables, $obstablePid);
      $resultingObservationsForPid = $obstable;
      $lines = explode("\n", $obstable);
      $countLines = count($lines);
      if ($countLines == 1 && !$lines[0]) {
        $countLines = 0;
      }
      $countSelectedLines = $countLines;
      if ($countSpeciesnames>0 && trim($speciesnamesArray[0])) {
        $countSelectedLines = 0;
        $resultingObservationsForPid = '';
        for($j=0;$j<=$countLines;$j++) {
          $line = trim($lines[$j]);
          $speciesFound = false;
          if ($line) {
            for($k=0;$k<$countSpeciesnames;$k++) {
              $speciesName = trim($speciesnamesArray[$k]);
              if($speciesName && stripos($line, $speciesName)) {
                $speciesFound = true;
                break;
              }
            }
          }
          if ($speciesFound) {
            if ($countSelectedLines > 0) {
              $resultingObservationsForPid .= "\n";
            }
            $resultingObservationsForPid .= $line;
            $countSelectedLines++;
          }
        }
      }
      if ($resultingObservationsForPid) {
        $resultingObservations .= "\n" . $resultingObservationsForPid;
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

  function sianctapiGetObstable(&$obstables, $obstablePid) {
    if (!array_key_exists($obstablePid, $obstables)) {
      $obstable = $this->sianctapiGetObstableForSianct($obstables, $obstablePid);
      return $obstable;
    } else {
      return $obstables[$obstablePid];
    }
  }

  function sianctapiGetObstableForSianct(&$obstables, $obstablePid) {
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

  function sianctapiGetFieldsAddedToCsvFromSolr($obstablePid) {
    $params = 'q=PID:%22' . $obstablePid . '%22&rows=1&wt=xslt&tr=sianctapiFieldsAddedToCsv-CT3.xslt';
    $solrResult =$this->sianctapiGetProjectStructureMetadataFromSolr($params);
    $solrResult = trim($solrResult);
    return $solrResult;
  }

  function sianctapiGetProjectHierarchyLabelsFromSolr($obstablePid) {
    $params = 'q=PID:%22' . $obstablePid . '%22&rows=1&wt=xslt&tr=sianctapiProjectStructureToCsv.xslt';
    $solrResult = $this->sianctapiGetProjectStructureMetadataFromSolr($params);
    return $solrResult;
  }

  function sianctapiGetDataFromFedora($params) {
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

  function sianctapiGetSpecies($obstablePids) {
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstables = $sianctapiCache['obstables'];
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSpecies: obstablePids= $obstablePids countobstables= $countobstables");
    $result = $this->sianctapiGetSpeciesOptions($obstables, $obstablePids, $sianctapiCache);
    return $result;
  }

  function sianctapiGetSpeciesJSON($obstablePids) {
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstables = $sianctapiCache['obstables'];
    $countobstables = count($obstables);
    $this->logger->notice("$this->app_id sianctapiGetSpeciesJSON: obstablePids= $obstablePids countobstables= $countobstables");
    $result = $this->sianctapiGetSpeciesOptionsJSON($obstables, $obstablePids, $sianctapiCache);
    return $result;
  }

  function sianctapiGetAllSpeciesNamesCached() {
    $sianctapiCache = $this->sianctapiCacheGet();
    $result = $sianctapiCache['speciesOptions'];
    $this->logger->info("$this->app_id sianctapiGetAllSpeciesNamesCached\n $result");
    return $result;
  }

  function sianctapiGetAllSpeciesNamesCachedJSON() {
    #global $user;
    $sianctapiCache = $this->sianctapiCacheGet();
    $result = $sianctapiCache['speciesOptionsJSON'];
    $this->logger->info("$this->app_id sianctapiGetAllSpeciesNamesCachedJSON\n $result");
    return $result;
  }

  function sianctapiGetSpeciesOptions($obstables, $obstablePids, &$sianctapiCache) {
    $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids countobstables= " . count($obstables));
    if ($obstablePids == 'ALL') {
      $obstablePids = $sianctapiCache['obstablePids'];
      $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids");
    }

    $speciesnames = array();
    $obstablePidArray = str_getcsv($obstablePids);
    $countPids=count($obstablePidArray);
    if ($countPids == 1 && !$obstablePidArray[0]) {
      $countPids = 0;
    }
    $countObsLines = 0;

    for($i=0;$i<$countPids;$i++) {
      $obstablePid = trim($obstablePidArray[$i]);
      $obstable = $this->sianctapiGetObstable($obstables, $obstablePid);
      $lines = explode("\n", $obstable);
      $countLines = count($lines);
      if ($countLines == 1 && !$lines[0]) {
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


  function sianctapiGetSpeciesOptionsJSON($obstables, $obstablePids, &$sianctapiCache) {
    $this->logger->notice("$this->app_id sianctapiGetSpeciesOptions: obstablePids= $obstablePids countobstables= " . count($obstables));
    if ($obstablePids == 'ALL') {
      $obstablePids = $sianctapiCache['obstablePids'];
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
    for($i=0;$i<$countPids;$i++) {
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
      for($j=0;$j<=$countLines;$j++) {
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

    return json_encode($speciesnames);
  }

  function sianctapiSelectObstables($query, $xslt) {
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
  function sianctapiCacheRefresh() {
    $cacheLogger = $this->createLogger('sianctapi-cache-' . date('Y-m-d') . '.log');

    $file = $this->path('runtime/sianctapi-cache-file');
    if (!is_writable($file)) {
      exit('Failed to write cache at ' . $file . '. Cache not writable.');
    } else {
      echo "Writing cache to $file\n";
    }
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh begin");
    $sianctapiCacheRefreshing = array(
      'beginTime'=> date('c'),
      'obstablePids'=>'',
      'obstables'=>array(),
      'selectedObservations'=>'',
      'projectStructure'=>'',
      'speciesOptions'=>'',
      'speciesOptionsJSON' => '',
      'endTime'=>'',
      );

    // Obstable PIDs
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh obstablePids started.");
    $sianctapiCacheRefreshing['obstablePids'] = $this->sianctapiGetAllObstablePidsFromSolr();
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh obstablePids complete.");
    $cacheLogger->debug($sianctapiCacheRefreshing['obstablePids']);

    // Selected Observations
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh selectedObservations started.");
    $sianctapiCacheRefreshing['selectedObservations'] = $this->sianctapiGettSelectedObservations($sianctapiCacheRefreshing['obstables'], $sianctapiCacheRefreshing['obstablePids'], '');
    $countobstables = count($sianctapiCacheRefreshing['obstables']);
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh selectedObservations complete");
    $cacheLogger->notice("selectedObservations countobstables: $countobstables");
    $cacheLogger->debug("$this->app_id sianctapiCacheRefresh selectedObservations:\n " . $sianctapiCacheRefreshing['selectedObservations']);

    // Project Structure
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh projectStructure started.");
    $sianctapiCacheRefreshing['projectStructure'] = $this->sianctapiGetProjectStructureFromSolr('default');
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh projectStructure complete.");
    $cacheLogger->debug("$this->app_id sianctapiCacheRefresh projectStructure:\n " . $sianctapiCacheRefreshing['projectStructure']);

    // Species Data
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh speciesOptions started.");
    $sianctapiCacheRefreshing['speciesOptions'] = $this->sianctapiGetSpeciesOptions($sianctapiCacheRefreshing['obstables'], $sianctapiCacheRefreshing['obstablePids'], $sianctapiCacheRefreshing);
    $sianctapiCacheRefreshing['speciesOptionsJSON'] = $this->sianctapiGetSpeciesOptionsJSON($sianctapiCacheRefreshing['obstables'], $sianctapiCacheRefreshing['obstablePids'], $sianctapiCacheRefreshing);
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh speciesOptions complete.");
    $cacheLogger->debug("$this->app_id sianctapiCacheRefresh speciesOptions:\n " . $sianctapiCacheRefreshing['speciesOptions']);

    // Cache creation complete.
    $sianctapiCacheRefreshing['endTime'] = date('c');
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh end");

    $lenobstablePids = strlen($sianctapiCacheRefreshing['obstablePids']);
    $countobstables = count($sianctapiCacheRefreshing['obstables']);
    $lenselectedObservations = strlen($sianctapiCacheRefreshing['selectedObservations']);
    $lenprojectStructure = strlen($sianctapiCacheRefreshing['projectStructure']);
    $lenspeciesOptions = strlen($sianctapiCacheRefreshing['speciesOptions']);

    $cacheCheckLine = "$this->app_id sianctapiCacheRefresh check
      lenobstablePids: $lenobstablePids
      countobstables: $countobstables
      lenselectedObservations: $lenselectedObservations
      lenprojectStructure: $lenprojectStructure
      lenspeciesOptions: $lenspeciesOptions";
    $cacheLogger->notice($cacheCheckLine);

    if ($lenobstablePids < 1000 ||
        $countobstables < 100 ||
        $lenselectedObservations < 1000 ||
        $lenprojectStructure < 1000 ||
        $lenspeciesOptions < 1000) {
      $this->logger->critical("sianctapiCacheRefresh FAILED");
      $cacheLogger->critical("sianctapiCacheRefresh FAILED");
      return "CACHE FAILED";
    } else {
      $this->sianctapiCacheSet('sianctapi_block_cache', $sianctapiCacheRefreshing);
    }
    $this->logger->notice("$this->app_id sianctapiCacheRefresh end");
    $cacheLogger->notice("$this->app_id sianctapiCacheRefresh end");
    exit();
  }

  function sianctapiCacheCheck() {
    $this->logger->notice("$this->app_id sianctapiCacheCheck ");
    $sianctapiCache = $this->sianctapiCacheGet();
    $obstablePids = $sianctapiCache['obstablePids'];
    $lenobstablePids = strlen($obstablePids);
    $obstablesRefreshing = $sianctapiCache['obstables'];
    $countobstables = count($obstablesRefreshing);
    $selectedObservations = $sianctapiCache['selectedObservations'];
    $lenselectedObservations = strlen($selectedObservations);
    $projectStructure = $sianctapiCache['projectStructure'];
    $lenprojectStructure = strlen($projectStructure);
    $speciesOptions = $sianctapiCache['speciesOptions'];
    $lenspeciesOptions = strlen($speciesOptions);
    $endtime = $sianctapiCache['endTime'];
    $cacheCheckLine = "$this->app_id sianctapiCacheCheck
      lenobstablePids: $lenobstablePids
      countobstables: $countobstables
      lenselectedObservations: $lenselectedObservations
      lenprojectStructure: $lenprojectStructure
      lenspeciesOptions: $lenspeciesOptions
      endtime: $endtime";
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

  function path($path, $cron=true) {
    if ($cron) {
      return $this->config['sianctapi_path'] . '/' . $path;
    } else {
      return $path;
    }
  }

  /**
   * This replace's drupal's variable_set function.
   * TODO: This needs to be replaced with a much, much better model.
   */
  function sianctapiCacheSet($name, $val) {
    $value = serialize($val);
    $file = $this->path('runtime/sianctapi-cache-file');
    #return file_put_contents('/tmp/sianctapi-cache-file', $value);
    if (!file_exists($file)) {
      touch($file);
    }
    chmod($file, 0644);
    return file_put_contents($file, $value);
  }

  function sianctapiCacheGet() {
    $file = $this->path('runtime/sianctapi-cache-file');
    if (file_exists($file)) {
      #$cache = file_get_contents('/tmp/sianctapi-cache-file');
      $cache = file_get_contents($file);
      return unserialize($cache);
    }
    return array();
  }

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
}
