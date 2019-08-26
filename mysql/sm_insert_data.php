<?php
  function table_insert($table, $data)
  {
    $results = Array(
      "message" => '',
      "success" => FALSE
    );

    $config = parse_ini_file("sianct.ini");

    $servername = $config["servername"];
    $username = $config["username"];
    $password = $config["password"];
    $dbname = $config["dbname"];

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error)
    {
      error_log("\n" . $conn->connect_error, 3, parse_ini_file('sianct.ini')['log']);
      die("Connection failed: " . $conn->connect_error);
    }

    $cols = "";
    $vals = "";

    $count = 1;

    foreach($data as $key => $value)
    {
      $cols .= $key;
      $vals .= '"' . $value . '"';

      if($count < count($data))
      {
        $cols .= ",";
        $vals .= ",";
      }

      $count++;
    }

    $sql = "INSERT INTO $table ($cols) VALUES ($vals)";

    if ($conn->query($sql) === TRUE)
    {
      $results['message'] = "New record created successfully in $table\n";
      $results['success'] = TRUE;
    }
    else
    {
      $results['message'] = "Error: " . $sql . "<br>" . $conn->error . "\n";
      $results['success'] = FALSE;
      error_log("\n" . $results['message'], 3, parse_ini_file("sianct.ini")['log']);
    }

    return $results;
  }
?>
