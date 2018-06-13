<?php

// check for and supply directory argument
if (isset($argv[1])) {
  $dir = $argv[1];
}
else {
  exit("\n\e[1;91mSTOP!\e[0m A directory argument must be supplied.\n");
}

// find all the OBJ.tiff files
$tiffs = glob($dir . '/*/*/OBJ.tiff');
foreach ($tiffs as $fullpath) {
  // each part of the path becomes an array item
  $obj_path_array = explode('/', $fullpath);
  // remove the last item (OBJ.tiff) from array
  array_pop($obj_path_array);
  // set up the full directory path for the page
  $page_dir = implode('/', $obj_path_array);
  // keep the last element so the page number is then $page_num_array[0]
  $page_num_array = array_slice($obj_path_array, -1);
  $page_num = $page_num_array[0];
  // remove the last item (the page number) from array
  array_pop($obj_path_array);
  // set up the full file path for the parent and its MODS file
  $parent_dir = implode('/', $obj_path_array);
  // keep the last element (the parent folder)
  $parent_folder_array = array_slice($obj_path_array, -1);
  $parent_folder = $parent_folder_array[0];

  // make directory for TIFFs
  $tiff_page_dir = $dir . '/TIFFs/' . $parent_folder . '/' . $page_num;
  if (!mkdir($tiff_page_dir, 0777, TRUE)) {
    echo "🚫  mkdir $tiff_page_dir failed\n";
  }
  if (!rename($page_dir . '/OBJ.tiff', $tiff_page_dir . '/OBJ.tiff')) {
    echo "🚫  moving " . $parent_folder . '/' . $page_num . "/OBJ.tiff failed\n";
  }
  if (!copy($page_dir . '/JP2.jp2', $page_dir . '/OBJ.jp2')) {
    echo "🚫  creating " . $parent_folder . '/' . $page_num . "/OBJ.jp2 failed\n";
  }
  else {
    echo "🤖  created \033[100m" . $parent_folder . '/' . $page_num . '/OBJ.jp2' . "\033[0m\n";
  }

}
