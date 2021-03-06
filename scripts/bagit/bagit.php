<?php

/**
 * Set up the structure for BagIt on the Hale Microfilm scans.
 *
 * We open the CSV file, save it to an array, loop over every record, set up the
 * strings to construct file paths, calculate source file locations, set up page
 * numbers for the files within the bags... (in progress)
 */

// get the path to the csv file from the command-line argument
if (empty($argv[1])) {
  exit("\n🚫 exited: please supply the path to a CSV file\n➡️  example: php bagit.php /path/to/file.csv\n");
}
elseif (!file_exists($argv[1])) {
  exit("\n🚫 exited: no file exists at supplied path\n➡️  example: php bagit.php /path/to/file.csv\n");
}
else {
  $csv_file = $argv[1];
}

$config_file = __DIR__ . "/config.bagit.inc";
if (file_exists($config_file)) {
  include $config_file;
}
else {
  exit("\n🚫 exited: config.bagit.inc file does not exist\n");
}

if (empty("$csv_file") || empty("$reel_source_path") || empty("$bag_destination_path") || empty("$islandora_url") || empty("$collection_id") || empty("$source_organization") || empty("$contact_email")) {
  exit("\n🚫 exited: parameters not set in config.bagit.inc file\n");
}

$external_id = ''; // set below
$external_description = ''; // set below
$bag_size = ''; // set below
$bag_group_id = $collection_id;
// Series Names
$series_names = [
  '1' => 'Personal Correspondence and Documents Relating to Individuals',
  '2' => 'Correspondence and Documents Relating to Organizations',
  '3' => 'Family Letters',
  '4' => 'Scientific Work',
  '5' => 'Biographical and Personal Material',
  '6' => 'Drafts of Articles',
  '7' => 'National Research Council Documents',
  '8' => 'California Institute of Technology Documents',
  '9' => 'League of Nations Committee on Intellectual Cooperation Documents',
  '10' => 'Director’s Files of the Mount Wilson Observatory',
];
// Subseries Names; only Series 2 contains Subseries
$subseries_names = [
  'A' => 'International',
  'B' => 'National Academy of Sciences',
  'C' => 'National Research Council',
  'D' => 'General',
];

// open the csv file and save the data to an array
// NOTE: no real validation is done of the file contents
if (($handle = fopen("$csv_file", "r")) !== FALSE) {
  while (($record = fgetcsv($handle)) !== FALSE) {
    $data[] = $record;
  }
  fclose($handle);
}

//print_r($data);

// remove the first row which contains column names
array_shift($data);

$folder_timer_start = microtime(TRUE);

// loop over each record that represents a line in the spreadsheet
foreach ($data as $folder_data) {

  # skip lines where the first value is blank
  if (empty($folder_data[0])) {
    continue;
  }

  # skip lines where there is no reel number
  if (empty($folder_data[12])) {
    continue;
  }

  // set up all variables and paths

  // pad the series number with zeros totaling 2 digits
  $series_number_padded = str_pad($folder_data[0], 2, '0', STR_PAD_LEFT);
  // pad the subseries character with zeros totaling 2 characters
  if (!empty($folder_data[1])) {
    $subseries_chars_padded = str_pad($folder_data[1], 2, '0', STR_PAD_LEFT);
  }
  else {
    $subseries_chars_padded = '00';
  }
  // pad the box number with zeros totaling 3 digits
  $box_number_padded = str_pad($folder_data[2], 3, '0', STR_PAD_LEFT);
  // pad the folder number with zeros totaling 2 digits
  $folder_number_padded = str_pad($folder_data[3], 2, '0', STR_PAD_LEFT);
  // replace non-alphanumeric characters in series name with underscores
  $series_name_alnum = preg_replace('/[^[:alnum:]]/', '_', $series_names[$folder_data[0]]);
  // replace non-alphanumeric characters in subseries name with underscores
  if (!empty($folder_data[1])) {
    $subseries_name_alnum = preg_replace('/[^[:alnum:]]/', '_', $subseries_names[$folder_data[1]]);
  }
  // replace non-alphanumeric characters in folder name with underscores
  $folder_name_alnum = preg_replace('/[^[:alnum:]]/', '_', $folder_data[4]);

  // set up directory strings
  $collection_directory_string = $collection_id;
  $folder_directory_string = "{$collection_directory_string}_{$series_number_padded}_{$subseries_chars_padded}_{$box_number_padded}_{$folder_number_padded}_{$folder_name_alnum}";
  $collection_directory_path = "{$bag_destination_path}/{$folder_directory_string}/{$collection_directory_string}";
  $series_directory_string = "{$collection_directory_string}_{$series_number_padded}_{$series_name_alnum}";
  if (!empty($folder_data[1])) {
    $subseries_directory_string = "{$collection_directory_string}_{$series_number_padded}_{$subseries_chars_padded}_{$subseries_name_alnum}";
  }

  // set up full path conditionally with subseries
  if (!empty($folder_data[1])) {
    // echo "{$collection_directory_string}/{$series_directory_string}/{$subseries_directory_string}/{$folder_directory_string} \n";
    $folder_directory_path = "{$collection_directory_path}/{$series_directory_string}/{$subseries_directory_string}/{$folder_directory_string}";
  }
  else {
    // echo "{$collection_directory_string}/{$series_directory_string}/{$folder_directory_string} \n";
    $folder_directory_path = "{$collection_directory_path}/{$series_directory_string}/{$folder_directory_string}";
  }

  // calculate the reel directory name
  $reel_number = $folder_data[12];
  $reel_number_padded = str_pad($reel_number, 3, '0', STR_PAD_LEFT);
  // echo "REEL NUMBER: $reel_number \n";
  $reel_directory = "{$reel_source_path}/Hale Reel#{$reel_number_padded}";
  // echo "REEL DIRECTORY: $reel_directory \n";

  $first_file_number = $folder_data[17];
  $last_file_number = $folder_data[18];

  // set up logs directory
  if (!is_dir("{$bag_destination_path}/logs/{$folder_directory_string}")) {
    if (!mkdir("{$bag_destination_path}/logs/{$folder_directory_string}", 0777, TRUE)) {
      exit("🚫 exited: failed to create {$bag_destination_path}/logs/{$folder_directory_string} directory...\n");
    }
  }
  $logs_directory = "{$bag_destination_path}/logs/{$folder_directory_string}";

  // actually start processing

  echo "\n📂 begin processing {$folder_directory_string}\n";

  // loop over all the files for the folder record and then calculate a page number
  $file_count = $folder_data[19];
  $file_counter = 0;
  $filesize_counter = 0;
  while ($file_counter < $file_count) {

    // the file counter is added to the file number so that we can continue
    // iterating through all the files that belong in the folder; on the first
    // pass the file number is added to the initial file counter of 0
    $source_file_number_padded = str_pad($first_file_number + $file_counter, 5, '0', STR_PAD_LEFT);
    $source_file_name = 'Reel #' . $reel_number_padded . '_' . $source_file_number_padded . '.tif';
    // echo "FILE NUMBER: $source_file_number_padded \n";
    // echo "FILE NAME: $source_file_name \n";

    $source_file_path = "{$reel_directory}/frames/{$source_file_name}";
    if (file_exists($source_file_path)) {
      // echo "FILE PATH: $source_file_path \n";

      // the page number needs a +1 from the counter so that the first page is
      // numbered as 0001 and not 0000
      $page_number_padded = str_pad($file_counter + 1, 4, '0', STR_PAD_LEFT);
      // echo "PAGE NUMBER: $page_number_padded \n";
      $folder_files_prefix = "{$collection_directory_string}_{$series_number_padded}_{$subseries_chars_padded}_{$box_number_padded}_{$folder_number_padded}";
      $page_filename = "{$folder_files_prefix}_{$page_number_padded}.tiff";
      $page_file_path = "{$folder_directory_path}/{$page_filename}";

      // make directories and copy the file
      if (!is_dir($folder_directory_path)) {
        if (!mkdir($folder_directory_path, 0777, TRUE)) {
          exit("🚫 failed to create $folder_directory_path directory structure...\n");
        }
      }
      // debug
      echo "{$source_file_name} ➜ {$page_filename}\n";
      if (!copy($source_file_path, $page_file_path)) {
        echo "🚫 failed to copy {$source_file_path}...\n";
      }

      $filesize = filesize($page_file_path);

    }
    else {
      exit("🚫 $source_file_path does not exist...\n");
    }

    $filesize_counter = $filesize_counter + $filesize;
    $file_counter++;

  } // end file loop

  // download the MODS XML for the folder
  if (!empty($islandora_url) && is_dir($folder_directory_path)) {
    file_put_contents("{$folder_directory_path}/{$folder_files_prefix}.xml", fopen("{$islandora_url}/islandora/object/{$folder_data[22]}/datastream/MODS/view", 'r'));
  }

  // we begin the external description string and continue conditionally with
  // concatenating assignment (.=) below
  $external_description = "{$folder_data[4]} folder, ";

  // we need to use different date fields depending on which have values in them
  $date_exists = FALSE;
  // if Structured Date and Date Qualifier both have content
  if (!empty($folder_data[5]) && !empty($folder_data[6])) {
    $date_exists = TRUE;
    $external_description .= "{$folder_data[5]} ({$folder_data[6]})";
  }
  // else if Structured Date has content
  elseif (!empty($folder_data[5])) {
    $date_exists = TRUE;
    $external_description .= $folder_data[5];
  }
  // else if Start Date and End Date have content
  elseif (!empty($folder_data[7]) && !empty($folder_data[8])) {
    $date_exists = TRUE;
    $external_description .= "{$folder_data[7]}–{$folder_data[8]}";
  }
  // if Textual Date has content
  if (!empty($folder_data[10]) && $date_exists) {
    $external_description .= ", {$folder_data[10]}";
  }
  elseif (!empty($folder_data[10]) && !$date_exists) {
    $external_description .= "{$folder_data[10]}";
  }

  $external_description .= ", from Series {$folder_data[0]}: {$series_names[$folder_data[0]]}";
  if (!empty($folder_data[1])) {
    $external_description .= "; Subseries {$folder_data[1]}: {$subseries_names[$folder_data[1]]}";
  }

  $external_description .= ", George Ellery Hale Papers, Caltech Archives. Digitized from microfilm into unprocessed grayscale TIFF files with accompanying metadata in MODS format.";
  // NOTE: bagit-python does not seem to respect line breaks passed in the
  // command line options, so we cannot strictly follow the recommendation for
  // line wrapping in bagit-info.txt
  // see https://github.com/LibraryOfCongress/bagit-python/issues/126
//  $external_description = "External-Description: $external_description";
//  $external_description = wordwrap($external_description, 79, "\r\n ");
//  $external_description = str_replace("External-Description: ", '', $external_description);

  $external_id = $folder_directory_string;

  try {
    $bag_size = human_filesize($filesize_counter + filesize("{$folder_directory_path}/{$folder_files_prefix}.xml"));
  }
  catch (Exception $e) {
    echo '🛑 caught exception: ',  $e->getMessage(), "\n";
  }

  // use a single process unless set differently in config file
  if (empty($processes)) {
    $processes = '1';
  }

  //
  // We need to actually bag each folder twice: the first time at the folder
  // level itself, and the second time at the collection level. The different
  // folder-collection-level metadata will be compiled into the final
  // bag-info.txt file and manifest-sha512.txt file in the permanent top-level
  // collection directory.
  //

  // bagit

  echo "\nℹ️  begin bagging folder: {$folder_directory_string}\n";

  $folder_directory_realpath = realpath($folder_directory_path);
  // debug
  echo "\n🐞 python3 -m bagit --wrap 79 --sha512 --processes '{$processes}' --log '{$logs_directory}/{$folder_files_prefix}_bagit.log' --source-organization '{$source_organization}' --contact-email '{$contact_email}' --external-description '{$external_description}' --external-identifier '{$external_id}' --bag-size '{$bag_size}' --bag-group-identifier '{$bag_group_id}' '{$folder_directory_realpath}'\n";
  exec("python3 -m bagit --wrap 79 --sha512 --processes '{$processes}' --log '{$logs_directory}/{$folder_files_prefix}_bagit.log' --source-organization '{$source_organization}' --contact-email '{$contact_email}' --external-description '{$external_description}' --external-identifier '{$external_id}' --bag-size '{$bag_size}' --bag-group-identifier '{$bag_group_id}' '{$folder_directory_realpath}'");

  //
  // We are creating intermediate bags that will be used to supply metadata to
  // the final collection-level bag.
  //

  echo "\nℹ️  begin bagging intermediate collection for: {$folder_directory_string}\n";

  $external_description = "THIS IS AN INTERMEDIATE BAG THAT WILL ONLY BE USED FOR HARVESTING THE 'Payload-Oxum' DATA FROM THE 'bag-info.txt' FILE AND THE COMPLETE 'manifest-sha512.txt' FILE.";

  $collection_directory_realpath = realpath($collection_directory_path);

  // debug
  echo "\n🐞 python3 -m bagit --sha512 --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit.log' --external-description '{$external_description}' '{$collection_directory_realpath}'\n";

  exec("python3 -m bagit --sha512 --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit.log' --external-description '{$external_description}' '{$collection_directory_realpath}'");

  // remove any .DS_Store files from the file structure before validation
  $objects = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($collection_directory_realpath, FilesystemIterator::SKIP_DOTS)
  );
  foreach($objects as $object) {
    if (basename($object) == '.DS_Store') {
      unlink($object);
    }
  }

  // validate

  // debug
  echo "\n🐞 python3 -m bagit --validate --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate.log' '{$collection_directory_realpath}'\n";

  exec("python3 -m bagit --validate --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate.log' '{$collection_directory_realpath}'", $output, $validate_return_status);

  //
  // offload moving the bags around to another script
  // @TODO: create a command line flag to choose among options here
  // for example, one might move files and one might copy files
  //

  // mark validation status so watcher script can take over
  if ($validate_return_status == 0) {

    // make 'validated' directory if it does not exist
    if (!is_dir("{$bag_destination_path}/validated")) {
      if (!mkdir("{$bag_destination_path}/validated", 0777, TRUE)) {
        exit("🚫 exited: failed to create {$bag_destination_path}/validated directory...\n");
      }
    }

    // touch a file to indicate folder has been validated
    touch("{$bag_destination_path}/validated/{$folder_directory_string}");

    // run script to move files to s3 if it is not running already
    if (file_exists("{$bag_destination_path}/aws-s3-mv.running")) {
      echo "\n🤖 move script is already running\n";
    }
    else {
      $aws_s3_mv = __DIR__ . "/aws-s3-mv.sh";
      // debug
      echo "\n🐞 bash {$aws_s3_mv} {$bag_destination_path} > {$logs_directory}/{$folder_files_prefix}_bagit-aws-s3-mv.log &\n";
      exec("bash {$aws_s3_mv} {$bag_destination_path} > {$logs_directory}/{$folder_files_prefix}_bagit-aws-s3-mv.log &");
    }

  }
  else {
    // batch did not validate
    echo "\n🐞 did not validate \n";
  }

} // end folder loop

$folder_time = (microtime(TRUE) - $folder_timer_start);
echo "\n⏱  folder time: {$folder_time} (final aws s3 mv operation may still need to run)\n";

// run the move script in a new process one last time to catch any final
// validated files that were not caught in the last for loop of the move script
$aws_s3_mv = __DIR__ . "/aws-s3-mv.sh";
// debug
echo "\n🐞 " . 'exec(\"bash {$aws_s3_mv} {$bag_destination_path}\", $aws_s3_mv_output, $aws_s3_mv_return_status)' . "\n";
exec("bash {$aws_s3_mv} {$bag_destination_path}", $aws_s3_mv_output, $aws_s3_mv_return_status);

// log the output
file_put_contents("{$logs_directory}/{$folder_files_prefix}_bagit-aws-s3-mv-FINAL.log", implode("\n", $aws_s3_mv_output));

if ($aws_s3_mv_return_status === 0) {

  rmdir("{$bag_destination_path}/validated");

  // start the top-level bag compilation script
  $compile_collection_bag = __DIR__ . "/compile-collection-bag.php";
  // debug
  echo "\n🐞 " . 'exec("php $compile_collection_bag")' . "\n";
  exec("php $compile_collection_bag");

}

//// debug
//echo "\n🗄  begin processing top-level: {$collection_id}\n";
//
//$external_description = "Manuscript collection of astrophysicist George Ellery Hale’s personal and professional papers, digitized from microfilm into unprocessed grayscale TIFF files.";
//// NOTE: bagit-python does not seem to respect line breaks passed in the
//// command line options, so we cannot strictly follow the recommendation for
//// line wrapping in bagit-info.txt
//// see https://github.com/LibraryOfCongress/bagit-python/issues/126
////$external_description = "External-Description: $external_description";
////$external_description = wordwrap($external_description, 79, "\r\n ");
////$external_description = str_replace("External-Description: ", '', $external_description);
//
//$external_id = $collection_id;
//
//// use a single process unless set differently in config file
//if (empty($processes)) {
//  $processes = '1';
//}
//
//$collection_timer_start = microtime(TRUE);
//
//// bagit all
//$collection_directory_realpath = realpath($collection_directory_path);
//// debug
//echo "\n🐞 python3 -m bagit --sha512 --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit.log' --source-organization '{$source_organization}' --contact-name '{$contact_name}' --contact-email '{$contact_email}' --external-description '{$external_description}' --external-identifier '{$external_id}' '{$collection_directory_realpath}'\n";
//exec("python3 -m bagit --sha512 --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit.log' --source-organization '{$source_organization}' --contact-name '{$contact_name}' --contact-email '{$contact_email}' --external-description '{$external_description}' --external-identifier '{$external_id}' '{$collection_directory_realpath}'");
//// debug
////echo "🤖 python3 -m bagit --validate --fast --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate-fast.log' '{$collection_directory_realpath}'\n";
////exec("python3 -m bagit --validate --fast --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate-fast.log' '{$collection_directory_realpath}'");
//
//// remove any .DS_Store files from the file structure before validation
//$objects = new RecursiveIteratorIterator(
//  new RecursiveDirectoryIterator($collection_directory_realpath, FilesystemIterator::SKIP_DOTS)
//);
//foreach($objects as $object) {
//  if (basename($object) == '.DS_Store') {
//    unlink($object);
//  }
//}
//
//// debug
//echo "\n🐞 python3 -m bagit --validate --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate.log' '{$collection_directory_realpath}'\n";
//exec("python3 -m bagit --validate --processes '{$processes}' --log '{$logs_directory}/{$collection_id}_bagit-validate.log' '{$collection_directory_realpath}'", $output, $validate_return_status);
//
//if ($validate_return_status == 0) {
//  // move data folder to S3 and send output to log file
//  // NOTE: consider how best to divvy up resources; `aws s3 mv` takes place in
//  // the background for an unknown length of time after this script finishes;
//  // if many instances of this script are run in sequence, server resources will
//  // likely become exhausted at some point when uploads to aws are queued up for
//  // every processor
//  //// debug
//  echo "\n🐞 aws s3 mv {$collection_directory_realpath}/data s3://archives-bagit-tmp/{$collection_id}/data --recursive --no-progress --exclude '*.DS_Store*' > {$logs_directory}/{$collection_id}_bagit-aws-s3-mv.log &\n";
////  exec("aws s3 mv {$collection_directory_realpath}/data s3://archives-bagit-tmp/{$collection_id}/data --recursive --no-progress --exclude '*.DS_Store*' > {$logs_directory}/{$collection_id}_bagit-aws-s3-mv.log &");
//}
//else {
//  // batch did not validate
//  echo "\n🐞 did not validate \n";
//}
//
//$collection_time = (microtime(TRUE) - $collection_timer_start);
//echo "\n⏱  collection time: {$collection_time}\n";

// adapted from http://php.net/manual/en/function.filesize.php#116205
function human_filesize($bytes, $decimals = 2) {
  $prefixes = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  if ($bytes > 0 && $factor > 0) {
    $prefix = substr($prefixes, $factor, 1);
  }
  elseif ($bytes > 0 && $factor == 0) {
    $prefix = '';
  }
  else {
    throw new Exception('invalid file size (not greater than zero)');
  }
  return number_format($bytes / pow(1000, $factor), $decimals) . " {$prefix}B";
}

//// this should give the same output as `du -bs $path`
//function GetDirectorySize($path){
//  $bytestotal = 0;
//  $path = realpath($path);
//  if($path!==false && $path!='' && file_exists($path)){
//    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
//      $bytestotal += $object->getSize();
//    }
//  }
//  return $bytestotal;
//}

//echo GetDirectorySize($path) . "\n";

// this should give the same output as `find $path -type f | wc -l`
//$objects = new RecursiveIteratorIterator(
//  new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
//);
//foreach($objects as $name => $object){
//  // beware the cursed .DS_Store file...
//  if (basename($object) == '.DS_Store') {
//    unlink($object);
//  }
//}

//$count=iterator_count($objects);

//echo number_format($count) . "\n"; //680,642 wooohaah!
