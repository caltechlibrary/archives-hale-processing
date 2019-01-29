<?php

// XXX load RELS-EXT file
// XXX if islandora:isPageNumber ends in 0, continue
// XXX store value
// XXX load MODS file
// XXX cut last "word" of title (numbers after final space character)
// XXX append page number value to title
// XXX rewrite MODS file
// XXX write PID to file

$path_source_rels_ext = '/tmp/hale_rels-ext';
$path_source_mods = '/tmp/new_page_mods';
$path_destination = '/tmp/page_mods_page_number_fix';

// recreate the PIDs file every time because we are appending to it
$page_pids_file = $path_destination . '/page_pids';
if (file_exists($page_pids_file)) {
    unlink($page_pids_file);
}

// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($path_source_rels_ext);
foreach ($dirItem as $fileInfo) {
    if ((!$fileInfo->isDot()) && ($fileInfo->getExtension() == 'rdf')) {
        $filename = $fileInfo->getFilename();

        // parse $filename to get PID
        $filename_parts = explode('_', $filename);
        $mods_filename = $filename_parts[0] . '_' . $filename_parts[1] . '_MODS.xml';
        $pid = $filename_parts[0] . ':' . $filename_parts[1];

        // load RELS-EXT file
        $rels_ext = simplexml_load_file($path_source_rels_ext . '/' . $filename);
        $page_number_zeros = $rels_ext->children('rdf', TRUE)->Description->children('islandora', TRUE)->isPageNumber;

        if (substr($page_number_zeros, -1) === '0') {
            if (!file_exists($path_source_mods . '/' . $mods_filename)) {
                echo "↩️  file doesn’t exist: $mods_filename\n";
                continue;
            }
            $mods = simplexml_load_file($path_source_mods . '/' . $mods_filename);
            $title = $mods->children('mods', TRUE)->titleInfo->title;
            // check the last character of the title for a zero
            if (substr($title, -1) === '0') {
                echo "↩️  skipping: $title\n";
                continue;
            }
            $title_parts = explode(' ', $title);
            $title_parts_no_number = array_slice($title_parts, 0, -1);
            $new_title_no_number = implode(' ', $title_parts_no_number);
            $new_page_number = ltrim($page_number_zeros, '0');
            $new_title = $new_title_no_number . ' ' . $new_page_number;

            $mods->children('mods', TRUE)->titleInfo->title = $new_title;
            $mods->asXML($path_destination . '/' . $mods_filename);

            $append_handle = fopen($page_pids_file, 'a') or die('cannot open file: ' . $page_pids_file);
            fwrite($append_handle, "$pid\n");
            fclose($append_handle);
            echo "✅  wrote $new_title ($pid) to $mods_filename\n";

        }

    }
}
