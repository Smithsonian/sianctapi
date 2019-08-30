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

      $projects = Array();
      $subprojects = Array();
      $plots = Array();
      $deployments = Array();

      $parent = NULL;
      $parentProject = FALSE;

      for($i = 0; $i < count($pids); $i++)
      {
        $rels = $migrator->getRelsExtData($pids[$i]);

        if($rels['type'] == 'si:projectCModel' && $pids[$i] != 'si:121909')
        {
          if($migrator->getRelsExtData($rels['parent']) == 'si:projectCModel' && $rels['parent'] != 'si:121909')
          {
            $subprojects[] = $pids[$i];
          }
          else
          {
            $projects[] = $pids[$i];
          }
        }
        elseif($rels['type'] == "si:ctPlotCModel")
        {
          $plots[] = $pids[$i];
        }
        elseif($rels['type'] == "si:cameraTrapCModel")
        {
          $deployments[] = $pids[$i];
        }

        //echo "count of pids before merge: " . count($pids) . "\n";
        $pids = array_merge($pids, $rels['children']);
        //echo "count of pids after merge: " . count($pids) . "\n";
      }

      echo "# of project: " . count($projects) . "\n";
      echo "# of subprojects: " . count($subprojects) . "\n";
      echo "# of plots: " . count($plots) . "\n";
      echo "# of deployments: " . count($deployments) . "\n";
  }

?>
