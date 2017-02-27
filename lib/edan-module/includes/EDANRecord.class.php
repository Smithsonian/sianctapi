<?php

namespace EDAN {

  class EDANRecord extends EDANBase {

    private $record_type;
    private $record_title;
    private $record_id;
    private $id; // the EDAN id
    private $record_json;
    private $record_data;

    public function __construct( $record_id = NULL, $edan_connection = NULL, $id = NULL) {

      $this->edan_connection = NULL;
      $this->errors = array();

      $this->record_type = NULL;
      $this->record_title = '';
      $this->record_id = '';
      $this->id = NULL;
      $this->record_json = NULL;
      $this->record_data = NULL;

      $this->results_raw = NULL;
      $this->results_json = NULL;
      $this->results_info = NULL;

      // check each incoming to make sure it is acceptable
      // @todo

      if(NULL == $this->errors || count($this->errors) == 0) {
        $this->is_valid = TRUE;
      }

      if(NULL !== $edan_connection) {
        $this->record_id = $record_id;
        $this->edan_id = $id;
        $this->edan_connection = $edan_connection;
        if(NULL !== $record_id) {
          $this->getRecord($record_id);
        }
        elseif(NULL !== $id) {
          $this->getRecordById($id);
        }
      }
    }

    //@todo - what about record types? $args? $opts?
    public function getRecord($record_id, $args = NULL, &$opts = NULL) {
      // retrieve the record from EDAN

      /*
       * Notes from Andrew G 3/24/2016
          Architecturally search is powered by the lucene index and the content endpoints will be just the repo.
          Use content whenever you need that one record.
          However since some of the content needs to be rendered we need to do some work on the content endpoints.

          Id and record_id are two different things.
          but yes… id is unique across all data types.
          record_id only applies to edanmdm records but in practice should also be unique.
        *
       */

      try {
        //$edan = new EDANInterface($this->edan_server, $this->edan_app_id, $this->edan_auth_key, $this->edan_tier_type);
        $this->record_id = $record_id;

        $endpoint = 'metadata/v1.1/metadata/search.htm';
        //$params = array('q' => 'record_ID:"' . $this->record_id . '"', 'facet' => FALSE);
        $params_string = 'fqs=["record_ID:' . $this->id . '"]&facet=false';

        //@todo - may have $args and $opts
        // see code:
        // edan_record.module - edan_record_menupage()
        // edan_search.module - _edan_search_execute_search()
        $info = '';
        $results = $this->edan_connection->sendRequest($params_string, $endpoint, $is_post = FALSE, $info);

        if ((!is_array($this->results_info) && strlen(trim($this->results_info)) == 0)
          || !array_key_exists('http_code', $this->results_info)) {
          //$this->errors[] = "EDAN did not return a HTTP code.";
          //return FALSE;
        }
        elseif ($this->results_info['http_code'] != 200) {
          $this->errors[] = "EDAN returned an unsuccessful HTTP code: " . $this->results_info['http_code'];
          return FALSE;
        }

        if (strlen($results) > 0) {
          try {
            $return = json_decode($results, TRUE);
            // v1.0 items, v1.1 rows
            $return_rows = isset($return['rows']) ? $return['rows'] : $return['items'];
            if(is_array($return) && isset($return_rows)) {
              $this->results_json = $return;
              $this->record_data = $return_rows[0];
              $this->id = isset($return['id']) ? $return['id'] : NULL;
              $this->getRecordId();
            }
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

      }
      catch (Exception $ex) {
        $this->errors[] = $ex->getMessage();
        return FALSE;
      }

    }

    public function getRecordById($id, $args = NULL, &$opts = NULL) {
      // retrieve the record from EDAN

      /*
       * Notes from Andrew G 3/24/2016
          Architecturally search is powered by the lucene index and the content endpoints will be just the repo.
          Use content whenever you need that one record.
          However since some of the content needs to be rendered we need to do some work on the content endpoints.

          Id and record_id are two different things.
          but yes… id is unique across all data types.
          record_id only applies to edanmdm records but in practice should also be unique.
        *
       */

      try {
        //$edan = new EDANInterface($this->edan_server, $this->edan_app_id, $this->edan_auth_key, $this->edan_tier_type);
        $this->id = $id;

        $endpoint = 'metadata/v1.1/metadata/search.htm';
        //$params = array('q' => 'id:"' . $this->id . '"', 'facet' => FALSE);
        $params_string = 'fqs=["id:' . $this->id . '"]&facet=false';

        //@todo - may have $args and $opts
        // see code:
        // edan_record.module - edan_record_menupage()
        // edan_search.module - _edan_search_execute_search()
        $info = '';
        $results = $this->edan_connection->sendRequest($params_string, $endpoint, $is_post = FALSE, $info);

        if ((!is_array($this->results_info) && strlen(trim($this->results_info)) == 0)
          || !array_key_exists('http_code', $this->results_info)) {
          // ignore
          //$this->errors[] = "EDAN did not return a HTTP code.";
          //return FALSE;
        }
        elseif ($this->results_info['http_code'] != 200) {
          $this->errors[] = "EDAN returned an unsuccessful HTTP code: " . $this->results_info['http_code'];
          return FALSE;
        }

        if (strlen($results) > 0) {
          try {
            $return = json_decode($results, TRUE);
            // v1.0 items, v1.1 rows
            $return_rows = isset($return['rows']) ? $return['rows'] : $return['items'];
            if(is_array($return) && isset($return_rows)) {
              $this->results_json = $return;
              $this->record_data = $return_rows[0];
              $this->id = isset($return['id']) ? $return['id'] : NULL;
              $this->getRecordId();
            }
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

      }
      catch (Exception $ex) {
        $this->errors[] = $ex->getMessage();
        return FALSE;
      }

    }

    /*
     * In some cases we will have already hit the API, and will have the JSON record data.
     * Instead of making another call to the API, use the JSON to load our EDANRecord properties.
     */
    public function loadRecordJSON($json) {
      try {
        if(NULL == $json || (is_array($json) && count($json) < 1) || (!is_array($json) && strlen($json) < 1)) {
          return FALSE;
        }

        if(is_array($json)) {
          $this->record_data = $json;
        }
        else {
          $this->record_data = json_decode($json);
        }

        if(isset($json['content']['record_ID'])) {
          $this->record_id = $json['content']['record_ID'];
        }
        elseif(isset($json['content']['descriptiveNonRepeating']['record_ID'])) {
          $this->record_id = $json['content']['descriptiveNonRepeating']['record_ID'];
        }
        if(isset($json['id'])) {
          $this->id = $json['id'];
        }


        if(NULL == $this->record_data || FALSE == $this->record_data) {
          return FALSE;
        }
      }
      catch (Exception $ex) {
        return FALSE;
      }

      $this->record_json = $json;
      //dpm($json);
      //@todo set record id

      return TRUE;
    }

    public function getRecordData() {
      return $this->record_data;
    }


    /**
     * Get Title
     *
     * Get the title of the record.
     *
     * @return  string  The title string
     *
     * Author Goran Halusa
     * was _get_title()
     */
    public function getTitle() {

      if(NULL == $this->record_data) {
        return '';
      }

      // Process through all record types.
      $type = isset($this->record_data['type']) ? $this->record_data['type'] : '';
      $this->record_data['type'] = $type;
      switch ($type) {
        case 'damsmdm':
          // damsmdm records can be a challenge.
          if(isset($this->record_data['content']['title']) && is_array($this->record_data['content']['title'])) {
            $title = implode(', ', $this->record_data['content']['title']);
          } else {
            $title = isset($this->record_data['content']['title']) ? $this->record_data['content']['title'] : '';
            $title = (empty($title) && isset($this->record_data['content']['collection_title'])) ? $this->record_data['content']['collection_title'] : $title;
            $title = (empty($title) && isset($this->record_data['content']['title'])) ? $this->record_data['content']['title'] : $title;
            $title = (empty($title) && isset($this->record_data['content']['IPTC_headline'])) ? $this->record_data['content']['IPTC_headline'] : $title;
            $title = (empty($title) && isset($this->record_data['content']['keywords'])) ? preg_replace('/(\.)([[:alpha:]]{2,})/', '$1 $2', $this->record_data['content']['keywords']) : $title;
            $title = empty($title) ? 'Untitled' : $title;
          }
          break;
        case 'edanmdm':
        case 'edanauth':
          //dpm($this->record_data);
          if(isset($this->record_data['content']['descriptiveNonRepeating'])) {
          $title = $this->record_data['content']['descriptiveNonRepeating']['title']['content'];
          }
          break;
        case 'concept':
        case 'objectgroup':
          // @hpham01102017: test to see if variable exist
          $title = isset($this->record_data['content']['title']) ? $this->record_data['content']['title'] : (isset($this->record_data['title']) ? $this->record_data['title'] : '');
          break;
        case 'edanead':
          $title = ucwords($this->record_data['content']['coll_unittitle']);
          break;
        case 'transasset':
        case 'transproject':
          $title = $this->record_data['content']['projectName'];
          break;
        case 'museum':
          $title = $this->record_data['title'];
          break;
        case '3d_model':
        case '3d_tour':
        case 'location':
        case 'si-unit':
          //@todo temp
          if (isset($this->record_data['content']['title']['content'])) {
            $title = $this->record_data['content']['title']['content'];
          }
          elseif (isset($this->record_data['content']['title'])) {
            $title = $this->record_data['content']['title'];
          }
          break;
        case 'ecr':
        case 'event':
          $title = $this->record_data['content']['title']['content'];
          break;
        default:
          $title = '';
          if(is_array($this->record_data)) {
            if(array_key_exists('content', $this->record_data)) {
              if(array_key_exists('descriptiveNonRepeating', $this->record_data['content'])) {
                $title = isset($this->record_data['content']['descriptiveNonRepeating']['title']['content'])
                  ? $this->record_data['content']['descriptiveNonRepeating']['title']['content']
                  : 'Untitled (not processed)';
              }
              elseif(array_key_exists('title', $this->record_data['content'])) {
                $title = isset($this->record_data['content']['title']['content'])
                  ? $this->record_data['content']['title']['content']
                  : 'Untitled (not processed)';
              }
            }
          }
          $title = '' == $title ? 'Untitled (not processed)' : $title;
      }

      // Remove brackets from titles.
      $title = isset($title) ? str_replace(array('[',']'), '', $title) : 'Untitled (not processed)';
      $this->record_title = $title;

      return $title;
    }

    //hpham: pass si.edu urls through IDS and colorbox integration
    /**
     * Get EDANMDM Images
     *
     * Get an edanmdm record's images.
     *
     * @return  array  The array of image data
     *
     * Author Goran Halusa
     * was app_util_get_edanmdm_images()
     */
    public function getImages() {

      if(NULL == $this->record_data) {
        return '';
      }

      $edan_image = variable_get('edan_image', array());
      $data = $data['all_image_ids'] = $query = array();
      $ids_url = isset($edan_image['ids']) ? $edan_image['ids'] : '';
      $ids_dynamic = isset($edan_image['dynamic']) ? $edan_image['dynamic'] : '';
      $thumbnail = isset($edan_image['thumbnail']) ? $edan_image['thumbnail'] : 200;
      $medium = isset($edan_image['medium']) ? $edan_image['medium'] : 600;
      $large = isset($edan_image['large']) ? $edan_image['large'] : 980;
      $colorbox = module_exists('colorbox');

      // get the array for this particular EDAN content type
      $image_array = array();
      $type = isset($this->record_data['type']) ? $this->record_data['type'] : '';
      switch ($type) {
        case 'damsmdm':

          break;
        case 'edanmdm':
        case 'edanauth':
        $image_array = isset($this->record_data['content']['descriptiveNonRepeating']['online_media']['media'])
        ? $this->record_data['content']['descriptiveNonRepeating']['online_media']['media'] : array();
          break;
        //case 'concept': ??
        case 'objectgroup':
          if(isset($this->record_data['content']['feature']['thumbnail'])) {
            $image_array[] = array('content' => $this->record_data['content']['feature']['thumbnail'], 'type' => 'Images');
          }
          if(isset($this->record_data['content']['feature']['url'])) {
            $image_array[] = array('content' => $this->record_data['content']['feature']['url'], 'type' => 'Images');
          }
          if(isset($this->record_data['content']['feature']['media'])) {
            $image_array[] = array('content' => $this->record_data['content']['feature']['media'], 'type' => 'Images');
          }
          break;
        /*case 'edanead': ??
          $title = ucwords($this->record_data['content']['coll_unittitle']);
          break;
        case 'transasset':
        case 'transproject':
          $title = $this->record_data['content']['projectName'];
          break;
        */
        case 'museum':
          if(isset($this->record_data['content']['image'])) {
            $image_array[] = array(
              'content' => $this->record_data['content']['image'],
              'title' => $this->getTitle(),
            );
          }
          break;
        case '3d_model':
        case '3d_tour':
        case 'ecr':
        case 'event':
        case 'location':
        case 'si-unit':
          //@todo temp
          $image_array = isset($this->record_data['content']['online_media']) ? $this->record_data['content']['online_media'] : array();
          break;
        case 'ecr':
        case 'event':
          $image_array = isset($this->record_data['content']['online_media']) ? $this->record_data['content']['online_media'] : array();
          break;
      }

      if( count($image_array) > 0 ) {
        //foreach($this->record_data['content']['descriptiveNonRepeating']['online_media']['media'] as $key => $media) {
        foreach($image_array as $key => $media) {

          if(!is_array($media)) {
            $media = array();
          }
          //$media['record_title'] = htmlspecialchars($this->record_data['content']['descriptiveNonRepeating']['title']['content']);
          $media['record_title'] = htmlspecialchars($this->getTitle());
          $media['record_date'] = !empty($this->record_data['content']['freetext']['date']) ? $this->record_data['content']['freetext']['date']['0']['content'] : '';

          if(!isset($media['type'])) {
            $media['type'] = 'Images'; // default
          }

          $media['description'] = isset($media['extended']['description']) ? $media['extended']['description'] : '';

          // If there is no idsId, go for the thumbnail.
          // Stitch it all together.
          switch ($media['type']) {
            case 'Images':
              $link = isset($media['content']) ? drupal_parse_url($media['content']) : array();
              $query = isset($link['query']) ? $link['query'] : '';

              $parsed = isset($link['path']) ? parse_url($link['path']) : array();
              $matches = array();

              if(isset($parsed['host'])) {
                preg_match('/si\.edu$/', $parsed['host'], $matches);
              }

              //only process image if url is good
              //@todo- what to do about colorbox? we don't know SAMEORIGIN if we don't cURL
              //default to FALSE for now
                    $colorbox = FALSE;
                $options = array(
                  'attributes' => array(
                    'class' => array('colorbox-load'),
                  ),
                  'query' => array(
                    'iframe' => 'true',
                    'width' => '85%',
                    'height' => '85%'
                  ),
                  'absolute' => TRUE,
                );
                $idsID = isset($media['idsId']) ? $media['idsId'] : (!empty($matches) ? $media['content'] : '');
                if (!empty($idsID)) {
                  // unset the id key if it's part of the query array
                  if(isset($query['id'])) {
                    unset($query['id']);
                  }

                  $constrain = isset($edan_image['constrain']) ? $edan_image['constrain'] : '';
                  $query[$constrain] = $medium;
                  $media['medium'] = url($ids_url, array('query' => $query)) . '&id=' . $idsID;
                  $query[$constrain]  = $thumbnail;
                  $media['thumbnail'] = url($ids_url, array('query' => $query)) . '&id=' . $idsID;
                  $query['constrain'] = $options['query']['constrain'] = $large;
                  $media['large'] = url($ids_dynamic, $query) . '&container.fullpage' . '&id=' . $idsID;
                  $options['query']['id'] = isset($media['idsId']) ? $media['idsId'] : '';
                  if ($colorbox) {
                    $media['colorbox'] = url($ids_dynamic, $options) . '&container.fullpage' . '&id=' . $idsID;
                  }

                 $data['all_image_ids'][$key] = $idsID;
                }
                else {
                if(!isset($media['content'])) { $media['content'] = ''; }
                  $media['medium'] = $media['large'] = $media['content'];
                  if ($colorbox) {
                    $media['colorbox'] = url($media['content'], $options) . '&container.fullpage';
                  }
                }
                $data['record_images'][$key] = $media;

              break;
            default:
              if(isset($media['thumbnail'])) {
                $parsed = parse_url($media['thumbnail']);
                if(isset($parsed['host'])) {
                  preg_match('/si\.edu$/', $parsed['host'], $matches);

                  //only set thumbnail if url is good
                    if (!empty($matches)) {
                      $cons = isset($edan_image['constrain']) ? $edan_image['constrain'] : 'medium';
                      $media['medium'] = strlen($ids_url) > 0 ? url($ids_url, array('query' => $query)) . '&id=' . $media['thumbnail'] : $media['thumbnail'];
                      $query[$cons] = $thumbnail;
                      $media['thumbnail'] = strlen($ids_url) > 0 ? url($ids_url, array('query' => $query)) . '&id=' . $media['thumbnail'] : $media['thumbnail'];
                      $query['constrain'] = $large;
                      $media['large'] = strlen($ids_url) > 0 ? url($ids_url, $query) . '&container.fullpage' . '&id=' . $media['thumbnail'] : $media['thumbnail'];
                      if(strlen($ids_url) == 0) {
                        $media['content'] = $media['thumbnail'];
                      }
                    }
                    else {
                      $media['medium'] = $media['large'] = $media['thumbnail'];
                    }
                  }
              }
              $data['record_images'][$key] = $media;
              break;
          }

        }
      }

      if(isset($data['all_image_ids'])) {
        //$data['all_image_ids'] = json_encode($data['all_image_ids'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        $data['all_image_ids'] = json_encode($image_array, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
      }

      return $data;
    }

    public function getRecordId() {

      if(NULL == $this->record_data) {
        return '';
      }

      if(!isset($this->record_id)) {
        $this->record_id = isset($this->record_data['content']['record_ID']) ? $this->record_data['content']['record_ID'] : '';
        if(strlen($this->record_id) == 0) {
          $this->record_id = isset($this->record_data['content']['descriptiveNonRepeating']['record_ID']) ? $this->record_data['content']['descriptiveNonRepeating']['record_ID'] : '';
        }
      }

      return $this->record_id;
    }

    public function getId() {
      if(NULL == $this->record_data) {
        return '';
      }
      return $this->id;
    }

    public function getUnitCode() {
      if(NULL == $this->record_data) {
        return '';
      }
      if(isset($this->record_data['descriptiveNonRepeating']['unit_code'])) {
        return $this->record_data['descriptiveNonRepeating']['unit_code'];
      }
      elseif(isset($this->record_data['unitCode'])) {
        return $this->record_data['unitCode'];
      }
      return NULL;
    }

    public function getDescription() {
      $desc = '';

      if(NULL == $this->record_data) {
        return '';
      }

      // Process through all record types.
      $type = isset($this->record_data['type']) ? $this->record_data['type'] : '';
      switch ($type) {
        case 'damsmdm':
          //@todo
          break;
        case 'edanmdm':
        case 'edanauth':
          //@todo
          break;
        case 'concept':
        case 'objectgroup':
          $desc = isset($this->record_data['content']['description']) ? $this->record_data['content']['description'] : '';
          break;
        case 'edanead':
          //@todo
          break;
        case 'transasset':
        case 'transproject':
          //@todo
          break;
        case 'museum':
          $desc = isset($this->record_data['content']['description']) ? $this->record_data['content']['description'] : '';
          break;
        case '3d_model':
        case '3d_tour':
        case 'location':
        case 'si-unit':
        case 'ecr':
        case 'event':
          $desc = isset($this->record_data['content']['description']) ? $this->record_data['content']['description'] : '';
          break;
        default:
          $desc = '';
      }

      $teaser_temp = drupal_html_to_text($desc);
      $teaser = text_summary($teaser_temp, NULL, 400);
      if(strlen($teaser) < strlen($teaser_temp)) {
        $teaser .= '...';
      }

      $desc_array = array('description' => $desc, 'teaser' => $teaser);
      return $desc_array;
    }

    public function getRecordType() {
      if(NULL == $this->record_data) {
        return '';
      }
      return isset($this->record_data['type']) ? $this->record_data['type'] : '';
    }

  } // EDANRecord
}
