<?php

// check the config file
$config_file = __DIR__ . "/boxes-config.inc";
if (file_exists($config_file)) {
  include $config_file;
  foreach (['index_csv','source_csv','destination_parent','reel_source_path'] as $config) {
    if (!file_exists($$config)) {
      $stop = TRUE;
      echo "\n\e[0;91mSTOP!\e[0m The value for \e[0;96m\$$config\e[0m in \e[0;95m$config_file\e[0m is invalid.\n";
    }
  }
  if ($stop) {
    exit("\nExited. One or more variables in \e[0;95m$config_file\e[0m is invalid.\n");
  }
}
else {
  exit("\n\e[1;91mSTOP!\e[0m the \e[0;96m\$boxes-config.inc\e[0m file does not exist.\n");
}

// check for and supply index argument
if (isset($argv[1])) {
  $boxes_index = $argv[1];
}
else {
  exit("\n\e[1;91mSTOP!\e[0m An index argument must be supplied.\n");
}

// prompt to set the open file soft limit higher in the shell before running;
// needed for pdfunite to open many files: `ulimit -Sn 2048`
echo "\nLast chance to ^C and set \e[1;32mulimit -Sn 2048\e[0m...\n\n";
for ($i = 5; $i > 0; $i--) {
  echo " $i\n";
  sleep(1);
}

// save the csv data to an array
if (($handle = fopen($index_csv, "r")) !== FALSE) {
  while (($record = fgetcsv($handle)) !== FALSE) {
    $data[$record[0]] = $record;
  }
  fclose($handle);
}
// access the csv data in the array based on the supplied index number
$box_start = $data[$boxes_index][1];
$box_end = $data[$boxes_index][2];
$box_padded_start = str_pad($box_start, 3, '0', STR_PAD_LEFT);
$box_padded_end = str_pad($box_end, 3, '0', STR_PAD_LEFT);

$destination_path = "{$destination_parent}/boxes-{$box_padded_start}-{$box_padded_end}";
if (!file_exists($destination_path)) {
  mkdir($destination_path);
}

$base_file = __DIR__ . "/base.inc";
if (file_exists($base_file)) {
  include $base_file;
}
else {
  exit("\n\e[1;91mSTOP!\e[0m the base file does not exist.\n");
}
