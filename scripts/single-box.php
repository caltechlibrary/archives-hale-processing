<?php

// check the config file
$config_file = __DIR__ . "/config.inc";
if (file_exists($config_file)) {
  include $config_file;
  foreach (['csv_source_file','destination_parent','reel_source_path'] as $config) {
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
  exit("\n\e[1;91mSTOP!\e[0m the \e[0;96mconfig.inc\e[0m file does not exist.\n");
}

// check for and supply box number argument
if (isset($argv[1])) {
  $box = $argv[1];
}
else {
  exit("\n\e[1;91mSTOP!\e[0m An box number argument must be supplied.\n");
}

// prompt to set the open file soft limit higher in the shell before running;
// needed for pdfunite to open many files: `ulimit -Sn 2048`
echo "\nLast chance to ^C and set \e[1;32mulimit -Sn 2048\e[0m...\n\n";
for ($i = 5; $i > 0; $i--) {
  echo " $i\n";
  sleep(1);
}

// $argv value is the box number
$box_start = $box;
$box_end = $box;
$box_padded = str_pad($box, 3, '0', STR_PAD_LEFT);

$destination_path = "{$destination_parent}/box-{$box_padded}";
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
