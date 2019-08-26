<?php

  //initialize_sianct_database();

  function initialize_sianct_database()
  {
    $config = parse_ini_file("sianct.ini");

    $servername = $config["servername"];
    $username = $config["username"];
    $password = $config["password"];
    $dbname = $config["dbname"];

    $conn = new mysqli($servername, $username, $password);

    // Check connection
    if ($conn->connect_error)
    {
      die("Connection failed: " . $conn->connect_error);
    }

    create_database($conn, $dbname);

    $conn->close();

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error)
    {
      error_log("\nConnection failed: " . $conn->connect_error, 3, parse_ini_file('sianct.ini')['log']);
      die("Connection failed: " . $conn->connect_error);
    }

    $sql_projects = file_get_contents("./sql/create/create_projects_table.sql");
    $sql_subprojects = file_get_contents("./sql/create/create_subprojects_table.sql");
    $sql_plots = file_get_contents("./sql/create/create_plots_table.sql");
    $sql_deployments = file_get_contents("./sql/create/create_deployments_table.sql");
    $sql_species = file_get_contents("./sql/create/create_species_table.sql");
    $sql_observations = file_get_contents("./sql/create/create_observations_table.sql");

    create_table($conn, $sql_projects, "projects");
    create_table($conn, $sql_subprojects, "subprojects");
    create_table($conn, $sql_plots, "plots");
    create_table($conn, $sql_deployments, "deployments");
    create_table($conn, $sql_species, "species");
    create_table($conn, $sql_observations, "observations");

    $conn->close();
  }

  function create_database($conn, $dbname)
  {
    // Create database
    $sql = "CREATE DATABASE $dbname";

    if ($conn->query($sql) === TRUE)
    {
      //echo "Database created successfully";
    }
    else
    {
      error_log("\nError creating database: " . $conn->error . "\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }

  function create_table($conn, $sql, $table)
  {
    if ($conn->query($sql) === TRUE)
    {
        //echo "Table $table created successfully\n";
    }
    else
    {
        error_log("Error creating table: " . $conn->error . "\n", 3, parse_ini_file('sianct.ini')['log']);
    }
  }
