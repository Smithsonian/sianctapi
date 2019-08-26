<?php
  require("sm_insert_data.php");
  require("sm_initialize_database.php");
  require 'vendor/autoload.php';

  if(file_exists(parse_ini_file('sianct.ini')['log']))
  {
    unlink(parse_ini_file('sianct.ini')['log']);
  }

  initialize_sianct_database();

  $PID = "si:121909";
  findDeployments($PID);

  $config = parse_ini_file("sianct.ini");

  function findDeployments($PID, $parentproject=FALSE, $parent=NULL)
  {
    $url = "objects/$PID/datastreams/RELS-EXT/content";

    $rels = sianctapiGetDataFromFedora($url);

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
        writeDeploymentTable($PID, $parent);
      }
      else
      {
        if($fedora_type == "si:projectCModel" && $PID != "si:121909")
        {
          if($parentproject)
          {
            writeSubprojectTable($PID, $parent);
          }
          else
          {
            writeProjectTable($PID);
          }
        }
        elseif($fedora_type == "si:ctPlotCModel")
        {
          writePlotTable($PID, $parent);
        }

        //recursively call children
        foreach($children as $child)
        {
          $hasProjectParent = ($fedora_type == "si:projectCModel" && $PID != "si:121909");
          findDeployments($child, $hasProjectParent, $PID);
        }
      }
    }
    else
    {
      error_log("ERROR: UNABLE TO RETRIEVE RELS-EXT FOR OBJECT: $PID\n", 3, parse_ini_file("sianct.ini")["log"]);
    }
  }

  function writeProjectTable($PID)
  {
    try
    {
      $tableValues = Array();
      $tableValues["sidora_project_id"] = $PID;

      $eac_url = "objects/$PID/datastreams/EAC-CPF/content";
      $result = sianctapiGetDataFromFedora($eac_url);

      $eacXML = new SimpleXMLElement($result);
      $eacXML->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
      $eacXML->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
      $eacXML->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

      $tableValues["ct_project_id"] = (string) $eacXML->xpath('//eac:recordId/text()')[0];
      $tableValues["name"] = (string) $eacXML->xpath('//eac:nameEntry[@localType="primary"]/eac:part')[0];
      $tableValues["country_code"] = (string) $eacXML->xpath('//eac:placeEntry/@countryCode')[0];
      $tableValues["lat"] = (string) $eacXML->xpath('//eac:placeEntry/@latitude')[0];
      $tableValues["lon"] = (string) $eacXML->xpath('//eac:placeEntry/@longitude')[0];
      $tableValues["publish_date"] = (string) $eacXML->xpath('//eac:localControl[@localType="Publication Date"]/eac:date/text()')[0];
      $tableValues["summary"] = "Placeholder";
      $tableValues["objectives"] = (string) $eacXML->xpath('//eac:functions/eac:function[eac:term/text()="Project Objectives"]/eac:descriptiveNote/*')[0];
      $tableValues["data_constraints"] = $eacXML->xpath('//eac:functions/eac:function[eac:term/text()="Project Data Access and Use Constraints"]/eac:descriptiveNote/*')[0];
      $tableValues["owner"] = (string) $eacXML->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Owner"]/eac:relationEntry/text()')[0];
      $tableValues["email"] = $eacXML->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Project Contact"]/eac:placeEntry/text()')[0];
      $tableValues["principal_investigator"] = $eacXML->xpath('//eac:relations/eac:cpfRelation[eac:descriptiveNote/eac:p/text()="Principal Investigator"]/eac:relationEntry/text()')[0];

      $results = table_insert("projects", $tableValues);
    }
    catch(Exceptions $e)
    {
      error_log("$e\n", 3, parse_ini_file("sianct.ini")['log']);
    }
  }

  function writeSubprojectTable($PID, $parent)
  {
    try
    {
      $tableValues = Array();
      $tableValues["sidora_subproject_id"] = $PID;

      $eac_url = "objects/$PID/datastreams/EAC-CPF/content";
      $result = sianctapiGetDataFromFedora($eac_url);
      $eacXML = new SimpleXMLElement($result);

      $eacXML->registerXPathNamespace("isbn", "urn:isbn:1-931666-33-4");
      $eacXML->registerXPathNamespace("eac", "urn:isbn:1-931666-33-4");
      $eacXML->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");

      $tableValues["ct_subproject_id"] = $eacXML->xpath('//eac:recordId/text()')[0];
      $tableValues["name"] = $eacXML->xpath('//eac:nameEntry[@localType="primary"]/eac:part/text()')[0];
      $tableValues["sidora_project_id"] = $parent;
      $tableValues["abbreviation"] = $eacXML->xpath('//eac:nameEntry[@localType="abbreviation"]/eac:part/text()')[0];
      $tableValues["project_design"] = $eacXML->xpath('//eac:function[eac:term/text()="Project Design"]/eac:descriptiveNote/*')[0];

      $results = table_insert("subprojects", $tableValues);
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function writePlotTable($PID, $parent)
  {
    try
    {
      $tableValues = Array();
      $tableValues["sidora_plot_id"] = $PID;

      $fgdc_url = "objects/$PID/datastreams/FGDC-CTPlot/content";
      $result = sianctapiGetDataFromFedora($fgdc_url);
      $fgdcXML = new SimpleXMLElement($result);
      $fgdcXML->registerXPathNamespace("fgdc", "http://localhost/");
      $fgdcXML->registerXPATHNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

      $tableValues["name"] = $fgdcXML->xpath('//title/text()')[0];
      $tableValues["treatment"] = $fgdcXML->xpath('//fgdc:citeinfo/fgdc:treatment/text()')[0] . " Placeholder";
      $tableValues["sidora_subproject_id"] = $parent;

      $results = table_insert("plots", $tableValues);
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function writeDeploymentTable($PID, $parent)
  {
    try
    {
      $tableValues = Array();
      $tableValues["sidora_deployment_id"] = $PID;

      $url = "objects/$PID/datastreams/MANIFEST/content";
      $result = sianctapiGetDataFromFedora($url);

      $manifest = new SimpleXMLElement($result);

      $sub_vals = getParentSubproject($parent);

      if($sub_vals["error"])
      {
        $tableValues["sidora_subproject_id"] = $PID;
      }
      else
      {
        $tableValues["sidora_subproject_id"] = $sub_vals["subproject"];
        $tableValues["sidora_plot_id"] = $sub_vals["plot"];
      }

      $tableValues["ct_deployment_id"] = $manifest->xpath('//CameraDeploymentID/text()')[0];
      $tableValues["name"] = $manifest->xpath('//CameraSiteName/text()')[0];
      $tableValues["access_constraints"] = $manifest->xpath('//AccessConstraints/text()')[0];
      $tableValues["feature_type"] = $manifest->xpath('//FeatureMethodology/text()')[0];
      $tableValues["feature_description"] = $manifest->xpath('//Feature/text()')[0];
      $tableValues["bait_type"] = $manifest->xpath('//Bait/text()')[0];
      $tableValues["bait_description"] = $manifest->xpath('//BaitDescription/text()')[0];
      $tableValues["camera_id"] = $manifest->xpath('//CameraID/text()')[0];
      $tableValues["proposed_lat"] = $manifest->xpath('//ProposedLatitude/text()')[0];
      $tableValues["proposed_lon"] = $manifest->xpath('//ProposedLongitude/text()')[0];
      $tableValues["actual_lat"] = $manifest->xpath('//ActualLatitude/text()')[0];
      $tableValues["actual_lon"] = $manifest->xpath('//ActualLongitude/text()')[0];

      $tableValues["camera_make"] = "can't find this";
      $tableValues["camera_model"] = "cannot find this either";

      $tableValues["camera_failure_details"] = $manifest->xpath('//CameraFailureDetails/text()')[0];
      $tableValues["detection_distance"] = $manifest->xpath('//DetectionDistance/text()')[0];
      $tableValues["sensitivity_setting"] = $manifest->xpath('//SensitivitySetting/text()')[0];
      $tableValues["quiet_period_setting"] = $manifest->xpath('//QuietPeriodSetting/text()')[0];
      $tableValues["image_resolution_setting"] = $manifest->xpath('//ImageResolutionSetting/text()')[0];
      $tableValues["deployment_notes"] = (string) $manifest->xpath('//CameraDeploymentNotes')[0];

      $results = table_insert("deployments", $tableValues);

      getObservations($PID, $manifest);
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function getParentSubproject($PID)
  {
    $results = Array(
      "subproject" => "",
      "plot" => "",
      "error" => FALSE
    );

    try
    {
      $url = "objects/$PID/datastreams/RELS-EXT/content";

      $rels = sianctapiGetDataFromFedora($url);

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
        error_log("ERROR: UNABLE TO RETRIEVE RELS-EXT FOR OBJECT: $PID\n", 3, parse_ini_file("sianct.ini")["log"]);
      }
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
      $results["error"] = TRUE;
    }
    finally
    {
      return $results;
    }
  }

  function getObservations($PID, $manifest)
  {
    try
    {
      $sequences = $manifest->xpath("//ImageSequence/ImageSequenceId");

      $count = count($sequences);

      foreach($sequences as $seq)
      {
        $xpath = "//ImageSequence[ImageSequenceId='$seq']";
        $volXPATH = "$xpath/VolunteerIdentifications/Identification";
        $resXPATH = "$xpath/VolunteerIdentifications/Identification";

        $volCount = count($manifest->xpath("$volXPATH"));
        $resCount = count($manifest->xpath("$resXPATH"));

        for($i = 1; $i < count($volCount) + 1; $i++)
        {
          parseObservation($PID, $manifest, $seq, $i, $xpath, "Volunteer");
        }

        for($i = 1; $i < count($resCount) + 1; $i++)
        {
          parseObservation($PID, $manifest, $seq, $i, $xpath, "Researcher");
        }
      }
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function parseObservation($PID, $manifest, $seq, $index, $xpath, $id_type)
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

      registerSpecies($speciesValues);

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

      $results = table_insert("observations", $observationValues);
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function registerSpecies($values)
  {
    $results = table_insert("species", $values);
  }

  function sianctapiGetDataFromFedora($params)
  {
    $config = parse_ini_file("sianct.ini");

    try
    {
      $fedoraURL = "http://" . $config['fedorahost'] . ":" . $config['fedoraport'] . "/fedora/$params?format=xml";

      $fedoraUserPass = $config['fedorauserpass'];

      $curlOptions = array(
        CURLOPT_USERPWD => $fedoraUserPass,
      );

      $fedoraResults = curlWithRetries($fedoraURL, $curlOptions);

      if($fedoraResults['code']=='200')
      {
        return $fedoraResults['results'];
      }
      else
      {
        error_log($fedoraResults['log'] . "\n", 3, $config['log']);
        return FALSE;
      }
    }
    catch(Exception $e)
    {
      error_log("$e\n", 3, $config['log']);
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
  function curlWithRetries($url, $curlOpts = array())
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
