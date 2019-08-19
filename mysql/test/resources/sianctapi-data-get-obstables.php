#!/usr/bin/php
<?php

// Set a really big memory limit for parsing cache values.
ini_set('memory_limit', '5G');

// The SIANCT API cache file
//define('CACHE_FILE', '/var/www/html/api/runtime/sianctapi-cache-file');
define('CACHE_FILE', '/Users/rbeall/Desktop/sianctp_mysql/test/resources/sianctapi-cache-file');

// The number of values (elements, lines) of a cache entry to print.
define('MAX_ARRAY_VALUES', 1);
//define('MAX_ARRAY_VALUES', 500000);

// The maximum line lenght of a cache entry to print.
define('MAX_LINE_LENGTH', 256);
//define('MAX_LINE_LENGTH', 70000000);

/**
 * Main
 */

function get_obstables()
{
  echo "\nYANKEE: " . CACHE_FILE . "\n";

  if (!file_exists(CACHE_FILE)) {
    printf('Could not find cache file: %s', CACHE_FILE);
    exit(1);
  }

  $file = file_get_contents(CACHE_FILE);
  if ($file === FALSE) {
    printf('Could not read cache file: %s', CACHE_FILE);
    exit(1);
  }

  $data = unserialize($file);
  if ($data === FALSE) {
    printf('Could not unserialized cache file: %s', CACHE_FILE);
    exit(1);
  }

  if (empty($data)) {
    printf('No data found in cache file: %s', CACHE_FILE);
    exit(1);
  }


  $max_key_length = 0;
  foreach (array_keys($data) as $key) {
    $key_length = strlen($key);
    if ($key_length > $max_key_length) {
      $max_key_length = $key_length;
    }
  }

  return $data["obstables"];
}



/*echo "YANKEY DOODLE: " . gettype($data["obstables"]) . "\n";
$count = 1;

foreach ($data["obstables"] as $obstable)
{
  $observations = explode("\n", $obstable);

  foreach ($observations as $obs)
  {
    echo "OBSERVATION: " . $obs . "\n\n";
  }

  $count++;

  if($count>1)
  {
    break;
  }
}

/*print 'Keys found in the cache file' . PHP_EOL;
printf("%" . ($max_key_length + 1) . "s\t%s\t%s", 'Name', 'Type', 'Length');
print PHP_EOL;
foreach (array_keys($data) as $key) {
  printf("%" . ($max_key_length + 1) . "s\t%s\t%d", $key, gettype($data[$key]), count($data[$key]));
  print PHP_EOL;
}
print PHP_EOL;

$file = 'projectStructure.html';
$current = file_get_contents($file);

foreach (array_keys($data) as $key) {
  $value = $data[$key];

  if($key == "projectStructure")
  {
    $current = "";
    $current = $value;
    file_put_contents($file, $current);
  }

  printf('# %s', $key);
  print PHP_EOL;

  if (is_string($value)) {
    echo 'String';
    $length = strlen($value);
    $value = str_replace("\r", "", $value);
    $value = explode("\n", $value);
    $count = count($value);

    if ($count > 1) {
      printf(', %d lines, %d characters', $count, $length);
    }
    elseif ($count == 1) {
      printf(', %d line, %d characters ', $count, $length);
    }
    else {
      echo ', no lines found' . PHP_EOL . PHP_EOL;
      continue;
    }

    echo PHP_EOL;
  }
  elseif (is_array($value)) {
    echo 'Array';
    printf(', %d elements', count($value));
    echo PHP_EOL;
  }
  else {
    echo 'Unexpected type' . PHP_EOL . PHP_EOL;
    continue;
  }

  printf('First %d %s (truncated to %d characters)', MAX_ARRAY_VALUES, (is_array($value) ? 'elements' : 'lines'), MAX_LINE_LENGTH);
  print PHP_EOL;

  $elements = array_slice($value, 0, MAX_ARRAY_VALUES, TRUE);
  foreach ($elements as $element) {
    print '  ' . substr($element, 0, MAX_LINE_LENGTH) . PHP_EOL;
  }

  print PHP_EOL;
}*/
