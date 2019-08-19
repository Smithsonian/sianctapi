<?php

  //test_insert();

  function test_insert()
  {
    $config = parse_ini_file("sianct.ini");

    $servername = $config["servername"];
    $username = $config["username"];
    $password = $config["password"];
    $dbname = $config["dbname"];

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error)
    {
      die("Connection failed: " . $conn->connect_error);
    }

    $data = Array(
      "sidora_project_id" => "test_project_pid",
      "ct_project_id" => "test_ct_project_id",
      "name" => "test project",
      "lon" => "test longitude",
      "lat" => "test latitude",
      "publish_date" => "today",
      "summary" => "a test project"
    );

    $isInserted = table_insert("projects", $conn, $data);

    if ($isInserted)
    {
      echo "Insert into table projects successful\n";
    }
    else
    {
      echo "Failed to insert data into table projects\n";
    }
  }

  function table_insert($table, $data)
  {
    $config = parse_ini_file("sianct.ini");

    $servername = $config["servername"];
    $username = $config["username"];
    $password = $config["password"];
    $dbname = $config["dbname"];

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error)
    {
      die("Connection failed: " . $conn->connect_error);
    }

    $cols = "";
    $vals = "";

    $count = 1;

    foreach($data as $key => $value)
    {
      $cols .= $key;
      $vals .= "'" . $value . "'";

      if($count < count($data))
      {
        $cols .= ",";
        $vals .= ",";
      }

      $count++;
    }

    $sql = "INSERT INTO $table ($cols) VALUES ($vals)";
    echo "SQL Query is: $sql\n";

    if ($conn->query($sql) === TRUE)
    {
      echo "New record created successfully in $table\n";
      return TRUE;
    }
    else
    {
      echo "Error: " . $sql . "<br>" . $conn->error . "\n";
      return FALSE;
    }
  }
?>
