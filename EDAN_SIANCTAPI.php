<?php

require_once('./lib/edan-module/includes/EDANInterface.class.php');

/**
 * EDAN interface API for Camera Trap images
 */
class EDAN_SIANCTAPI {
  private $app_id;
  private $config;
  private $types = array(
    'image',
    'sequence',
  );

  /**
   * Reference the SIANCTAPI constructor
   */
  public function __construct($config = array(), $app_id = '') {
    $this->config = array();

    if (is_array($config)) {
      $this->config = $config;
    }

    // Default the config
    $this->config += array(
      'server_url' => '',
      'app_id' => '',
      'auth_key' => '',
      'tier_type' => '',
    );

    // Set app_id
    $this->app_id = $app_id;

    return;
  }

  /**
   *
   */
  public function getImageSequence($type, $id, $return) {
    if (!isset($type) || !isset($id)) {
      $this->_invalidRequest();
    }

    if (!in_array($type, $this->types)) {
      $this->_invalidRequest();
    }

    if (!$return) {
      $id = filter_input(INPUT_GET, 'id');
    }

    $edan = new EDAN\Connection($this->config['server_url'], $this->config['tier_type'], $this->config['app_id'], $this->config['auth_key']);
    $service = 'metadata/v1.1/metadata/search.htm';
    $fqs = array(
      'type:emammal_image',
      'status:0',
    );

    switch ($type) {
      case 'image':
        $fqs[] = 'p.emammal_image.image.id:' . $id;
        break;

      case 'sequence':
        $fqs[] = 'p.emammal_image.image_sequence_id:' . $id;
        break;

      default:
        $this->_invalidRequest();
        break;
    }

    $params = array(
      'fqs' => json_encode($fqs),
      'rows' => 100,
    );

    $edan->callEDAN($service, $params, FALSE);

    switch ($type) {
      case 'image':
        $image = $edan->getResultsJSON();

        if (!isset($image['rows'])) {
          $this->_invalidRequest();
        }

        if (!isset($image['rows'][0])) {
          break;
        }

        $edan = $this->getImageSequence('sequence', $image['rows'][0]['content']['image_sequence_id'], TRUE);
        break;

      case 'sequence':
        break;
    }

    if ($return) {
      return $edan;
    }

    print $edan->getResultsRaw();
  }

  /**
   * Reject nonconforming requests
   */
  function _invalidRequest() {
    http_response_codeREAL(404);
    exit();
  }
}
