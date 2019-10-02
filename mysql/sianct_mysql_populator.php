<?php
  /**
   * SIANCT MySQL Data Populator Class
   *
   * Recursively cycles through PIDS in fedora repo, extracting information for
   * MySQL data tables.
   */
  class sianct_mysql_populator
  {
    /**
     * Constructor for SIANCT MySQL Data Populator Class
     * @param array $db array of database information
     *
     * NOTE if $db is NULL, constructor uses values in sianct.ini config file
     */
    public function __construct($db=NULL)
    {
      $this->config = parse_ini_file('sianct.ini');

      $dbvals = $db ? $db : $this->config;

      $this->host = $dbvals['host'];
      $this->user = $dbvals['user'];
      $this->pass = $dbvals['pass'];
      $this->dbname = $dbvals['dbname'];
    }

    /**
     * Main method of class. Initializes log and timing data and then passes a
     * list of PIDs to the recursive findObjects method.
     *
     * @param  array  $PIDs        list of fedora PIDs
     * @param  boolean $repopulate If TRUE, delete database and repopulate.
     */
    public function populateDatabase($PIDs=NULL, $repopulate=FALSE)
    {
      ini_set("memory_limit", "-1");

      //initialize log data
      $this->resetLogData();

      //if $repopulate is TRUE, delete existing database.
      if($repopulate)
      {
        $this->deleteDatabase();
      }

      //Initialize database if it hasn't been already.
      $this->initializeSianctDatabase();

      //if $PIDs is empty or null, add root pid to the list
      if(!$PIDs || count($PIDs) == 0)
      {
        $this->isSubsetOfRepo = TRUE;
        $PIDs = Array('si:121909');
      }

      //start timer
      $start = microtime(true);

      //pass each $PID to findObjects method
      foreach($PIDs as $PID)
      {
        $this->findObjects($PID);
      }

      //end timer and get execution time
      $time_elapsed_secs = microtime(true) - $start;

      //create log file prefix from current date
      $date = new DateTime('NOW');
      $prefix= $date->format('m-d-Y_H:i:s');

      //output log contains error and debug messages
      $output_log = './log/' . $prefix . '_output.log';
      //pids log contains a list of PIDs that failed to be added to the database.
      $pids_log = './log/' . $prefix . '_failed_pids.log';

      //write failed PIDS to pids log
      foreach($this->errorpids as $pid)
      {
        $this->log($pid, $pids_log);
      }

      //log debug and error data
      $this->logDebugData($time_elapsed_secs, $output_log);

      //reset log data.
      $this->resetLogData();
    }

    /**
     * Function to write error and debug data to log file
     * @param  double $time     Execution time for database population
     * @param  string $filepath path to log file
     */
    private function logDebugData($time, $filepath)
    {
      //log execution time
      $this->log("Execution time for database population: $time", $filepath);

      if($this->isSubsetOfRepo)
      {
        //get row count for each datatable
        $db_projects = $this->getTableLength('projects');
        $db_subprojects = $this->getTableLength('subprojects');
        $db_plots = $this->getTableLength('plots');
        $db_deployments = $this->getTableLength('deployments');
        $db_observations = $this->getTableLength('observations');

        //log comparisons of fedora data objects and mysql entries for projects, subprojects, plots, deployments, and observations
        $this->log("\nFedora Project Count: $this->projectCount", $filepath);
        $this->log("MySQL Project Count: $db_projects", $filepath);
        $this->log("Population Success: " . (($this->projectCount == $db_projects) ? "TRUE" : "FALSE"), $filepath);

        $this->log("\nFedora Subproject Count: $this->subprojectCount", $filepath);
        $this->log("MySQL Subproject Count: $db_subprojects", $filepath);
        $this->log("Population Success: " . (($this->subprojectCount == $db_subprojects) ? "TRUE" : "FALSE"), $filepath);

        $this->log("\nFedora Plot Count: $this->plotCount", $filepath);
        $this->log("MySQL Plot Count: $db_plots", $filepath);
        $this->log("Population Success: " . (($this->plotCount == $db_plots) ? "TRUE" : "FALSE"), $filepath);

        $this->log("\nFedora Deployment Count: $this->deploymentCount", $filepath);
        $this->log("MySQL Deployment Count: $db_deployments", $filepath);
        $this->log("Population Success: " . (($this->deploymentCount == $db_deployments) ? "TRUE" : "FALSE"), $filepath);

        $this->log("\nFedora Observation Count: $this->observationCount", $filepath);
        $this->log("MySQL Observation Count: $db_observations", $filepath);
        $this->log("Population Success: " . (($this->observationCount == $db_observations) ? "TRUE" : "FALSE"), $filepath);
      }

      //write log data to file.
      $this->log("\nLog: \n", $filepath);
      $this->log($this->executionlog, $filepath);
    }

    /**
     * Recursive method for retrieving fedora object pids
     * @param  string  $PID           object pid
     * @param  boolean $parentproject true if object parent is of type project
     * @param  string  $parent        pid of object parent
     */
    public function findObjects($PID, $parent=NULL)
    {
      //echo "$PID\n";
      //get RELS-EXT datastream information for Fedora PID
      $rels = $this->getRelsExtData($PID);

      //if parent parameter is NULL, get information from FEDORA
      if($parent == NULL && $PID != "si:121909")
      {
        $parentRels = $this->getRelsExtData($rels['parent']);
        $parent = Array(
          'pid' => $rels['parent'],
          'type' => $parentRels['type'],
          'isSubproject' => $parentRels['isSubproject']
        );
      }

      if($parent['type'] == "si:projectCModel" && !$parent['isSubproject'] && !$rels['isSubproject'])
      {
        echo "Skipping $PID. It has a project parent but is not a subproject";
        return;
      }

      if($rels['type'] == 'si:cameraTrapCModel') //Fedora Deployment Object
      {
        echo "Deployment $PID\n";
        $this->deploymentCount++;
        $this->writeDeploymentTable($PID, $parent['pid']);
      }
      else
      {
        if($rels['type'] == 'si:projectCModel' && $PID != 'si:121909')
        {
          if($parent['pid'] != 'si:121909' && $parent['type'] == 'si:projectCModel') //Fedora Subproject Object
          {
            echo "Subproject $PID\n";
            $this->subprojectCount++;
            $this->writeSubprojectTable($PID, $parent['pid']);
          }
          else //Fedora Project Object
          {
            echo "Project $PID\n";
            $this->projectCount++;
            $this->writeProjectTable($PID);
          }
        }
        elseif($rels['type'] == 'si:ctPlotCModel') //Fedora Plot Object
        {
          echo "Plot $PID\n";
          $this->plotCount++;
          $this->writePlotTable($PID, $parent['pid']);
        }

        //recursively call children of fedora object
        foreach($rels['children'] as $child)
        {
          /*
            Use current PID and type to pass as parent information for children
            This prevents an excess of Fedora queries for RELS-EXT data.
           */
          $parent = Array(
            'pid' => $PID,
            'type' => $rels['type']
          );

          //recursive call to findObjects with child
          $this->findObjects($child, $parent);
        }
      }
    }

    /**
     * Extract Project table values and insert row into mysql
     * @param  string $PID pid of project
     */
    private function writeProjectTable($PID)
    {
      try
      {
        //get EAC-CPF datastream for Project
        $xml = $this->getDatastream($PID, "EAC-CPF");

        if($xml)
        {
          //register namespaces for EAC-CPF xml
          $xml->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
          $xml->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
          $xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

          //get project table data from EAC-CPF xml
          $tableValues = Array(
            'sidora_project_id' => $PID,
            'ct_project_id' => $this->validate_xpath($xml, '//eac:recordId/text()'),
            'name' => $this->validate_xpath($xml, '//eac:nameEntry[@localType="primary"]/eac:part'),
            'country_code' => $this->validate_xpath($xml, '//eac:placeEntry/@countryCode'),
            'lat' => $this->validate_xpath($xml, '//eac:placeEntry/@latitude'),
            'lon' => $this->validate_xpath($xml, '//eac:placeEntry/@longitude'),
            'publish_date' => $this->validate_xpath($xml, '//eac:localControl[@localType="Publication Date"]/eac:date/text()'),
            'objectives' => strip_tags($this->validate_xpath($xml, '//eac:functions/eac:function[eac:term/text()="Project Objectives"]/eac:descriptiveNote/*')),
            'data_constraints' => $this->validate_xpath($xml, '//eac:functions/eac:function[eac:term/text()="Project Data Access and Use Constraints"]/eac:descriptiveNote/*'),
            'owner' => $this->validate_xpath($xml, '//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Owner"]/eac:relationEntry/text()'),
            'email' => $this->validate_xpath($xml, '//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Contact"]/eac:placeEntry/text()'),
            'principal_investigator' => $this->validate_xpath($xml, '//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Principal Investigator"]/eac:relationEntry/text()')
          );

          //insert data into projects table
          $sql = $this->tableInsert("projects", $tableValues);

          if($sql['status']) //successful insert
          {
            $this->executionlog .= "DEBUG: PROJECT $PID - Message: " . $sql['message'] . "\n";
            echo "DEBUG: PROJECT $PID - Message: " . $sql['message'] . "\n";
          }
          else //failed insert
          {
            //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
            if (strpos($sql['message'], 'Duplicate entry') === false)
            {
              $this->executionlog .= "ERROR: PROJECT $PID - " . $sql['message'] . "\n";
              echo "ERROR: PROJECT $PID - " . $sql['message'] . "\n";
              array_push($this->errorpids, $PID);
            }
          }
        }
      }
      catch(Exceptions $e)
      {
        $this->executionlog .= "ERROR: PROJECT $PID - $e\n";
        echo "ERROR: PROJECT $PID - $e\n";
        array_push($this->errorpids, $PID);
      }
    }

    private function validate_xpath($xml, $xpath)
    {
      $raw = $xml->xpath($xpath);

      if(!empty($raw[0]))
      {
        return (string) $raw[0];
      }
      else
      {
        return "";
      }
    }

    /**
     * Extract Subproject table values and insert row into mysql
     * @param  string $PID    pid of subproject fedora object
     * @param  string $parent pid of subproject parent
     */
    private function writeSubprojectTable($PID, $parent)
    {
      try
      {
        //get EAC-CPF datastream for Subproject
        $xml = $this->getDatastream($PID, "EAC-CPF");

        if($xml)
        {
          //register namespaces for EAC-CPF xml
          $xml->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
          $xml->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
          $xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

          //get subproject table data from EAC-CPF xml
          $tableValues = Array(
            'sidora_subproject_id' => $PID,
            'ct_subproject_id' => $this->validate_xpath($xml, '//eac:recordId/text()'),
            'name' => $this->validate_xpath($xml, '//eac:nameEntry[@localType="primary"]/eac:part/text()'),
            'sidora_project_id' => $parent,
            'abbreviation' => $this->validate_xpath($xml, '//eac:nameEntry[@localType="abbreviation"]/eac:part/text()'),
            'project_design' => $this->validate_xpath($xml, '//eac:function[eac:term/text()="Project Design"]/eac:descriptiveNote/*')
          );

          //insert data into subprojects table
          $sql = $this->tableInsert("subprojects", $tableValues);


          if($sql['status']) //successful insert
          {
            $this->executionlog .= "DEBUG: SUBPROJECT $PID - Message: " . $sql['message'] . "\n";
            echo "DEBUG: SUBPROJECT $PID - Message: " . $sql['message'] . "\n";
          }
          else //failed insert
          {
            //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
            if (strpos($sql['message'], 'Duplicate entry') === false)
            {
              $this->executionlog .= "ERROR: SUBPROJECT $PID - " . $sql['message'] . "\n";
              echo "ERROR: SUBPROJECT $PID - " . $sql['message'] . "\n";
              array_push($this->errorpids, $PID);
            }
          }
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .="ERROR: SUBPROJECT $PID - $e\n";
        echo "ERROR: SUBPROJECT $PID - $e\n";
        array_push($this->errorpids, $PID);
      }
    }

    /**
     * Extract plot table values and insert row into mysql
     * @param  string $PID    fedora pid of plot
     * @param  string $parent fedora pid of plot parent object
     */
    private function writePlotTable($PID, $parent)
    {
      try
      {
        //get FGDC-CTPlot datastream for plot
        $xml = $this->getDatastream($PID, 'FGDC-CTPlot');

        if(!$xml)
        {
          $xml = $this->getDatastream($PID, 'FGDC-Research');
        }

        if($xml)
        {
          //register namespaces for FGDC-CTPlot xml
          $xml->registerXPathNamespace("fgdc", "http://localhost/");
          $xml->registerXPATHNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

          //get plot table data from FGDC-CTPlot xml
          $tableValues = Array(
            'sidora_plot_id' => $PID,
            'name' => $this->validate_xpath($xml, '//title/text()'),
            'treatment' => $this->validate_xpath($xml, '//fgdc:dataqual/fgdc:lineage/fgdc:method/fgdc:methdesc/text()'),
            'sidora_subproject_id' => $parent
          );

          //insert data into plots table
          $sql = $this->tableInsert("plots", $tableValues);

          if($sql['status']) //successful insert
          {
            $this->executionlog .= "DEBUG: PLOT $PID - Message: " . $sql['message'] . "\n";
            echo "DEBUG: PLOT $PID - Message: " . $sql['message'] . "\n";
          }
          else //failed insert
          {
            //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
            if (strpos($sql['message'], 'Duplicate entry') === false)
            {
              $this->executionlog .= "ERROR: PLOT $PID - " . $sql['message'] . "\n";
              echo "ERROR: PLOT $PID - " . $sql['message'] . "\n";
              array_push($this->errorpids, $PID);
            }
          }
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: PLOT $PID - $e\n";
        echo "ERROR: PLOT $PID - $e\n";
        array_push($this->errorpids, $PID);
      }
    }

    /**
     * Extract Deployment table values and insert row into mysql
     * @param  string $PID    fedora pid of deployment object
     * @param  string $parent fedora pid of deployment parent
     */
    private function writeDeploymentTable($PID, $parent)
    {
      try
      {
        //get manifest datastream for deployment
        $manifest = $this->getDatastream($PID, "MANIFEST");

        //get the parent subproject pid for the deployment
        $sub_vals = $this->getParentSubproject($parent);

        //get deployment camera metadata
        $camera_data = $this->getCameraMetadata($PID);

        //get deployment data from manifest xml
        $tableValues = Array(
          'sidora_deployment_id' => $PID,
          'sidora_subproject_id' => $sub_vals['subproject'],
          'sidora_plot_id' => $sub_vals['plot'],
          'ct_deployment_id' => $this->validate_xpath($manifest, '//CameraDeploymentID/text()'),
          'name' => $this->validate_xpath($manifest, '//CameraSiteName/text()'),
          'access_constraints' => $this->validate_xpath($manifest, '//AccessConstraints/text()'),
          'feature_type' => $this->validate_xpath($manifest, '//FeatureMethodology/text()'),
          'feature_description' => $this->validate_xpath($manifest, '//Feature/text()'),
          'bait_type' => $this->validate_xpath($manifest, '//Bait/text()'),
          'bait_description' => $this->validate_xpath($manifest, '//BaitDescription/text()'),
          'camera_id' => $this->validate_xpath($manifest, '//CameraID/text()'),
          'proposed_lat' => $this->validate_xpath($manifest, '//ProposedLatitude/text()'),
          'proposed_lon' => $manifest->xpath('//ProposedLongitude/text()')[0],
          'actual_lat' => $manifest->xpath('//ActualLatitude/text()')[0],
          'actual_lon' => $manifest->xpath('//ActualLongitude/text()')[0],
          'camera_make' => $camera_data['make'],
          'camera_model' => $camera_data['model'],
          'camera_failure_details' => $this->validate_xpath($manifest, '//CameraFailureDetails/text()'),
          'detection_distance' => $this->validate_xpath($manifest, '//DetectionDistance/text()'),
          'sensitivity_setting' => $this->validate_xpath($manifest, '//SensitivitySetting/text()'),
          'quiet_period_setting' => $this->validate_xpath($manifest, '//QuietPeriodSetting/text()'),
          'image_resolution_setting' => $this->validate_xpath($manifest, '//ImageResolutionSetting/text()'),
          'deployment_notes' => $this->validate_xpath($manifest, '//CameraDeploymentNotes')
        );

        //insert data into deployments table
        $sql = $this->tableInsert("deployments", $tableValues);

        if($sql['status']) //successful insert
        {
          $this->executionlog .= "DEBUG: DEPLOYMENT $PID - Message: " . $sql['message'] . "\n";
          echo "DEBUG: DEPLOYMENT $PID - Message: " . $sql['message'] . "\n";
        }
        else //failed insert
        {
          //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
          if (strpos($sql['message'], 'Duplicate entry') === false)
          {
            $this->executionlog .= "ERROR: Deployment $PID - " . $sql['message'] . "\n";
            echo "ERROR: Deployment $PID - " . $sql['message'] . "\n";
            array_push($this->errorpids, $PID);
          }
        }

        /*
          get # of observations for this deployment and add it to the running total of
          observations gathered from fedora
        */
        $this->observationCount += $this->getObstableObservationsCount($PID);

        //call getObservations to populate observations datatable.
        //$this->getObservations($PID, $manifest);
        $this->getObstableObservations($PID);
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR Deployment $PID - $e\n";
        echo "ERROR Deployment $PID - $e\n";
        array_push($this->errorpids, $PID);
      }
    }

    /**
     * Extract observations from deployment manifest
     * @param  string $PID      Deployment pid
     * @param  SimpleXMLElement $manifest deployment manifest xml
     */
    private function getObservations($PID, $manifest)
    {
      /*
        get the number of observations under corresponding deployment PID in both
        fedora and mysql
      */
      $mysql_observation_count  = $this->getObservationSubsetLength($PID);
      $fedora_observation_count = $this->getObstableObservationsCount($PID);

      /*
        if the #observations in fedora under the deployment do not match the number
        in the mysql database, repopulate the data in mysql.
      */
      if($mysql_observation_count != $fedora_observation_count)
      {
        //if there are already observations in the db, drop them and repopulate
        if($mysql_observation_count > 0)
        {
          $this->dropObservations($PID);
        }

        try
        {
          //get a list of image sequence IDs from manifest
          $sequences = $manifest->xpath("//ImageSequence/ImageSequenceId");

          foreach($sequences as $seq)
          {
            //base xpath using sequence ID
            $xpath = "//ImageSequence[ImageSequenceId='$seq']";

            //xpath to researcher identification
            $resXPATH = "$xpath/ResearcherIdentifications/Identification";

            //get # of researcher identifications corresponding to the sequence ID
            $resCount = count($manifest->xpath("$resXPATH"));

            for($i = 1; $i < $resCount + 1; $i++) //parse each researcher observation
            {
              $this->parseObservation($PID, $manifest, $seq, $i, "Researcher");
            }
          }
        }
        catch(Exception $e)
        {
          $this->executionlog .= "ERROR: getObservations() Deployment $PID - Message: " . $e->getMessage() . "\n";
          echo "ERROR: getObservations() Deployment $PID - Message: " . $e->getMessage() . "\n";
        }
      }
    }

    private function getObstableObservations($PID)
    {
      $mysql_observation_count  = $this->getObservationSubsetLength($PID);
      $fedora_observation_count = $this->getObservationsCount($PID);

      /*
        if the #observations in fedora under the deployment do not match the number
        in the mysql database, repopulate the data in mysql.
      */
      if($mysql_observation_count != $fedora_observation_count)
      {
        //if there are already observations in the db, drop them and repopulate
        if($mysql_observation_count > 0)
        {
          $this->dropObservations($PID);
        }

        $rels = $this->getRelsExtData($PID);

        $count = 1;

        foreach($rels['children'] as $child)
        {
          $dc = $this->checkDublinCoreTitle($child, "Researcher Observations");

          if($dc)
          {
            $url = "objects/$child/datastreams/CSV/content";
            $obstable = $this->sianctapiGetDataFromFedora($url);

            if(!$obstable)
            {
              $this->executionlog = "ERROR: Researcher Observations Table For Deployment $PID Could Not Be Read\n";
              echo "ERROR: Researcher Observations Table For Deployment $PID Could Not Be Read\n";
            }
            else
            {
              $lines = explode("\n", trim($obstable));

              foreach($lines as $line)
              {
                $line = trim($line);
                $values = explode(',', $line);

                if(!empty($values[14]))
                {
                  $speciesValues = Array(
                    "iucn_id" => $values[14],
                    "tsn_id" => $this->validate_array_value($values, 13),
                    "iucn_status" => "placeholder",
                    "scientific_name" => str_replace("\"", "", $values[5]),
                    "common_name" => str_replace("\"", "", $values[6])
                  );

                  $sql_species = $this->tableInsert("species", $speciesValues);

                  if($sql_species['status']) //successful insert
                  {
                    $this->executionlog .= "DEBUG: Species " . $speciesValues['iucn_id'] . " - Message: " . $sql_species['message'] . "\n";
                    echo "DEBUG: Species " . $speciesValues['iucn_id'] . " - Message: " . $sql_species['message'] . "\n";
                  }
                  else //failed insert
                  {
                    //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
                    //there will be a lot of duplicate species even on a fresh rebuild
                    if(strpos($sql_species['message'], 'Duplicate entry') === false)
                    {
                      $this->executionlog .= "ERROR: Failed to insert species " . $speciesValues['iucn_id'] . " - Message:  " . $sql_species['message'] . "\n";
                      echo  "ERROR: Failed to insert species " . $speciesValues['iucn_id'] . " - Message:  " . $sql_species['message'] . "\n";
                    }
                  }
                }

                //get observation data from manifest xml
                $observationValues = Array(
                  "obstable_id" => $child,
                  "sequence_id" => $this->validate_array_value($values, 2),
                  "sidora_deployment_id" => $PID,
                  "begin_time" => $this->validate_array_value($values, 3),
                  "end_time" => $this->validate_array_value($values, 4),
                  "iucn_id" => $this->validate_array_value($values, 14),
                  "age" => $this->validate_array_value($values, 7),
                  "sex" => $this->validate_array_value($values, 8),
                  "individual" => $this->validate_array_value($values, 9),
                  "count" => $this->validate_array_value($values, 10),
                  "id_type" => $this->validate_array_value($values, 0)
                );

                if($observationValues['count'] == "")
                {
                  $observationValues['count'] = 0;
                }

                /*foreach($observationValues as $key=>$value)
                {
                  if(!$value || trim($value) == "")
                  {
                    $observationValues[$key] = "Unlisted";
                  }
                }*/

                //insert data into observations table
                $sql_observations = $this->tableInsert("observations", $observationValues);

                if($sql_observations['status']) //successful insert
                {
                  $this->executionlog .= "DEBUG: DEPLOYMENT $PID/OBSERVATION $count - Message: " . $sql_observations['message'] . "\n";
                  echo "DEBUG: DEPLOYMENT $PID/OBSERVATION $count - Message: " . $sql_observations['message'] . "\n";
                }
                else //failed insert
                {
                  //NOTE there is no way to determine duplicate observation entries from the data alone. This is why we compare contents in fedora and mysql instead
                  $this->executionlog .= "ERROR: Failed to Insert Deployment $PID/Observation $count - Message: " . $sql_observations['message'] . "\n";
                  echo "ERROR: Failed to Insert Deployment $PID/Observation $count - Message: " . $sql_observations['message'] . "\n";
                }

                $count ++;
              }
            }
          }
        }
      }
    }

    private function validate_array_value($array, $index)
    {
      if(!empty($array[$index]))
      {
        return $array[$index];
      }
      else
      {
        return "";
      }
    }

    /**
     * Get the number of observations contained in a researcher observations csv
     * @param  string $PID Deployment pid
     * @return int         Number of observation rows in the observation table csv
     */
    public function getObstableObservationsCount($PID)
    {
      $count = 0;

      $rels = $this->getRelsExtData($PID);

      foreach($rels['children'] as $child)
      {
        $dc = $this->checkDublinCoreTitle($child, "Researcher Observations");

        if($dc)
        {
          $url = "objects/$child/datastreams/CSV/content";
          $obstable = $this->sianctapiGetDataFromFedora($url);

          if(!$obstable)
          {
            $this->executionlog = "ERROR: Researcher Observations Table For Deployment $PID Could Not Be Read\n";
            echo "ERROR: Researcher Observations Table For Deployment $PID Could Not Be Read\n";
          }
          else
          {
            $lines = explode("\n", trim($obstable));
            $count = count($lines);
            break;
          }
        }
      }

      return $count;
    }

    /**
     * Extract Observation and species table values and insert respecgtive rows into mysql
     * @param  string $PID      Fedora pid for deployment
     * @param  SimpleXMLElement $manifest deployment manifest xml
     * @param  string $seq      image sequence id
     * @param  int $index    index of image sequence in manifest
     * @param  string $xpath    xpath base for querying manifest
     * @param  string $id_type  how the observation was id'd
     */
    private function parseObservation($PID, $manifest, $seq, $index, $id_type)
    {
      try
      {
        //base xpath
        $xpath = "//ImageSequence[ImageSequenceId='$seq']";

        //xpath to researcher identifications
        $idXPATH = "$xpath/ResearcherIdentifications";

        //get species iucn nubmer for researcher identification
        $iucn_id = (string) $manifest->xpath("$idXPATH/Identification[$index]/IUCNId")[0];

        //get species data from manifest xml
        $speciesValues = Array(
          "iucn_id" => $iucn_id,
          "tsn_id" => (string) $manifest->xpath("$idXPATH/Identification[$index]/TSNId")[0],
          "iucn_status" => "placeholder",
          "scientific_name" => (string) $manifest->xpath("$idXPATH/Identification[$index]/SpeciesScientificName")[0],
          "common_name" => (string) $manifest->xpath("$idXPATH/Identification[$index]/SpeciesCommonName")[0]
        );

        //insert data into species table
        $sql_species = $this->tableInsert("species", $speciesValues);

        if($sql_species['status']) //successful insert
        {
          $this->executionlog .= "DEBUG: Species " . $speciesValues['iucn_id'] . " - Message: " . $sql_species['message'] . "\n";
        }
        else //failed insert
        {
          //we filter out 'Duplicate entry' errors because these will occur if we rebuild without dropping database
          //there will be a lot of duplicate species even on a fresh rebuild
          if(strpos($sql_species['message'], 'Duplicate entry') === false)
          {
            $this->executionlog .= "ERROR: Failed to insert species " . $speciesValues['iucn_id'] . " - Message:  " . $sql_species['message'] . "\n";
          }
        }

        //get observation data from manifest xml
        $observationValues = Array(
          "sequence_id" => $seq,
          "sidora_deployment_id" => $PID,
          "begin_time" => (string) $manifest->xpath("$xpath/ImageSequenceBeginTime")[0],
          "end_time" => (string) $manifest->xpath("$xpath/ImageSequenceEndTime")[0],
          "iucn_id" => $iucn_id,
          "age" => (string) $manifest->xpath("$idXPATH/Identification[$index]/Age")[0],
          "sex" => (string) $manifest->xpath("$idXPATH/Identification[$index]/Sex")[0],
          "individual" => (string) $manifest->xpath("$idXPATH/Identification[$index]/IndividualId/text()")[0],
          "count" => (string) $manifest->xpath("$idXPATH/Identification[$index]/Count")[0],
          "id_type" => $id_type
        );

        //insert data into observations table
        $sql_observations = $this->tableInsert("observations", $observationValues);

        if($sql_observations['status']) //successful insert
        {
          $this->executionlog .= "DEBUG: DEPLOYMENT $PID/OBSERVATION $index - Message: " . $sql_observations['message'] . "\n";
        }
        else //failed insert
        {
          //NOTE there is no way to determine duplicate observation entries from the data alone. This is why we compare contents in fedora and mysql instead
          $this->executionlog .= "ERROR: Failed to Insert Deployment $PID/Observation $index - Message: " . $sql_observations['message'] . "\n";
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: Failer to Insert Deployment $PID/Observation $index - Message: " . $e->getMessage() . "\n";
      }
    }

    /**Helper Functions**/

    /**
     * Reset log data so that logs do not carry data from rebuild to rebuild if
     * the class is reused.
     */
    private function resetLogData()
    {
      $this->isSubsetOfRepo = TRUE;

      $this->projectCount = 0;
      $this->subprojectCount = 0;
      $this->plotCount = 0;
      $this->deploymentCount = 0;
      $this->observationCount = 0;

      $this->errorpids = Array();

      $this->executionlog = '';
    }

    /**
     * log function for sianct mysql operations
     * @param  string $message message to log
     * @param  string $logfile output file path
     */
    private function log($message, $logfile)
    {
      error_log("$message\n", 3, $logfile);
    }

    /**
     * Get the PID for the deployment subproject if it has a plot for a direct parent
     * @param  string $PID pid of deployment parent
     * @return array      Return array with plot and subproject pids and error message
     */
    private function getParentSubproject($PID)
    {
      $results = Array(
        "subproject" => "",
        "plot" => ""
      );

      //get RELS-EXT datastream for PID
      $rels = $this->getRelsExtData($PID);

      //if plot, get subproject parent
      if($rels['type'] == 'si:ctPlotCModel')
      {
        $results["subproject"] = $rels['parent'];
        $results["plot"] = $PID;
      }
      else //assume $PID IS a subproject
      {
        $results["subproject"] = $PID;
        $results["plot"] = NULL;
      }

      return $results;
    }

    /**
     * Get camera metadata for deployment
     * @param  string $PID deployment PID
     * @return array      camera make and model values
     */
    public function getCameraMetadata($PID)
    {
      $results = Array
      (
        'make' => 'undefined',
        'model' => 'undefined'
       );

      try
      {
        //get deployment RELS-EXT datastream
        $rels = $this->getRelsExtData($PID);

        //get deployment child objects
        foreach($rels['children'] as $child)
        {
          $type = $this->getRelsExtData($child)['type'];

          //if image object
          if($type == 'si:generalImageCModel')
          {
            //get FITS datastream xml for child image object
            $xml = $this->getDatastream($child, "FITS");

            if(!$xml)
            {
              //if FITS datastream failed to parse, try next child
              continue;
              //return $results;
            }

            //register FITS xml namespaces
            $xml->registerXPathNamespace("fits", "http://hul.harvard.edu/ois/xml/ns/fits/fits_output");
            $xml->registerXPATHNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

            //get camera make
            $mk_result = $xml->xpath("/fits:fits/fits:metadata/fits:image/fits:digitalCameraManufacturer/text()");

            if(!empty($mk_result[0]))
            {
              $results['make'] = (string)$mk_result[0];
            }

            //if not null or empty, set camera make
            /*if($make != "" && $make != NULL)
            {
                $results['make'] = $make;
            }*/

            //get camera model
            $md_result = $xml->xpath("/fits:fits/fits:metadata/fits:image/fits:digitalCameraModelName/text()");

            if(!empty($md_result[0]))
            {
              $results['model'] = (string)$md_result[0];
            }


            //if not null or empty, set camera model
            /*if($model != "" && $model != NULL)
            {
                $results['model'] = $model;
            }*/
          }
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: Unable to Retrieve Camera Metadata for Deployment $PID - Message: " . $e->getMessage() . "\n";
        echo "ERROR: Unable to Retrieve Camera Metadata for Deployment $PID - Message: " . $e->getMessage() . "\n";
      }

      return $results;
    }

    /**
     * Get the number of observations contained in a deployment manifest
     * @param  string $PID      Deployment pid
     */
    public function getObservationsCount($deploymentPid)
    {
      $count = 0;

      //get manifest datastream xml for deployment
      $manifest = $this->getDatastream($deploymentPid, "MANIFEST");

      try
      {
        //get a list of image sequence IDs
        $sequences = $manifest->xpath("//ImageSequence/ImageSequenceId");

        foreach($sequences as $seq)
        {
          $xpath = "//ImageSequence[ImageSequenceId='$seq']";
          $resXPATH = "$xpath/ResearcherIdentifications/Identification";

          //get number of researcher identifications under each sequence
          $resCount = count($manifest->xpath("$resXPATH"));

          //add to count
          $count += $resCount;
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: Unable to retrieve observation count from Deployment $deploymentPid - Message: " . $e->getMessage() . "\n";
      }
      finally
      {
        return $count;
      }
    }

    /**
     * helper function to retrieve specific datastream xml for a fedora object
     * @param  string $PID fedora object PID
     * @param  string $ds  fedora datastream identifier
     * @return SimpleXMLElement   Datastream XML or NULL if failed
     */
    public function getDatastream($PID, $ds)
    {
      $datastream = NULL;

      try
      {
        $url = "objects/$PID/datastreams/$ds/content";

        $result = $this->sianctapiGetDataFromFedora($url);

        if($ds == "DC")
        {
          echo "DC RESULTS:\n$result\n";
          return $result;
        }

        $datastream = new SimpleXMLElement($result);
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: Failed to Retrieve Datastream $ds for Object $PID - Message: " . $e->getMessage() . "\n";
        echo "ERROR: Failed to Retrieve Datastream $ds for Object $PID - Message: " . $e->getMessage() . "\n";
      }
      finally
      {
        return $datastream;
      }
    }

    private function checkDublinCoreTitle($PID, $title)
    {
      $isPresent = FALSE;

      try
      {
        $url = "objects/$PID/datastreams/DC/content";

        $result = $this->sianctapiGetDataFromFedora($url);


        if(stripos($result, $title))
        {
          //echo "$title was found in \n$result";
          $isPresent = TRUE;
        }
      }
      catch(Exception $e)
      {
        $this->executionlog = "ERROR: Could not retrieve DC information for $PID - Message: ". $e->getMessage() . "\n";
        echo  "ERROR: Could not retrieve DC information for $PID - Message: ". $e->getMessage() . "\n";
      }
      finally
      {
        return $isPresent;
      }
    }

    /**
     * helper function to retrieve specific RELS-EXT data for a fedora object
     *
     * NOTE: Uses EasyRdf library
     *
     * @param  string $PID Fedora Object PID
     * @return array       set of pertinent RELS-EXT information
     */
    public function getRelsExtData($PID)
    {
      $Values = Array(
        'type' => 'undefined',
        'parent' => NULL,
        'children' => [],
        'isSubproject' => FALSE
      );

      $url = "objects/$PID/datastreams/RELS-EXT/content";

      $rels = $this->sianctapiGetDataFromFedora($url);

      if($rels) //if results aren't empty or NULL
      {
        //set RELS-EXT rdf namespaces
        EasyRdf_Namespace::set('fedora','info:fedora/fedora-system:def/relations-external#');
        EasyRdf_Namespace::set('fedoramodel','info:fedora/fedora-system:def/model#');
        EasyRdf_Namespace::set('dc', "http://purl.org/dc/elements/1.1/");
        EasyRdf_Namespace::set('oris', "http://oris.si.edu/2017/01/relations#");

        $qpid = 'info:fedora/' . $PID;
        $graph = new EasyRdf_Graph($qpid, $rels, 'rdfxml');

        //get isAdministeredBy node in RELS-EXT
        $admin_array = $graph->allResources($qpid, 'oris:isAdministeredBy');
        if(!empty($admin_array))
        {
          $admin = $admin_array[0];
        };

        if(!empty($admin))
        {
          //get parent object for $PID
          $Values['parent'] = preg_replace('/info:fedora\//', '', $admin->getUri());
        }

        //get fedora type for $PID object
        $model = $graph->allResources($qpid, 'fedoramodel:hasModel')[0];
        $Values['type'] = preg_replace('/info:fedora\//', '', $model->getUri());

        if($Values['type'] == 'si:projectCModel' && $Values['parent'] != 'si:121909')
        {
          $Values['isSubproject'] = TRUE;
        }

        //Get all possible children for $PID object
        $concepts  = $graph->allResources($qpid,'fedora:hasConcept');
        $resources = $graph->allResources($qpid, 'fedora:hasResource');
        $codebook  = $graph->allResources($qpid, 'fedora:managesCodebook');

        //merge into one array
        $children = array_merge($concepts, $resources, $codebook);

        //iterate through children and strip away excess text, leaving only PIDs
        foreach ($children as $child)
        {
          $child_pid = preg_replace('/info:fedora\//', '', $child->getUri());

          if ($child_pid != $PID)
          {
            $Values['children'][] = $child_pid;
          }
        }
      }

      return $Values;
    }

    /**
     * Get specific sianct object from Fedora
     * @param  string $params params to include in fedora query
     * @return boolean        true for success, false for failure
     */
    public function sianctapiGetDataFromFedora($params)
    {
      try
      {
        $fedoraURL = "http://" . $this->config['fedorahost'] . ":" . $this->config['fedoraport'] . "/fedora/$params?format=xml";

        $fedoraUserPass = $this->config['fedorauserpass'];

        $curlOptions = array(
          CURLOPT_USERPWD => $fedoraUserPass,
        );

        $fedoraResults = $this->curlWithRetries($fedoraURL, $curlOptions);

        if($fedoraResults['code']=='200')
        {
          return $fedoraResults['results'];
        }
        else
        {
          $this->executionlog .= "ERROR: " . $fedoraResults['log'] . "\n";
          return FALSE;
        }
      }
      catch(Exception $e)
      {
        $this->executionlog .= "ERROR: " . $e->getMessage() . "\n";
        return FALSE;
      }
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
    private function curlWithRetries($url, $curlOpts = array())
    {
      $maxRetries = 5;
      $retrySleep = 5;
      $return = array(
        'log' => '',
        'results' => FALSE,
        'code' => ''
      );

      try
      {
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
            $return['code'] = $info['http_code'];
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
      }
      catch(Exception $e)
      {
        $return['log'] = $e;
      }
      finally
      {
        return $return;
      }
    }

    /**mysql functions**/

    /**
     * Initialize the sianct mysql database and tables
     */
    public function initializeSianctDatabase()
    {
      //create new database if one has not been created
      $this->createDatabase();

      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
        die("Connection failed: " . $conn->connect_error);
      }

      //get sql scripts for creating datatables

      $prefix = "./sql/create";

      $sql_projects = file_get_contents("$prefix/create_projects_table.sql");
      $sql_subprojects = file_get_contents("$prefix/create_subprojects_table.sql");
      $sql_plots = file_get_contents("$prefix/create_plots_table.sql");
      $sql_deployments = file_get_contents("$prefix/create_deployments_table.sql");
      $sql_species = file_get_contents("$prefix/create_species_table.sql");
      $sql_observations = file_get_contents("$prefix/create_observations_table.sql");

      //create datatables if they aren't already created
      $this->createTable($conn, $sql_projects, "projects");
      $this->createTable($conn, $sql_subprojects, "subprojects");
      $this->createTable($conn, $sql_plots, "plots");
      $this->createTable($conn, $sql_deployments, "deployments");
      $this->createTable($conn, $sql_species, "species");
      $this->createTable($conn, $sql_observations, "observations");

      //close connection
      $conn->close();
    }

    /**
     * Create mysql database based on configuration parameters
     */
    private function createDatabase()
    {
      $conn = new mysqli($this->host, $this->user, $this->pass);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
        die("Connection failed: " . $conn->connect_error);
      }

      //query to create database
      $sql = "CREATE DATABASE $this->dbname";

      if ($conn->query($sql) === TRUE) //on success
      {
        $this->executionlog .= "DEBUG: Database '$this->dbname' created successfully" . "\n";
      }
      elseif(strpos($conn->error, 'database exists') === false) //on failure, filtering out 'database exists' errors
      {
        $this->executionlog .= "ERROR: creating database - Message: " . $conn->error . "\n";
      }

      $sql = "GRANT ALL PRIVILEGES ON $this->dbname TO '$this->user'@'$this->host'";

      if ($conn->query($sql) === TRUE)
      {
        $this->executionlog .= "DEBUG: All privileges on '$this->dbname' granted to '$this->user'@'$this->host' successfully" . "\n";
      }
      elseif($conn->error === FALSE) //on failure, filtering out 'database exists' errors
      {
        $this->executionlog .= "ERROR: Granting privelges on $this->dbname failed for '$this->user'@'$this->host' - Message: " . $conn->error . "\n";
      }
    }

    /**
     * Delete the siant mysql database
     * @return [type] [description]
     */
    public function deleteDatabase()
    {
      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
        die("Connection failed: " . $conn->connect_error);
      }

      //query to delete database
      $sql = "DROP DATABASE $this->dbname";

      if ($conn->query($sql) === TRUE) //on success
      {
        $this->executionlog .= "DEBUG: Database $this->dbname Deleted Successfully" . "\n";
      }
      else //on failure
      {
        $this->executionlog .= "ERROR: " . $conn->error . "\n";
      }
    }

    /**
     * Create msyql data table
     * @param  mysqli $conn  mysql connection
     * @param  string $sql   sql query
     * @param  string $table table name
     */
    private function createTable($conn, $sql, $table)
    {
      if ($conn->query($sql) === TRUE) //on success
      {
        $this->executionlog .= "DEBUG: Table $table created successfully in Database '$this->dbname'" . "\n";
      }
      elseif(strpos($conn->error, 'already exists') === false) //on failure, filtering out 'already exists' errors
      {
        $this->executionlog .= "ERROR: " . $conn->error . "\n";
      }
    }

    /**
     * Insert a row of data into a specific table in the database
     * @param  string $table name of table
     * @param  array $data   array of key=>value pairs corresponding to a data entry
     * @return array         an array containing a status (TRUE or FALSE) and error or success message from MySQL
     */
    public function tableInsert($table, $data)
    {
      $results = Array(
        'status' => TRUE,
        'message' => ''
      );

      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
      }

      $cols = "";
      $vals = "";

      $count = 1;

      //parse row names and data
      foreach($data as $key => $value)
      {
        $cols .= $key;
        $vals .= '"' . $value . '"';

        if($count < count($data))
        {
          $cols .= ",";
          $vals .= ",";
        }

        $count++;
      }

      //query to insert data
      $sql = "INSERT INTO $table ($cols) VALUES ($vals)";

      if ($conn->query($sql) === TRUE) //on success
      {
        $results['message'] = "New record created successfully in $table";
      }
      else //on failure
      {
        $results['message'] = "Error: " . $sql . "<br>" . $conn->error;
        $results['status'] = FALSE;
      }

      return $results;
    }

    /**
     * Get the # of rows in a specified table
     * @param  string $table name of table
     * @return int           Row count of table
     */
    public function getTableLength($table)
    {
      $count = 0;

      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
      }

      //query selecting all rows from specified table
      $sql = "SELECT * FROM $table";

      if ($result = $conn->query($sql)) //on success
      {
        /* determine number of rows result set */
        $count = $result->num_rows;
        /* close result set */
        $result->close();
      }

      /* close connection */
      $conn->close();

      return $count;
    }

    /**
     * Get # of observation table rows corresponding to a specified deployment
     * @param string $deployment  $PID of deployment
     * @return int   Row count of observations corresponding to deployment
     */
    public function getObservationSubsetLength($deployment)
    {
      $count = 0;

      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog .= "ERROR: " . $conn->connect_error . "\n";
        echo "ERROR: " . $conn->connect_error . "\n";
      }

      //query to select all observation table rows corresponding to deployment
      $sql = "SELECT observation_id FROM observations WHERE sidora_deployment_id=\"$deployment\"";

      if ($result = $conn->query($sql)) //on success
      {
        /* determine number of rows result set */
        $count = $result->num_rows;
        /* close result set */
        $result->close();
      }

      /* close connection */
      $conn->close();

      return $count;
    }

    /**
     * Remove observation table rows corresponding to specified deployment
     * @param  string $deployment PID of fedora deployment
     */
    public function dropObservations($deployment)
    {
      //get current # of observations under deployment in observations table
      $obscount = $this->getObservationSubsetLength($deployment);

      //connect to the database using passed or configured credentials
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->executionlog = "ERROR: Failed to delete observation rows corresponding to deployment $deployment - Message: $conn->connect_error\n";
        echo "ERROR: Failed to delete observation rows corresponding to deployment $deployment - Message: $conn->connect_error\n";
      }

      //query to delete observation records
      $sql = "DELETE FROM observations WHERE sidora_deployment_id=\"$deployment\"";

      if ($conn->query($sql) === TRUE) //on success
      {
        $this->executionlog .= "DEBUG: Deleting " . $obscount . " Observations under Deployment $deployment From Table Observations in Database '$this->dbname'\n";
        echo "DEBUG: Deleting " . $obscount . " Observations under Deployment $deployment From Table Observations in Database '$this->dbname'\n";
      }
      else //on failure
      {
        $this->executionlog .= "ERROR: Failed to Delete Observations Under Deployment $deployment - Message: " . $conn->error . "\n";
        echo "ERROR: Failed to Delete Observations Under Deployment $deployment - Message: " . $conn->error . "\n";
      }

      $conn->close();
    }
  }
?>
