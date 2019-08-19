<?php
  require("sm_insert_data.php");
  //findDeployments("si:121909");
  //fedora_test();

  $PID = "si:121909";
  findDeployments($PID);

  function findDeployments($PID, $parentproject=FALSE, $parent=NULL)
  {
    $obj_url = "objects/$PID";
    $rels_ext_url = "objects/$PID/datastreams/RELS-EXT/content";
    //echo "rels_ext url: $rels_ext_url";

    $obj = sianctapiGetDataFromFedora($obj_url);
    $obj_xml = new SimpleXMLElement($obj);

    $rels = sianctapiGetDataFromFedora($rels_ext_url);
    $rels_xml = new SimpleXMLElement($rels);

    $fedora_type = str_replace("info:fedora/", "", $rels_xml->xpath('//hasModel[1]/@rdf:resource')[0]);

    $children = $rels_xml->xpath('//hasConcept/@rdf:resource');

    if($fedora_type == "cameraTrapCModel")
    {

    }
    else
    {
      if($fedora_type == "si:projectCModel")
      {
        if($parentproject)
        {
          //get subproject information
        }
        else
        {
          //get project information
        }
      }
      else
      {
        //plot code
      }

      //recursively call children
      foreach($children as $child)
      {
        $PID = str_replace("info:fedora/", "", $child);
        $hasProjectParent = ($fedora_type == "si:projectCModel");
        findDeployments($PID, $hasProjectParent);
      }
    }

    //echo "OBJECT XML: $obj\n\n\n";

    //echo "RELS-EXT XML: $rels\n";
  }

  function writeProjectTable($PID)
  {
    $tableValues = Array();
    $tableValues["sidora_project_id"] = $PID;

    $url = "objects/$PID";

    $result = sianctapiGetDataFromFedora($url);
    $objXML =  new SimpleXMLElement($obj);

    $eac_url = "objects/$PID/datastreams/EAC_CPF/content";
    $result = sianctapiGetDataFromFedora($eac_url);
    $eacXML = new SimpleXMLElement($result);

    $tableValues["ct_project_id"] = $eacXML->xpath('//recordId/text()')[0];
    $tableValues["name"] = $eacXML->xpath('//nameEntry[@localType="primary"]/part/text()')[0];
    $tableValues["country_code"] = $eacXML->xpath('string(//placeEntry/@countryCode)')[0];
    $tableValues["lat"] = $eacXML->xpath('string(//placeEntry/@latitude)')[0];
    $tableValues["lon"] = $eacXML->xpath('string(//placeEntry/@longitude)')[0];
    $tableValues["publish_date"] = $eacXML->xpath('//localControl[@localType="Publication Date"]/date/text()')[0];
    $tableValues["objectives"] = $eacXML->xpath('//functions/function[term/text()="Project Objectives"]/descriptiveNote/*')[0];
    $tableValues["data_constraints"] = $eacXML->xpath('//functions/function[term/text()="Project Data Access and Use Constraints"]/descriptiveNote/*')[0];
    $tableValues["owner"] = $eacXML->xpath('//relations/cpfRelation[descriptiveNote/p/text()="Project Owner"]/relationEntry/text()')[0];
    $tableValues["email"] = $eacXML->xpath('//relations/cpfRelation[descriptiveNote/p/text()="Project Contact"]/relationEntry/text()')[0];
    $tableValues["principal_investigator"] = $eacXML->xpath('//relations/cpfRelation[descriptiveNote/p/text()="Principal Investigator"]/relationEntry/text()')[0];

    $successfulInsert = table_insert("projects", $tableValues);
  }

  function writeSubprojectTable($PID, $parent)
  {
    $tableValues = Array();
    $tableValues["sidora_subproject_id"] = $PID;

    $url = "objects/$PID";

    $result = sianctapiGetDataFromFedora($url);
    $objXML =  new SimpleXMLElement($obj);

    $eac_url = "objects/$PID/datastreams/EAC_CPF/content";
    $result = sianctapiGetDataFromFedora($eac_url);
    $eacXML = new SimpleXMLElement($result);

    $tableValues["ct_project_id"] = $eacXML->xpath('//recordId/text()')[0];
    $tableValues["name"] = $eacXML->xpath('//nameEntry[@localType="primary"]/part/text()')[0];
    $tableValues["sidora_project_id"] = $parent;
    $tableValues["abbreviation"] = $eacXML->xpath('//nameEntry[@localType="abbreviation"]/part/text()')[0];
    $tableValues["project_design"] = $eacXML->xpath('//function[term/text()="Project Design"]/descriptiveNote/*')[0];

    $successfulInsert = table_insert("subprojects", $tableValues);
  }

  function writePlotTable($PID, $parent)
  {
    $tableValues = Array();
    $tableValues["sidora_plot_id"] = $PID;

    $url = "objects/$PID";

    $result = sianctapiGetDataFromFedora($url);
    $objXML =  new SimpleXMLElement($obj);

    $fgdc_url = "objects/$PID/datastreams/FGDC-CTPlot/content";
    $result = sianctapiGetDataFromFedora($fgdc_url);
    $fgdcXML = new SimpleXMLElement($result);

    $tableValues["name"] = $fgdcXML->xpath('//citeinfo/title/text()')[0];
    $tableValues["treatment"] = $fgdcXML->xpath('//citeinfo/treatment/text()')[0];
    $tableValues["sidora_subproject_id"] = $parent;

    $successfulInsert = table_insert("plots", $tableValues);
  }

  function writeDeploymentTable($PID, $parent)
  {
    $tableValues = Array();
    $tableValues["sidora_deployment_id"] = $PID;

    $result = sianctapiGetDataFromFedora($url);
    $objXML =  new SimpleXMLElement($obj);

    $manifest_url = "objects/$PID/datastreams/MANIFEST/content";
    $result = sianctapiGetDataFromFedora($manifest_url);
    $manifestXML = new SimpleXMLElement($result);

    $tableValues["ct_deployment_id"] = $manifestXML->xpath('//CameraDeploymentID/text()')[0];
    $tableValues["name"] = $manifestXML->xpath('//CameraSiteName/text()')[0];
    $tableValues["sidora_subproject_id"] = $parent;//modify to check for subproject id
    $tableValues["access_constraints"] = $manifestXML->xpath('//AccessConstraints/text()')[0];
    $tableValues["feature_type"] = $manifestXML->xpath('//FeatureMethodology/text()')[0];
    $tableValues["feature"] = $manifestXML->xpath('//Feature/text()')[0];
    $tableValues["bait_type"] = $manifestXML->xpath('//Bait/text()')[0];
    $tableValues["bait_description"] = $manifestXML->xpath('//BaitDescription/text()')[0];
    $tableValues["sidora_plot_id"] = $parent;//modify to check for plot id
    $tableValues["camera_id"] = $manifestXML->xpath('//CameraID/text()')[0];
    $tableValues["proposed_lat"] = $manifestXML->xpath('//ProposedLatitude/text()')[0];
    $tableValues["proposed_lon"] = $manifestXML->xpath('//ProposedLongitude/text()')[0];
    $tableValues["ActualLatitude"] = $manifestXML->xpath('//ActualLatitude/text()')[0];
    $tableValues["ActualLongitude"] = $manifestXML->xpath('//ActualLongitude/text()')[0];

    $tableValues["camer_make"] = "can't find this";
    $tableValues["camera_model"] = "cannot find this either";

    $tableValues["camera_failure_details"] = $manifestXML->xpath('//CameraFailureDetails/text()')[0];
    $tableValues["detection_distance"] = $manifestXML->xpath('//DetectionDistance/text()')[0];
    $tableValues["sensitivity_setting"] = $manifestXML->xpath('//SensitivitySetting/text()')[0];
    $tableValues["quiet_period_setting"] = $manifestXML->xpath('//QuietPeriodSetting/text()')[0];
    $tableValues["image_resolution_setting"] = $manfiestXML->xpath('//ImageResolutionSetting/text()')[0];
    $tableValues["deployment_notes"] = $manifestXML->xpath('//CameraDeploymentNotes/text()')[0];

  }

  function sianctapiGetDataFromFedora($params)
  {
    $config = parse_ini_file("sianct.ini");

    $fedoraURL = "http://" . $config['fedorahost'] . ":" . $config['fedoraport'] . "/fedora/$params?format=xml";

    $fedoraUserPass = $config['fedorauserpass'];

    //echo "userpass: $fedoraUserPass";

    $curlOptions = array(
      CURLOPT_USERPWD => $fedoraUserPass,
    );

    $fedoraResults = curlWithRetries($fedoraURL, $curlOptions);

    //echo "LOG: " . $fedoraResults['log'];
    //echo "RESULTS: " . $fedoraResults['results'];

    return $fedoraResults['results'];
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

  function fedora_test()
  {
    $config = parse_ini_file("sianct.ini");

    $fedoraURL = "http://" . $config['fedorahost'] . ":8080/fedora/objects/si:121909/datastreams/RELS-EXT?format=xml";

    $fedoraUserPass = $config['fedorauserpass'];
    echo "userpass: $fedoraUserPass\n";

    $curlOptions = array(
      CURLOPT_USERPWD => $fedoraUserPass,
    );

    echo "FEDORA URL: $fedoraURL\n";
    /*$fedoraUserPass = $config['sianctapi_block_fedora_userpass'];*/

    /*$curlOptions = array(
      CURLOPT_USERPWD => $fedoraUserPass,
    );*/
    $fedoraResults = curlWithRetries($fedoraURL, $curlOptions);
    if(is_null($fedoraResults))
    {
      echo "results are null \n";
    }

    echo "RESULTS: " . $fedoraResults['results'] . "\n";
  }
