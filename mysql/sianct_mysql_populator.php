<?php

  class sianct_mysql_populator
  {
    public function __construct($db=NULL)
    {
      $this->config = parse_ini_file('sianct.ini');

      $dbvals = $db ? $db : $this->config;

      $this->host = $dbvals['host'];
      $this->user = $dbvals['user'];
      $this->pass = $dbvals['pass'];
      $this->dbname = $dbvals['dbname'];

      $date = new DateTime('NOW');
      $prefix= $date->format('m-d-Y_H:i:s');

      $this->error= './log/' . $prefix . '_error.log';
      $this->debug = './log/' . $prefix . '_debug.log';
    }

    /**
     * Recursive method for retrieving fedora object pids
     * @param  string  $PID           object pid
     * @param  boolean $parentproject true if object parent is of type project
     * @param  string  $parent        pid of object parent
     */
    public function findDeployments($PID, $parentproject=FALSE, $parent=NULL)
    {
      $url = "objects/$PID/datastreams/RELS-EXT/content";

      $rels = $this->sianctapiGetDataFromFedora($url);

      if($rels)
      {
        EasyRdf_Namespace::set('fedora','info:fedora/fedora-system:def/relations-external#');
        EasyRdf_Namespace::set('fedoramodel','info:fedora/fedora-system:def/model#');

        $qpid = 'info:fedora/' . $PID;
        $graph = new EasyRdf_Graph($qpid, $rels, 'rdfxml');

        $model = $graph->allResources($qpid, 'fedoramodel:hasModel')[0];

        $fedora_type = preg_replace('/info:fedora\//', '', $model->getUri());

        $concepts = $graph->allResources($qpid,'fedora:hasConcept');
        $children = array();

        foreach ($concepts as $concept)
        {
          $child = preg_replace('/info:fedora\//', '', $concept->getUri());

          if ($child != $pid) {
            $children[] = $child;
          }
        }

        if($fedora_type == "si:cameraTrapCModel")
        {
          $this->log("Deployment $PID: \n", $this->debug);
          $this->writeDeploymentTable($PID, $parent);
        }
        else
        {
          if($fedora_type == "si:projectCModel" && $PID != "si:121909")
          {
            if($parentproject)
            {
              $this->log("Subproject $PID:", $this->debug);
              $this->writeSubprojectTable($PID, $parent);
            }
            else
            {
              $this->log("Project $PID:", $this->debug);
              $this->writeProjectTable($PID);
            }
          }
          elseif($fedora_type == "si:ctPlotCModel")
          {
            $this->log("Plot $PID:", $this->debug);
            $this->writePlotTable($PID, $parent);
          }

          $this->log("$fedora_type $PID child count: " . count($children), $this->debug);

          //recursively call children
          foreach($children as $child)
          {
            $hasProjectParent = ($fedora_type == "si:projectCModel" && $PID != "si:121909");
            $this->findDeployments($child, $hasProjectParent, $PID);
          }
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
        $url = "objects/$PID/datastreams/EAC-CPF/content";
        $res = $this->sianctapiGetDataFromFedora($url);

        $xml = new SimpleXMLElement($res);

        $xml->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
        $xml->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
        $xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

        $tableValues = Array(
          'sidora_project_id' => $PID,
          'ct_project_id' => (string) $xml->xpath('//eac:recordId/text()')[0],
          'name' => (string) $xml->xpath('//eac:nameEntry[@localType="primary"]/eac:part')[0],
          'country_code' => (string) $xml->xpath('//eac:placeEntry/@countryCode')[0],
          'lat' => (string) $xml->xpath('//eac:placeEntry/@latitude')[0],
          'lon' => (string) $xml->xpath('//eac:placeEntry/@longitude')[0],
          'publish_date' => (string) $xml->xpath('//eac:localControl[@localType="Publication Date"]/eac:date/text()')[0],
          'objectives' => strip_tags((string) $xml->xpath('//eac:functions/eac:function[eac:term/text()="Project Objectives"]/eac:descriptiveNote/*')[0]),
          'data_constraints' => $xml->xpath('//eac:functions/eac:function[eac:term/text()="Project Data Access and Use Constraints"]/eac:descriptiveNote/*')[0],
          'owner' => (string) $xml->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Owner"]/eac:relationEntry/text()')[0],
          'email' => $xml->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Contact"]/eac:placeEntry/text()')[0],
          'principal_investigator' => $xml->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Principal Investigator"]/eac:relationEntry/text()')[0]
        );

        $sql = $this->table_insert("projects", $tableValues);

        if($sql['status'])
        {
          $this->log($sql['message'], $this->debug);
        }
        else
        {
          $this->log($sql['message'], $this->error);
        }

      }
      catch(Exceptions $e)
      {
        $this->log($e, $this->error);
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
        $url = "objects/$PID/datastreams/EAC-CPF/content";
        $res = $this->sianctapiGetDataFromFedora($url);
        $xml = new SimpleXMLElement($res);

        $xml->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
        $xml->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
        $xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

        $tableValues = Array(
          'sidora_subproject_id' => $PID,
          'ct_subproject_id' => $xml->xpath('//eac:recordId/text()')[0],
          'name' => $xml->xpath('//eac:nameEntry[@localType="primary"]/eac:part/text()')[0],
          'sidora_project_id' => $parent,
          'abbreviation' => $xml->xpath('//eac:nameEntry[@localType="abbreviation"]/eac:part/text()')[0],
          'project_design' => $xml->xpath('//eac:function[eac:term/text()="Project Design"]/eac:descriptiveNote/*')[0]
        );

        $sql = $this->table_insert("subprojects", $tableValues);

        if($sql['status'])
        {
          $this->log($sql['message'], $this->debug);
        }
        else
        {
          $this->log($sql['message'], $this->error);
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
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
        $url = "objects/$PID/datastreams/FGDC-CTPlot/content";
        $res = $this->sianctapiGetDataFromFedora($url);

        $xml = new SimpleXMLElement($res);

        $xml->registerXPathNamespace("fgdc", "http://localhost/");
        $xml->registerXPATHNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

        $tableValues = Array(
          'sidora_plot_id' => $PID,
          'name' => $xml->xpath('//title/text()')[0],
          'treatment' => $xml->xpath('//fgdc:citeinfo/fgdc:treatment/text()')[0],
          'sidora_subproject_id' => $parent
        );

        $sql = $this->table_insert("plots", $tableValues);

        if($sql['status'])
        {
          $this->log($sql['message'], $this->debug);
        }
        else
        {
          $this->log($sql['message'], $this->error);
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
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
        $url = "objects/$PID/datastreams/MANIFEST/content";
        $result = $this->sianctapiGetDataFromFedora($url);

        $manifest = new SimpleXMLElement($result);

        $sub_vals = $this->getParentSubproject($parent);

        //$camera_data = $this->getCameraMetadata($PID);

        $tableValues = Array(
          'sidora_deployment_id' => $PID,
          'sidora_subproject_id' => $sub_vals['subproject'],
          'sidora_plot_id' => $sub_vals['plot'],
          'ct_deployment_id' => $manifest->xpath('//CameraDeploymentID/text()')[0],
          'name' => $manifest->xpath('//CameraSiteName/text()')[0],
          'access_constraints' => $manifest->xpath('//AccessConstraints/text()')[0],
          'feature_type' => $manifest->xpath('//FeatureMethodology/text()')[0],
          'feature_description' => $manifest->xpath('//Feature/text()')[0],
          'bait_type' => $manifest->xpath('//Bait/text()')[0],
          'bait_description' => $manifest->xpath('//BaitDescription/text()')[0],
          'camera_id' => $manifest->xpath('//CameraID/text()')[0],
          'proposed_lat' => $manifest->xpath('//ProposedLatitude/text()')[0],
          'proposed_lon' => $manifest->xpath('//ProposedLongitude/text()')[0],
          'actual_lat' => $manifest->xpath('//ActualLatitude/text()')[0],
          'actual_lon' => $manifest->xpath('//ActualLongitude/text()')[0],
          'camera_make' => $camera_data['make'],
          'camera_model' => $camera_data['model'],
          'camera_failure_details' => $manifest->xpath('//CameraFailureDetails/text()')[0],
          'detection_distance' => $manifest->xpath('//DetectionDistance/text()')[0],
          'sensitivity_setting' => $manifest->xpath('//SensitivitySetting/text()')[0],
          'quiet_period_setting' => $manifest->xpath('//QuietPeriodSetting/text()')[0],
          'image_resolution_setting' => $manifest->xpath('//ImageResolutionSetting/text()')[0],
          'deployment_notes' => (string) $manifest->xpath('//CameraDeploymentNotes')[0]
        );

        $sql = $this->table_insert("deployments", $tableValues);

        if($sql['status'])
        {
          $this->log($sql['message'], $this->debug);
        }
        else
        {
          $this->log($sql['message'], $this->error);
        }

        $this->getObservations($PID);
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }
    }

    /**
     * Extract observations from deployment manifest
     * @param  string $PID      Deployment pid
     * @param  SimpleXMLElement $manifest deployment manifest xml
     */
    private function getObservations($PID)
    {
      $manifest = $this->getDatastream($PID, "MANIFEST");

      try
      {
        $sequences = $manifest->xpath("//ImageSequence/ImageSequenceId");

        foreach($sequences as $seq)
        {
          $xpath = "//ImageSequence[ImageSequenceId='$seq']";
          $resXPATH = "$xpath/ResearcherIdentifications/Identification";

          $resCount = count($manifest->xpath("$resXPATH"));

          for($i = 1; $i < $resCount + 1; $i++)
          {
            //echo "Parsing identification $i/$resCount for deployment: $PID, sequence: $seq\n";
            $this->parseObservation($PID, $manifest, $seq, $i, $xpath, "Researcher");
          }
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }


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
    private function parseObservation($PID, $manifest, $seq, $index, $xpath, $id_type)
    {
      try
      {
        if($id_type == "Researcher")
        {
          $idXPATH = "$xpath/ResearcherIdentifications";
        }
        else
        {
          $idXPATH = "$xpath/VolunteerIdentifications";
        }

        $iucn_id = (string) $manifest->xpath("$idXPATH/Identification[$index]/IUCNId")[0];

        $speciesValues = Array(
          "iucn_id" => $iucn_id,
          "tsn_id" => (string) $manifest->xpath("$idXPATH/Identification[$index]/TSNId")[0],
          "iucn_status" => "placeholder",
          "scientific_name" => (string) $manifest->xpath("$idXPATH/Identification[$index]/SpeciesScientificName")[0],
          "common_name" => (string) $manifest->xpath("$idXPATH/Identification[$index]/SpeciesCommonName")[0]
        );

        $sql_species = $this->table_insert("species", $speciesValues);

        if($sql_species['status'])
        {
          $this->log($sql_species['message'], $this->debug);
        }
        else
        {
          $this->log($sql_species['message'], $this->error);
        }

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

        $sql_observations = $this->table_insert("observations", $observationValues);

        if($sql_observations['status'])
        {
          $this->log($sql_observations['message'], $this->debug);
        }
        else
        {
          $this->log($sql_observations['message'], $this->error);
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }
    }

    /**Helper Functions**/

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
        "plot" => "",
        "error" => FALSE
      );

      try
      {
        $url = "objects/$PID/datastreams/RELS-EXT/content";

        $rels = $this->sianctapiGetDataFromFedora($url);

        if($rels)
        {
          EasyRdf_Namespace::set('fedora','info:fedora/fedora-system:def/relations-external#');
          EasyRdf_Namespace::set('fedoramodel','info:fedora/fedora-system:def/model#');
          EasyRdf_Namespace::set('administered', 'http://oris.si.edu/2017/01/relations#');

          $qpid = 'info:fedora/' . $PID;
          $graph = new EasyRdf_Graph($qpid, $rels, 'rdfxml');

          $model = $graph->allResources($qpid, 'fedoramodel:hasModel')[0];

          $type = preg_replace('/info:fedora\//', '', $model->getUri());

          if($type=="si:ctPlotCModel")
          {
            $admin = $graph->allResources($qpid, 'administered:isAdministeredBy')[0];

            $results["subproject"] = preg_replace('/info:fedora\//', '', $admin->getUri());
            $results["plot"] = $PID;
          }
          else
          {
            $results["subproject"] = $PID;
            $results["plot"] = NULL;
          }
        }
        else
        {
          $results["error"] = TRUE;
          $this->log("ERROR: UNABLE TO RETRIEVE RELS-EXT FOR OBJECT: $PID", $this->error);
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
        $results["error"] = TRUE;
      }
      finally
      {
        return $results;
      }
    }

    public function getCameraMetadata($PID)
    {
      $results = Array
      (
        'make' => 'undefined',
        'model' => 'undefined'
       );

      try
      {
        $rels = $this->getRelsExtData($PID);

        foreach($rels['children'] as $child)
        {
          $type = $this->getRelsExtData($child)['type'];

          if($type == 'si:generalImageCModel')
          {
            $url = "objects/$child/datastreams/FITS/content";
            $res = $this->sianctapiGetDataFromFedora($url);

            $xml = new SimpleXMLElement($res);

            $xml->registerXPathNamespace("fits", "http://hul.harvard.edu/ois/xml/ns/fits/fits_output");
            $xml->registerXPATHNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

            $make = (string) $xml->xpath("/fits:fits/fits:metadata/fits:image/fits:digitalCameraManufacturer/text()")[0];

            if($make != "" && $make != NULL)
            {
                $results['make'] = $make;
            }

            $model = (string) $xml->xpath("/fits:fits/fits:metadata/fits:image/fits:digitalCameraModelName/text()")[0];

            if($model != "" && $model != NULL)
            {
                $results['model'] = $model;
            }
          }
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }

      return $results;
    }

    /**
     * Extract observations from deployment manifest
     * @param  string $PID      Deployment pid
     * @param  SimpleXMLElement $manifest deployment manifest xml
     */
    public function getObservationsCount($deploymentPid)
    {
      $count = 0;
      $manifest = $this->getDatastream($deploymentPid, "MANIFEST");

      try
      {
        $sequences = $manifest->xpath("//ImageSequence/ImageSequenceId");

        foreach($sequences as $seq)
        {
          $xpath = "//ImageSequence[ImageSequenceId='$seq']";
          $resXPATH = "$xpath/ResearcherIdentifications/Identification";

          $resCount = count($manifest->xpath("$resXPATH"));

          $count += $resCount;
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }
      finally
      {
        return $count;
      }
    }

    public function getDatastream($PID, $ds)
    {
      $datastream = NULL;

      try
      {
        $url = "objects/$PID/datastreams/$ds/content";
        $result = $this->sianctapiGetDataFromFedora($url);

        $datastream = new SimpleXMLElement($result);
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
      }
      finally
      {
        return $datastream;
      }
    }

    public function getRelsExtData($PID)
    {
      $Values = Array(
        'type' => 'undefined',
        'parent' => NULL,
        'children' => []
      );

      $url = "objects/$PID/datastreams/RELS-EXT/content";

      $rels = $this->sianctapiGetDataFromFedora($url);

      if($rels)
      {
        EasyRdf_Namespace::set('fedora','info:fedora/fedora-system:def/relations-external#');
        EasyRdf_Namespace::set('fedoramodel','info:fedora/fedora-system:def/model#');
        EasyRdf_Namespace::set('dc', "http://purl.org/dc/elements/1.1/");
        EasyRdf_Namespace::set('oris', "http://oris.si.edu/2017/01/relations#");

        $qpid = 'info:fedora/' . $PID;
        $graph = new EasyRdf_Graph($qpid, $rels, 'rdfxml');

        $admin = $graph->allResources($qpid, 'oris:isAdministeredBy')[0];

        if($admin != NULL)
        {
          $Values['parent'] = preg_replace('/info:fedora\//', '', $admin->getUri());
        }

        $model = $graph->allResources($qpid, 'fedoramodel:hasModel')[0];

        $Values['type'] = preg_replace('/info:fedora\//', '', $model->getUri());

        $concepts  = $graph->allResources($qpid,'fedora:hasConcept');
        $resources = $graph->allResources($qpid, 'fedora:hasResource');
        $codebook  = $graph->allResources($qpid, 'fedora:managesCodebook');

        $children = array_merge($concepts, $resources, $codebook);


        foreach ($children as $child)
        {
          $child_pid = preg_replace('/info:fedora\//', '', $child->getUri());

          if ($child_pid != $pid)
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
          $this->log($fedoraResults['log'], $this->error);
          return FALSE;
        }
      }
      catch(Exception $e)
      {
        $this->log($e, $this->error);
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
      $this->createDatabase();

      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->log("Connection failed: " . $conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      $sql_projects = file_get_contents($this->config["projects"]);
      $sql_subprojects = file_get_contents($this->config['subprojects']);
      $sql_plots = file_get_contents($this->config['plots']);
      $sql_deployments = file_get_contents($this->config['deployments']);
      $sql_species = file_get_contents($this->config['species']);
      $sql_observations = file_get_contents($this->config['observations']);

      $this->createTable($conn, $sql_projects, "projects");
      $this->createTable($conn, $sql_subprojects, "subprojects");
      $this->createTable($conn, $sql_plots, "plots");
      $this->createTable($conn, $sql_deployments, "deployments");
      $this->createTable($conn, $sql_species, "species");
      $this->createTable($conn, $sql_observations, "observations");

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
        $this->log("Connection failed: " . $conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      // Create database
      $sql = "CREATE DATABASE $this->dbname";

      if ($conn->query($sql) === TRUE)
      {
        $this->log("Database created successfully", $this->debug);
      }
      else
      {
        $this->log("Error creating database: " . $conn->error, $this->error);
      }
    }

    /**
     * Delete the siant mysql database
     * @return [type] [description]
     */
    public function deleteDatabase()
    {
      $conn = new mysqli($this->host, $this->user, $this->pass);

      // Check connection
      if ($conn->connect_error)
      {
        $this->log("Connection failed: " . $conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      // Create database
      $sql = "DROP DATABASE $this->dbname";

      if ($conn->query($sql) === TRUE)
      {
        $this->log("Database deleted successfully", $this->debug);
      }
      else
      {
        $this->log("Error deleting database: " . $conn->error, $this->error);
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
      if ($conn->query($sql) === TRUE)
      {
          $this->log("Table $table created successfully", $this->debug);
      }
      else
      {
          $this->log("Error creating table: " . $conn->error, $this->error);
      }
    }

    public function table_insert($table, $data)
    {
      $results = Array(
        'status' => TRUE,
        'message' => ''
      );

      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        $this->log($conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      $cols = "";
      $vals = "";

      $count = 1;

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

      $sql = "INSERT INTO $table ($cols) VALUES ($vals)";

      if ($conn->query($sql) === TRUE)
      {
        $results['message'] = "New record created successfully in $table";
      }
      else
      {
        $results['message'] = "Error: " . $sql . "<br>" . $conn->error;
        $results['status'] = FALSE;
      }

      return $results;
    }

    public function getTableLength($table)
    {
      $count = 0;

      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        //$this->log($conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      $sql = "SELECT * FROM $table";

      if ($result = $conn->query($sql))
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

    public function getObservationSubsetLength($deployment)
    {
      $count = 0;

      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

      // Check connection
      if ($conn->connect_error)
      {
        //$this->log($conn->connect_error, $this->error);
        die("Connection failed: " . $conn->connect_error);
      }

      $sql = "SELECT observation_id FROM observations WHERE sidora_deployment_id=\"$deployment\"";

      if ($result = $conn->query($sql))
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

    public function dropObservations($deployment)
    {
      // Create connection
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
      // Check connection
      if ($conn->connect_error)
      {
        die("Connection failed: " . $conn->connect_error);
      }

      // sql to delete a record
      $sql = "DELETE FROM observations WHERE sidora_deployment_id=\"$deployment\"";

      if ($conn->query($sql) === TRUE)
      {
        echo "Record deleted successfully";
      }
      else
      {
        echo "Error deleting record: " . $conn->error;
      }

      $conn->close();
    }

  }
?>
