<?php

// We need to gather data from two different sets of files and compile it into
// single instances of two files.
//
// From every bag-info.txt file we need to get every Payload-Oxum value and sum
// both components into totals. (The Payload-Oxum value is not an integer; it is
// the total number of bytes in the payload and the total number of files in the
// payload separated by a full stop character.) The totals will be added to a
// new bag-info.txt file that will be used for the whole collection.
//
// From every manifest-sha512.txt file we need to get every line and concatenate
// it into a new manifest-sha512.txt file that will be used for the whole
// collection.
//
// We also need to save a bagit.txt file for the whole collection.

// get configuration
$config_file = __DIR__ . "/../config.bagit.inc";
if (file_exists($config_file)) {
  include $config_file;
}
else {
  exit("\n🚫 exited: config.bagit.inc file does not exist\n");
}
if (empty("$bag_destination_path") || empty("$collection_id")) {
  exit("\n🚫 exited: parameters not set in config.bagit.inc file\n");
}

$payload_oxum = array();
$bytes = 0;
$files = 0;
$manifest = '';

// loop through directories
$objects = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($bag_destination_path, FilesystemIterator::SKIP_DOTS)
);
foreach($objects as $object) {
  if ($object->isDir()) {
    continue;
  }
  $pathname = $object->getPathname();
  if (strpos($pathname, "{$collection_id}/bag-info.txt") !== FALSE) {
    // we have the top-level bag-info.txt file
    $baginfo_source = file($pathname);
    foreach ($baginfo_source as $line) {
      if (strpos($line, 'Payload-Oxum: ') !== FALSE) {
        $payload_oxum = explode('.', substr_replace('Payload-Oxum: ', '', 0));
        $bytes = (int) $payload_oxum[0] + $bytes;
        $files = (int) $payload_oxum[1] + $files;
      }
    }
  }
  if (strpos($pathname, "{$collection_id}/manifest-sha512.txt") !== FALSE) {
    // we have the top-level manifest-sha512.txt file
    $manifest .= file_get_contents("$pathname");
  }
}

$baginfo = array(
  'Bag-Software-Agent: bagit.py v1.7.0 <https://github.com/LibraryOfCongress/bagit-python>',
  'Bagging-Date: ' . date('Y-m-d'),
  'Contact-Email: pcollopy@caltech.edu',
  'Contact-Name: Peter Collopy',
  'External-Description: Manuscript collection of astrophysicist George Ellery',
  '  Hale’s personal and professional papers, digitized from microfilm into',
  '  unprocessed grayscale TIFF files.',
  'External-Identifier: HaleGE',
  'Payload-Oxum: ' . $bytes . '.' . $files,
  'Source-Organization: Caltech Archives',
  '',
);
file_put_contents("{$bag_destination_path}/bag-info.txt", implode("\n", $baginfo));

file_put_contents("{$bag_destination_path}/manifest-sha512.txt", $manifest);
