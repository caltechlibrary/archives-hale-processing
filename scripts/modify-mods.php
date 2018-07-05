<?php

// script purposes:
// - load each page MODS file and rewrite the title line
// - add PIDs of rewritten files to /path/to/destination/files/pids file
//
// assumptions:
// - book and page MODS files were generated with
//   `drush islandora_datastream_crud_fetch_datastreams` and are all located in
//   /path/to/source/files directory
// - the namespace of each page is 'hale'
// - each page sequence number has four digits, like '0123'
//
// run like:
// php /path/to/modify-mods.php /path/to/source/files /path/to/destination/files

// check for directory arguments and set variables
if (isset($argv[1]) && isset($argv[2])) {
  $path_source = $argv[1];
  $path_destination = $argv[2];
}
else {
  exit("\n🚫  \e[1;91mSTOP!\e[0m The source and destination directories must be supplied.\nExample: php /path/to/modify-mods.php /path/to/source/files /path/to/destination/files\n\n");
}

// recreate the PIDs file every time because we are appending to it
$page_pids_file = $path_destination . '/page_pids';
if (file_exists($page_pids_file)) {
  unlink($page_pids_file);
}

// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($path_source);
foreach ($dirItem as $fileInfo) {
    if ((!$fileInfo->isDot()) && ($fileInfo->getExtension() == 'xml')) {
        $filename = $fileInfo->getFilename();

        // parse $filename to get PID
        $filename_parts = explode('_', $filename);
        $pid = $filename_parts[0] . ':' . $filename_parts[1];

        // load MODS file
        $mods = simplexml_load_file($path_source . '/' . $filename);

        // note: page MODS files have 'mods' namespace
        // see: http://us1.php.net/manual/en/simplexmlelement.children.php
        $title = $mods->children('mods', TRUE)->titleInfo->title;

        if (strpos($title, ', page 0') > 0) {
            // when the page number string has leading zeros we strip them out
            // example title: `J. L. Kandel, page 0005`
            // get the last 4 characters of the title and trim 0s
            $page_number = trim(substr($title, -4), '0');
            // get the title without the last 4 characters and append the page number
            $title_new = substr($title, 0, -4) . $page_number;
            // set new title in $mods object
            $mods->children('mods', TRUE)->titleInfo->title = $title_new;
            // see: http://us1.php.net/manual/en/simplexmlelement.asxml.php
            $mods->asXML($path_destination . '/' . $filename);
            // write PID to $page_pids_file
            $append_handle = fopen($page_pids_file, 'a') or die('cannot open file: ' . $page_pids_file);
            fwrite($append_handle, "$pid\n");
            fclose($append_handle);
            echo "✅  wrote $title_new to $filename\n";
        }
        elseif (strpos($title, ', page 0') === 0) {
            // when the MODS title starts with the page number string, warn us
            // example title: `, page 0004`
            echo "🚫  $filename has a malformed title: $title\n";
        }
        elseif (strpos($title, 'hale:') === 0) {
            // when MODS title begins with the namespace we must get the real
            // title from the book MODS file
            // example title: `hale:11176-0097`
            $book_pid = substr(trim($title, 'hale:'), 0, -5);
            // get the title from the book MODS file
            $book_mods = simplexml_load_file($path_source . '/hale_' . $book_pid . '_MODS.xml');
            $book_title = $book_mods->titleInfo->title;
            // get the last 4 characters of the title and trim 0s
            $page_number = trim(substr($title, -4), '0');
            // get the book title and append the page number string
            $title_new = $book_title . ', page ' . $page_number;
            // set new title in $mods object
            $mods->children('mods', TRUE)->titleInfo->title = $title_new;
            // see: http://us1.php.net/manual/en/simplexmlelement.asxml.php
            $mods->asXML($path_destination . '/' . $filename);
            // write PID to $page_pids_file
            $append_handle = fopen($page_pids_file, 'a') or die('cannot open file: ' . $page_pids_file);
            fwrite($append_handle, "$pid\n");
            fclose($append_handle);
            echo "✅  wrote $title_new to $filename\n";
        }
        else {
            echo "↩️  skipping file: $filename\n";
        }

    }
}
