<?php

use EDAN\Connection as EdanConnection;

require_once './lib/edan-module/includes/EDANInterface.class.php';

/**
 * EDAN interface API for Camera Trap images.
 */
class EDAN_SIANCTAPI {
  private $app_id;
  private $config;
  private $types = array(
    'image',
    'sequence',
  );

  /**
   * Reference the SIANCTAPI constructor.
   */
  public function __construct($config = array(), $app_id = '') {
    $this->config = array();

    if (is_array($config)) {
      $this->config = $config;
    }

    // Default the EDAN connection config.
    $this->config += array(
      'server_url' => '',
      'app_id' => '',
      'auth_key' => '',
      'tier_type' => '',
    );

    $this->app_id = $app_id;
  }

  /**
   * Filter a list of image sequences.
   *
   * @param string $sequences
   *   A comma-separated list of image sequence IDs.
   *
   * @return string
   *   An JSON-encoded array of image sequence IDs available in EDAN.
   */
  public function getValidImageSequences($sequences) {
    $valid_sequences = array();

    if (empty($sequences)) {
      $this->invalidRequest();
    }

    // Create and sanitize an array of image sequences.
    $sequences = explode(',', $sequences);
    array_walk($sequences, function (&$value) {
      $value = addslashes(trim($value));
    });
    $sequences = array_values(array_filter($sequences));
    if (empty($sequences)) {
      $this->invalidRequest();
    }

    // Search EDAN for the first image in the processed image sequences.
    // Iterate through image sequences in groups of 100, which is the max rows
    // returned by EDAN.
    $edan = new EdanConnection($this->config['server_url'], $this->config['tier_type'], $this->config['app_id'], $this->config['auth_key']);
    $service = 'metadata/v1.1/metadata/search.htm';
    $rows = 100;
    for ($offset = 0; $offset <= count($sequences); $offset = $offset + $rows) {
      $sequence_slices = array_slice($sequences, $offset, $rows);
      array_walk($sequence_slices, function (&$value) {
        $value = $value . 'i1';
      });

      // Configure Solr parameters.
      $sequence_slice = '"' . implode('" OR "', $sequence_slices) . '"';
      $params = array(
        'rows' => $rows,
        'q' => 'p.emammal_image.image.id:(' . $sequence_slice . ')',
        'fqs' => json_encode(array(
          'type:emammal_image',
        )),
      );

      // Query EDAN and return matching image sequence IDs.
      $edan->callEDAN($service, $params, TRUE);
      $results = $edan->getResultsJSON();
      if (!empty($results['rows'])) {
        $valid_sequences += array_map(function ($value) {
          return $value['content'];
        }, $results['rows']);
      }
    }

    print json_encode($valid_sequences);
  }

  /**
   * Get an fully-loaded image sequence.
   *
   * @param string $type
   *   The request type, either "image" or "sequence".
   *
   * @param string $id
   *   The id of the requested type.
   *
   * @param bool $return
   *   Internal use only for recursion during "image" type requests.
   */
  public function getImageSequence($type, $id, $return) {
    if (!isset($type) || !isset($id)) {
      $this->invalidRequest();
    }

    if (!in_array($type, $this->types)) {
      $this->invalidRequest();
    }

    if (!$return) {
      $id = filter_input(INPUT_GET, 'id');
    }

    $edan = new EdanConnection($this->config['server_url'], $this->config['tier_type'], $this->config['app_id'], $this->config['auth_key']);
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
        $this->invalidRequest();
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
          $this->invalidRequest();
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
   * Reject nonconforming requests.
   */
  private function invalidRequest() {
    http_response_codeREAL(404);
    exit();
  }

}
