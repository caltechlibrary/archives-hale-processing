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
  $pagetime_start = microtime(true);
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
  $parent_mods_file = $parent_dir . '/MODS.xml';

  // // set up page title for MODS file
  // $time_start = microtime(true);
  // $folder_mods_xml = simplexml_load_file($parent_mods_file);
  // $page_title = $folder_mods_xml->titleInfo->title . ', page ' . $page_num;
  // echo "\n$page_title\n\n";
  // // recreate the MODS file every time because we are using append
  // $page_mods_file = $page_dir . '/MODS.xml';
  // if (file_exists($page_mods_file)) {
  //   unlink($page_mods_file);
  // }
  // // write a MODS file for the page
  // $append_handle = fopen($page_mods_file, 'a') or die('cannot open file: ' . $page_mods_file);
  // fwrite($append_handle, '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3" xmlns="http://www.loc.gov/mods/v3">' . "\n");
  // fwrite($append_handle, "  <mods:titleInfo>\n");
  // fwrite($append_handle, "    <mods:title>$page_title</mods:title>\n");
  // fwrite($append_handle, "  </mods:titleInfo>\n");
  // fwrite($append_handle, "</mods:mods>\n");
  // fclose($append_handle);
  // $time = (microtime(true) - $time_start);
  // echo "created MODS... $time\n";

  // // create a JP2
  // $time_start = microtime(true);
  // $create_jp2 = "kdu_compress -i $fullpath -o $page_dir/JP2.jp2 -rate 0.5 Clayers=1 Clevels=7 Cprecincts='{256,256},{256,256},{256,256},{128,128},{128,128},{64,64},{64,64},{32,32},{16,16}' Corder=RPCL ORGgen_plt=yes ORGtparts=R Cblk='{32,32}' Cuse_sop=yes";
  // exec($create_jp2);
  // $time = (microtime(true) - $time_start);
  // echo "created JP2... $time\n";

  // // create a JPG
  // $time_start = microtime(true);
  // $create_jpg = "convert -quiet $fullpath -quality 75 -resize 600x800 $page_dir/JPG.jpg";
  // exec($create_jpg);
  // $time = (microtime(true) - $time_start);
  // echo "created JPG... $time\n";

  // // create a TN
  // $time_start = microtime(true);
  // $create_tn = "convert -quiet $fullpath -thumbnail 200x200 -quality 75 $page_dir/TN.jpg";
  // exec($create_tn);
  // $time = (microtime(true) - $time_start);
  // echo "created TN... $time\n";
  // copy first page TN.jpg to parent
  if ($page_num == '0001') {
    copy("$page_dir/TN.jpg", "$parent_dir/TN.jpg");
  }

  // // add a PDF datastream for the page
  // $time_start = microtime(true);
  // $create_pdf = "pdfseparate -f $page_num -l $page_num $parent_dir/PDF.pdf $page_dir/PDF.pdf";
  // exec($create_pdf);
  // $time = (microtime(true) - $time_start);
  // echo "created PDF... $time\n";

  $pagetime = (microtime(true) - $pagetime_start);
  echo "\npage processing time: {$pagetime}\n\n";
}
