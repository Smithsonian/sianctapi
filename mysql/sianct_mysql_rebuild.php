<?php
  require("sianct_mysql_populator.php");

  require 'vendor/autoload.php';

  $migrator = new sianct_mysql_populator();
  $migrator->deleteDatabase();
  $migrator->initializeSianctDatabase();
  $migrator->findDeployments('si:121909');
?>
