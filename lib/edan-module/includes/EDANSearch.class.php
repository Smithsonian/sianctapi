<?php

namespace EDAN {

  class EDANSearch extends EDANBase {

    public function __construct( $edan_connection) {

      $this->edan_connection = NULL;
      $this->errors = array();

      $this->results_raw = NULL;
      $this->results_json = NULL;
      $this->results_info = NULL;
      $this->is_valid = true;

      // check $edan_connection to make sure it is acceptable
      //@todo

      if(NULL !== $edan_connection && is_object($edan_connection) &&
        (get_class($edan_connection) == 'EDANInterface' || get_class($edan_connection) == 'EDAN\EDANInterface')
        ) {
        $this->edan_connection = $edan_connection;
      }
      else {
        //@todo- error code
        $this->is_valid = false;
      }
    }

    public function executeSearch($args, &$opts = array()) {
      if(NULL == $this->edan_connection) {
        $err = array('error' => 'EDAN Connection not set. Cannot search EDAN.');
        return(json_encode($err));
      }

      $uri = "wt=json";
      $uri_facets = '';

      if (isset($args['q'])) {
        $uri .= '&q=' . urlencode(str_replace(' ', '+', $args['q']));
      }

      if (isset($args['rows'])) {
        $uri .= '&rows=' . $args['rows'];
      }

      if (isset($args['start'])) {
        $uri .= '&start=' . $args['start'];
      }

      if (isset($args['type'])) {
        $uri .= '&type=' . $args['type'];
      }

      if (isset($args['universal']) && $args['universal'] == TRUE) {
        $uri .= '&universal=true';
      }

      if (!empty($args['sort'])) {
        $uri .= '&sort=' . $args['sort'];
      }

      // array('online_visual_material:true','record_ID:saam_1997.108.62');
      $_fqs = array();
      if (isset($args['fq'])) {
        foreach ($args['fq'] as $fq) {
          if (strpos($fq, ':') !== FALSE) {
            list($name, $value) = explode(':', $fq, 2);
            #$uri .= '&fq=' . $name . ':' . urlencode($value);
            $_fqs[] = $name . ':' . $value;
          }
        }
      }

      if (!empty($_fqs)) {
        $uri .= '&fqs=' . json_encode($_fqs); // Don't encode fqs
      }

      // Lists
      if (isset($args['listid'])) {
        $uri .= 'qt=myListSearch&sl.id=' . $args['listid'];
      }

      // Facets
      if (isset($args['facet']) && $args['facet'] === TRUE) {
        $uri_facets .= '&facet=true';
        #$uri_facets .= '&facet.mincount=1';
        #$uri_facets .= '&facet.limit=25';

        if (!isset($args['facet.field']) || empty($args['facet.field'])) {
          #$uri_facets .= '&facet.field=data_source&facet.field=topic&facet.field=object_type&facet.field=date&facet.field=place&facet.field=name&facet.field=culture&facet.field=language';
        }
      }

      // queryFacet - gets all of the facet totals, regardless of the fq[].
      if (isset($args['queryFacet']) && $args['queryFacet'] === TRUE) {
        $uri_facets .= '&queryFacet=true';
      }

      // NEW:
      // http://edantest.si.edu/metadata/v1.1/metadata/objectlists.htm?applicationId=OGMT_TEST&listName=OGMT_TEST:12:128
      // http://edantest.si.edu/metadata/v1.1/metadata/objectlists.htm?applicationId=OGMT_TEST&listName=OGMT_TEST:12:133
      // Can also accept objectGroupUrl and pageUrl
      // Supports listType=0 and listType=1

      /*
      start,rows,q
      facet=bool
      fqs=json->array("topic:Monkey")
      */

      // Object Group Id
      if (isset($args['objectGroupId'])) {
        /*
        objectListingMetadata
        /ogmt/webservices/objectListingMetadata.htm?objectGroupId=12
        objectListingMetadata with metadata...
        /ogmt/webservices/objectListingMetadata.htm?objectGroupId=12&facet=true.
        */
        #$service = 'ogmt/webservices/objectListingMetadata.htm';

        //http://edantest.si.edu/metadata/v1.1/metadata/objectlists.htm?applicationId=OGMT_TEST&listName=OGMT_TEST:12:133
        $service = 'metadata/v1.1/metadata/objectlists.htm';

        $uri .= '&objectGroupId=' . $args['objectGroupId'];
        if (isset($args['pageId'])) {
          $uri .= '&pageId=' . $args['pageId'];
        }
        // RB 20161111 - AG says do not pass applicationId
        //$uri .= '&applicationId=' . $this->app_id;
        if (isset($args['facet']) && $args['facet'] === TRUE) {
          $uri .= '&facet=true';
        }
        $uri_facets = ''; // Clear
      } else {
        $service = 'metadata/v1.1/metadata/search.htm'; //metadataService';
      }

      // Add facets
      $uri .= $uri_facets;

      $results = $this->edan_connection->sendRequest($uri, $service, FALSE, $this->info);

      if ($this->info['http_code'] != 200) {
        $this->setError('Insufficient credentials to query EDAN (' . $this->info['http_code'] . ').');
        return FALSE;
      }

      return json_decode($results, TRUE);
    }

    public function setError($err) {
      $this->errors[] = $err;
    }
  } // EDANSearch

}