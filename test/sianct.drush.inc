<?php

/**
 *
 * A collection of Drush commands to demonstrate the sianct API.
 *
 */

/**
 * Implements hook_drush_command.
 */
function sianct_drush_command() {
  return array(
    'sianct-get-all-species-names-cached' => array(
      'callback' => 'drush_get_all_species_names_cached',
      'arguments' => array(
      ),
      'description' => 'Returns all the cached species names',
      'examples' => array(
        'drush sianct-get-all-species-names-cached' => 'Returns all the cached species names',
      ),
      'aliases' => array('cached-species'),
      //'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-species' => array(
      'callback' => 'drush_get_species',
      'arguments' => array(
      ),
      'description' => 'Returns all the species',
      'examples' => array(
        'drush sianct-get-species' => 'Returns all the species names',
      ),
      'aliases' => array('species'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-all-obstable-pids' => array(
      'callback' => 'drush_get_all_obstable_pids',
      'arguments' => array(
      ),
      'description' => 'Returns all the observation table PIDs',
      'examples' => array(
        'drush sianct-get-all-obstable-pids' => 'Returns all the observation table PIDs',
      ),
      'aliases' => array('obstable-pids'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-project-structure' => array(
      'callback' => 'drush_get_project_structure',
      'arguments' => array(
      ),
      'description' => 'Returns the project structure',
      'examples' => array(
         'drush sianct-get-project-structure' => 'Returns the project structure',
      ),
      'aliases' => array('project-structure'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-project-structure-cached' => array(
      'callback' => 'drush_get_project_structure_cached',
      'arguments' => array(
      ),
      'description' => 'Returns the cached project structure',
      'examples' => array(
         'drush sianct-get-project-structure-cached' => 'Returns the cached project structure',
      ),
      'aliases' => array('cached-projects'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-project-structure-metadata' => array(
      'callback' => 'drush_get_project_structure_metadata',
      'arguments' => array(
      ),
      'description' => 'Returns the project structure metadata',
      'examples' => array(
         'drush sianct-get-project-structure-metadata' => 'Returns the project structure metadata',
      ),
      'aliases' => array('project-metadata'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-selected-observations' => array(
      'callback' => 'drush_get_selected_observations',
      'arguments' => array(
      ),
      'description' => 'Returns the selected observations',
      'examples' => array(
         'drush sianct-get-selected-observation' => 'Returns the selected observations',
      ),
      'aliases' => array('selected-observations'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-run-workflow' => array(
      'callback' => 'drush_run_workflow',
      'arguments' => array(
      ),
      'description' => 'Runs the selected workflow',
      'examples' => array(
         'drush sianct-run-workflow' => 'Runs the selected workflow',
      ),
      'aliases' => array('run-workflow'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-select-obstables' => array(
      'callback' => 'drush_select_obstables',
      'arguments' => array(
      ),
      'description' => 'Runs a Solr query',
      'examples' => array(
         'drush sianct-select-obstables' => 'Returns the result of a Solr query',
      ),
      'aliases' => array('select-obstables'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    ),
    'sianct-get-file' => array(
      'callback' => 'drush_get_file',
      'arguments' => array(
      ),
      'description' => 'Returns a file from the server',
      'examples' => array(
         'drush sianct-get-file' => 'Returns a file from the server',
      ),
      'aliases' => array('get-file'),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN, // We can pass in users id on the command line using drush -u.
    )
  );
}


/**
 * Returns all the species found within the observation resources denoted by a set of PIDs.
 *
 * Note: The PIDs below are for resource objects containing observation tables. You need to put in valid PIDs.
 */
function drush_get_species() {

    drush_print('Getting species');
    $response = _sianct_api_send_request('sianctapi/getSpecies', 'pids=si:144335,si:144336', TRUE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns all the cached species names.
 */
function drush_get_all_species_names_cached() {

    drush_print('Getting cached species');
    $response = _sianct_api_send_request('sianctapi/getAllSpeciesNamesCached', '', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the project structure.
 */
function drush_get_all_obstable_pids() {

    drush_print('Getting getting all observation table PIDs');
    $response = _sianct_api_send_request('sianctapi/getAllObstablePids', '', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the project structure.
 */
function drush_get_project_structure() {

    drush_print('Getting project structure');
    $response = _sianct_api_send_request('sianctapi/getProjectStructure', 'xslt=none', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the cached project structure.
 */
function drush_get_project_structure_cached() {

    drush_print('Getting cached project structure');
    $response = _sianct_api_send_request('sianctapi/getProjectStructureCached', '', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the project structure metadata.
 */
function drush_get_project_structure_metadata() {

    drush_print('Getting project structure metadata');
    $response = _sianct_api_send_request('sianctapi/getProjectStructureMetadata', 'params=q=PID:si*&sort=projectPID+asc', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the selected observations.
 */
function drush_get_selected_observations() {

    drush_print('Getting the selected observations');
    $response = _sianct_api_send_request('sianctapi/getSelectedObservations', 'pids=si:144335,si:144336&species=', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Runs the selected workflow.
 */
function drush_run_workflow() {

    drush_print('Run the selected workflow');
    $uri = 'workflowName=selected_observations.csv&pids=si%3A144335%2C+si%3A144336%2C+si%3A348526%2C+si%3A348527%2C+si%3A353268%2C+si%3A353269%2C+si%3A396178%2C+si%3A396180%2C+si%3A401862%2C+si%3A401863%2C+si%3A403384%2C+si%3A403386%2C+si%3A405342%2C+si%3A405345%2C+si%3A407018%2C+si%3A407019%2C+si%3A410897%2C+si%3A410899%2C+si%3A431767%2C+si%3A431768%2C+si%3A435856%2C+si%3A435857%2C+si%3A680500%2C+si%3A680501%2C+si%3A348069%2C+si%3A348070%2C+si%3A413327%2C+si%3A413328%2C+si%3A433390%2C+si%3A433391%2C+si%3A492565%2C+si%3A492568%2C+si%3A516168%2C+si%3A516169%2C+si%3A618648%2C+si%3A618649%2C+si%3A688557%2C+si%3A688559%2C+si%3A348493%2C+si%3A348494%2C+si%3A351281%2C+si%3A351282%2C+si%3A394120%2C+si%3A394124%2C+si%3A400005%2C+si%3A400008%2C+si%3A404507%2C+si%3A404508%2C+si%3A408652%2C+si%3A408654%2C+si%3A430724%2C+si%3A430725%2C+si%3A434603%2C+si%3A434604%2C+si%3A517871%2C+si%3A517873%2C+si%3A524829%2C+si%3A524830%2C+si%3A658598%2C+si%3A658599%2C+si%3A736387%2C+si%3A736388%2C+si%3A741272%2C+si%3A741274%2C+si%3A412247%2C+si%3A412250%2C+si%3A305637%2C+si%3A305641%2C+si%3A315314%2C+si%3A315317%2C+si%3A311424%2C+si%3A311430%2C+si%3A325893%2C+si%3A325894%2C+si%3A328197%2C+si%3A328200%2C+si%3A743783%2C+si%3A743784%2C+si%3A863476%2C+si%3A863477%2C+si%3A326176%2C+si%3A326177&species=+&resultFileExt=csv';
    $response = _sianct_api_send_request('sianctapi/runWorkflow', $uri, TRUE, array('noexit' => TRUE));
    drush_print(dt($response));

}


/**
 * Returns the selected obstables.
 */
function drush_select_obstables() {

    drush_print('Getting the selected obstables');
    // These queries all work; they are just commented out so you can see on result at a time.
    $response = _sianct_api_send_request('sianctapi/selectObstables', 'query=cameraLongitude%3A%5B-80.0526+TO+-75.3504%5D+AND+cameraLatitude%3A%5B39.41922+TO+41.55792%5D&xslt=none', FALSE, array('noexit' => TRUE));
    drush_print(dt($response));
    //$response = _sianct_api_send_request('sianctapi/selectObstables', 'query=speciesTaxonrv%3A%22Mephitis%20mephitis%22&xslt=none', FALSE, array('noexit' => TRUE));
    //drush_print(dt($response));
    //$response = _sianct_api_send_request('sianctapi/selectObstables', 'query=datasetLabel%3A(researcher_observation.csv)&xslt=none', FALSE, array('noexit' => TRUE));
    //drush_print(dt($response));
    //$response = _sianct_api_send_request('sianctapi/selectObstables', 'query=cameraDeploymentBeginDate%3A%5B20120901%20TO%2020120930%5D%20AND%20cameraDeploymentEndDate%3A%5B20121001%20TO%2020121031%5D&xslt=none', FALSE, array('noexit' => TRUE));
    //drush_print(dt($response));

}


/**
 * Returns the file from the server.
 */
function drush_get_file() {

    drush_print('Getting the file');
    $response = _sianct_api_send_request('sianctapi/getFile', 'filepath=/var/www/html/api/runtime/sianctapi-selected-observations-54b05aeec15cc.csv', array('noexit' => TRUE));
    drush_print(dt($response));

}

?>
