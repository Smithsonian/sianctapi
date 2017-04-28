<?php

namespace EDAN {

  class EDAN_JSONObject {

    public $numFound;
    public $response;
    public $isValid;

    public function __construct( $obj ) {

      $this->numFound = 0;
      $this->isValid = False;
      $jsonobj = json_decode($obj);

      $this->response = $jsonobj;


      if (isset($jsonobj->response->numFound)) {
        $this->numFound = $jsonobj->response->numFound;
      }

    }

  }


  class EDAN_metadataJSON extends EDAN_JSONObject
  {

    public $docs;

    public function __construct($obj)

    {
      parent::__construct($obj);

      if (isset($this->response->response->docs)) {
        $this->docs = $this->response->response->docs;
      }

    }

  }

  class EDAN_taggingJSON extends EDAN_JSONObject
  {

    public $tags;
    public $responseCode;

    public function __construct($obj)

    {
      parent::__construct($obj);

      if (isset($this->response->tagResults)) {

        $tagResults = $this->response->tagResults;
        $this->responseCode = $tagResults->responseCode;

        if (isset($tagResults->tags)) {
          $this->tags = $tagResults->tags;
        }
      }

    }

  }

  class EDAN_listsJSON extends EDAN_JSONObject
  {

    public $lists;
    public $items;
    public $responseCode;

    public function __construct($obj)

    {
      parent::__construct($obj);

      if (isset($this->response->listResults)) {

        $listResults = $this->response->listResults;
        $this->responseCode = $listResults->responseCode;
        $this->numFound = $listResults->numFound;
        if (isset($listResults->lists)) {
          $this->lists = $listResults->lists;
        }
        if (isset($listResults->items)) {
          $this->items = $listResults->items;
        }
      }

    }

  }

} // EDAN namespace
