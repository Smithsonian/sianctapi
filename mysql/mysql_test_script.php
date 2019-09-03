<?php
  require("sianct_mysql_populator.php");
  require 'vendor/autoload.php';

  findDeployments();

  /**
   * Recursive method for retrieving fedora object pids
   * @param  string  $PID           object pid
   * @param  boolean $parentproject true if object parent is of type project
   * @param  string  $parent        pid of object parent
   */
  function findDeployments()
  {
      $migrator = new sianct_mysql_populator();

      $pids = Array(
        'si:121909'
      );

      $fedora_counts = $mysql_counts = Array(
        'projects' => 0,
        'subprojects' => 0,
        'plots' => 0,
        'deployments' => 0,
        'observations' => 0
      );

      $parent = NULL;
      $parentProject = FALSE;

      for($i = 0; $i < count($pids); $i++)
      {
        $rels = $migrator->getRelsExtData($pids[$i]);

        if($pids[$i] != 'si:121909')
        {
          if($rels['type'] == 'si:projectCModel')
          {
            $parent = $migrator->getRelsExtData($rels['parent'])['type'];

            if($parent == 'si:projectCModel' && $rels['parent'] != 'si:121909')
            {
              $fedora_counts['subprojects'] += 1;
            }
            else
            {
              $fedora_counts['projects'] += 1;
            }
          }
          elseif($rels['type'] == "si:ctPlotCModel")
          {
            $fedora_counts['plots'] += 1;
          }
          elseif($rels['type'] == "si:cameraTrapCModel")
          {
            $fedora_counts['deployments'] += 1;
            $fedora_counts['observations'] += $migrator->getObservationsCount($pids[$i]);
          }
        }

        $pids = array_merge($pids, $rels['children']);
      }

      $migrator->deleteDatabase();
      $migrator->initializeSianctDatabase();

      $start = microtime(true);
      $migrator->findDeployments('si:121909');
      $time_elapsed_secs = microtime(true) - $start;

      echo "\nTotal elapsed time to populate the database: $time_elapsed_secs seconds\n\n";

      $mysql_counts = Array(
        'projects' => $migrator->getTableLength("projects"),
        'subprojects' => $migrator->getTableLength("subprojects"),
        'plots' => $migrator->getTableLength("plots"),
        'deployments' => $migrator->getTableLength("deployments"),
        'observations' => $migrator->getTableLength("observations")
      );


      echo "# of Projects in Fedora: " . $fedora_counts['projects'] . "\n# of Projects in MySQL: " . $mysql_counts['projects'] . "\nSuccessful Migration: " . (($fedora_counts['projects'] == $mysql_counts['projects']) ? "TRUE" : 'FALSE') . "\n\n";
      echo "# of Subrojects in Fedora: " . $fedora_counts['subprojects'] . "\n# of Subprojects in MySQL: " . $mysql_counts['subprojects'] . "\nSuccessful Migration: " . (($fedora_counts['subprojects'] == $mysql_counts['subprojects']) ? "TRUE" : 'FALSE') . "\n\n";
      echo "# of Plots in Fedora: " . $fedora_counts['plots'] . "\n# of Plots in MySQL: " . $mysql_counts['plots'] . "\nSuccessful Migration: " . (($fedora_counts['plots'] == $mysql_counts['plots']) ? "TRUE" : 'FALSE') . "\n\n";
      echo "# of Deployments in Fedora: " . $fedora_counts['deployments'] . "\n# of Deployments in MySQL: " . $mysql_counts['deployments'] . "\nSuccessful Migration: " . (($fedora_counts['deployments'] == $mysql_counts['deployments']) ? "TRUE" : 'FALSE') . "\n\n";
      echo "# of Observations in Fedora: " . $fedora_counts['observations'] . "\n# of Observations in MySQL: " . $mysql_counts['observations'] . "\nSuccessful Migration: " . (($fedora_counts['observations'] == $mysql_counts['observations']) ? "TRUE" : 'FALSE') . "\n\n";

      //$migrator->dropObservations('test.smx.home:50');
  }

?>
