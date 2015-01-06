SIANCT API
==========

Please note that this API is under heavy development and things in this repo are subject to a lot of change.

The API needs two .config files to run properly. If these are no present things may still work, but will likely return empty results. This behavior may change in the future.


api.config defines variables per library served. At this time this was created these were the following configurables per library:

{
  "SIANCTAPI": {
    "sianctapi_block_fedora": "http://127.0.0.1:8080/fedora",
    "sianctapi_block_fedora_userpass": "sianctapiuseroffedora:sianctapiuseroffedorapassword",
    "sianctapi_block_gsearch": "http://127.0.0.1:8080/fedoragsearch/rest",
    "sianctapi_block_solr": "http://127.0.0.1:8080/solr",
    "sianctapi_block_solr_max": "1000",
    "sianctapi_block_solr_xslt_tree": "sianctapiProjectStructureToHtml.xslt",
    "sianctapi_block_solr_xslt_filtered": "sianctapiObstablesToHtml.xslt",
    "sianctapi_path": ""
  },
  "SIANCT": {
    "sianct_block_solr": "noSolrUrl",
    "sianct_block_solr_max": "1000",
    "sianct_block_solr_xslt_tree": "none",
    "sianct_block_fedora": "nofedoraurl",
    "sianct_block_fedora_userpass": "nofedorauser:password",
    "sianct_block_solr_xslt_filtered": ""
  },
  "SIANCTDEMO": {
    "sianctdemo_block_sianct": "noSianisUrl",
    "sianctdemo_block_sianct_userpass": "noSianisdemoUserOfSianis:Pass",
    "sianctdemo_path": ""
  },
  "GFLOW": {
    "gflow_block_workflow_server": "",
    "gflow_block_workflow_userpass": "",
    "gflow_block_workflow_mimetype": "",
    "gflow_block_fedora": "",
    "gflow_path": ""
  }
}

You only need SIANCTAPI (as of 2014-07) in your api.config.

Secondly you'll need keys.config:

{
  "APP_ID": "A Random Key"
}

This is a key/value pair where the key is the unique API assigned to each user and the value is a randomly generated shared secret used to sign and validate requests.

So in short you need two file: api.config and keys.config.