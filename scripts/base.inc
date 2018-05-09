<?php

// set the open file soft limit higher in the shell before running;
// needed for pdfunite to open many files
// `ulimit -Sn 2048`

// this file does a number of things:
  // 1. creates a mods xml file
  // 2. creates a new directory structure for images
  // 3. creates a pdf with embedded ocr for each image
  // 4. creates ocr and hocr files for each image
  // 5. creates a multi-page pdf file for each folder of images
  // 6. creates a concatenated ocr file for each folder of images

// the overall processing in this file is as follows:
  // 1. open a csv file for reading
  // 2. loop through the file, one line/folder record at a time
  // 3. skip records that don't have the appropriate values in certain columns
  // 4. create a unique directory for each folder
  // 5. write a new mods xml file for each folder
  // 6. find the starting file for each folder
  // 7. loop through all the files in the folder
  // 8. copy the tiff file to a new directory structure
  // 9. create pdf and ocr files for each image
  // 10. compress the pdf file
  // 11. create a multi-page pdf file for each folder
  // 12. create a concatenated ocr file for each folder

if (($source_handle = fopen($source_csv, "r")) !== FALSE) {
  // skip the first line
  fgetcsv($source_handle);
  $row = 1;
  while (($data = fgetcsv($source_handle)) !== FALSE) {
    // set up variables
      // [0]: Series
      // [1]: Subseries
      // [2]: Box
      // [3]: Folder
      // [4]: Name
      // [5]: Structured Date
      // [6]: Date Qualifier
      // [7]: Start Date
      // [8]: End Date
      // [9]: Range Qualifier
      // [10]: Textual Date
      // [11]: Physical Description
      // [12]: Reel
      // [13]: First Frame
      // [14]: Last Frame
      // [15]: Frames
      // [16]: Title File
      // [17]: First File
      // [18]: Last File
      // [19]: Files
      // [20]: Processing Note
      // [21]: Contents
    $data_series = $data[0];
    $data_box = $data[2];
    $data_folder = $data[3];
    $data_name = $data[4];
    $data_reel = $data[12];
    $data_first_file = $data[17];
    $data_files = $data[19];

    // run when Folder, Reel, and Files values exist and Box is within range
    if (!empty($data_folder) && !empty($data_reel) && !empty($data_files)
        && $data_box >= $box_start && $data_box <= $box_end) {
      $foldertime_start = microtime(true);
      // create filesystem-safe directory names
      //
      // note: some names are duplicated, so a unique string is appended to the
      // directory name to keep each folder separate
      $directory_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data_name) . '_' . substr(str_shuffle(uniqid()), 0, 5);
      $directory_path = $destination_path . '/' . $directory_name;
      if (!file_exists($directory_path)) {
        mkdir($directory_path);
        // echo "‣ {$directory_name} directory created\n";
      }
      else {
        echo "‣‣‣ {$directory_name} directory name collision! exiting\n";
        exit;
      }
      $mods_file = $directory_path . '/MODS.xml';
      // recreate the MODS file every time
      if (file_exists($mods_file)) {
        unlink($mods_file);
      }

      // write to new MODS file
      $append_handle = fopen($mods_file, 'a') or die('Cannot open file: ' . $mods_file);
      fwrite($append_handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
      fwrite($append_handle, '<mods xmlns="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">' . "\n");

      // title always exists
      fwrite($append_handle, "  <titleInfo>\n");
      fwrite($append_handle, "    <title>{$data_name}</title>\n");
      fwrite($append_handle, "  </titleInfo>\n");

      // some kind of date always exists
      fwrite($append_handle, "  <originInfo>\n");
      // if Structured Date and Date Qualifier both have content
      if (!empty($data[5]) && !empty($data[6])) {
        fwrite($append_handle, '    <dateCreated encoding="w3cdtf" qualifier="' . $data[6] . '" keyDate="yes">' . $data[5] . '</dateCreated>' . "\n");
      }
      // else if Structured Date has content
      elseif (!empty($data[5])) {
        fwrite($append_handle, '    <dateCreated encoding="w3cdtf" keyDate="yes">' . $data[5] . '</dateCreated>' . "\n");
      }
      // else if Start Date and End Date have content
      elseif (!empty($data[7]) && !empty($data[8])) {
        fwrite($append_handle, '    <dateCreated encoding="w3cdtf" point="start" keyDate="yes">' . $data[7] . '</dateCreated>' . "\n");
        fwrite($append_handle, '    <dateCreated encoding="w3cdtf" point="end">' . $data[8] . '</dateCreated>' . "\n");
      }
      // if Textual Date has content
      if (!empty($data[10])) {
        fwrite($append_handle, "    <dateCreated>{$data[10]}</dateCreated>\n");
      }
      fwrite($append_handle, "  </originInfo>\n");

      // physicalDescription
      fwrite($append_handle, '  <physicalDescription>' . "\n");
      fwrite($append_handle, '    <extent unit="pages">' . $data[19] . '</extent>' . "\n");
      fwrite($append_handle, '  </physicalDescription>' . "\n");

      // relatedItem
      fwrite($append_handle, '  <relatedItem type="host">' . "\n");
      fwrite($append_handle, '    <titleInfo>' . "\n");
      fwrite($append_handle, '      <title>George Ellery Hale Papers</title>' . "\n");
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
      // only Series 2 contains Subseries
      $subseries_names = [
        'A' => 'International',
        'B' => 'National Academy of Sciences',
        'C' => 'National Research Council',
        'D' => 'General',
      ];
      $series_number = $data[0];
      $subseries_letter = $data[1];
      fwrite($append_handle, "      <partNumber>series {$series_number}</partNumber>\n");
      fwrite($append_handle, "      <partName>{$series_names[$series_number]}</partName>\n");
      if (!empty($subseries_letter)) {
        fwrite($append_handle, "      <partNumber>subseries {$subseries_letter}</partNumber>\n");
        fwrite($append_handle, "      <partName>{$subseries_names[$subseries_letter]}</partName>\n");
      }
      fwrite($append_handle, '    </titleInfo>' . "\n");
      fwrite($append_handle, '    <typeOfResource collection="yes" manuscript="yes">mixed material</typeOfResource>' . "\n");
      fwrite($append_handle, '    <language>' . "\n");
      fwrite($append_handle, '      <languageTerm authority="iso639-2b" type="code">eng</languageTerm>' . "\n");
      fwrite($append_handle, '    </language>' . "\n");
      fwrite($append_handle, '  </relatedItem>' . "\n");

      // identifier
      $identifier_string = 'GEH_' . $series_number . '_' . $data[2] . '_' . $data_folder;
      fwrite($append_handle, '  <identifier type="local">' . $identifier_string . '</identifier>' . "\n");

      // location
      fwrite($append_handle, '  <location>' . "\n");
      // if in Series 10 the material is located at Huntington Library
      if ($data[0] == '10') {
        fwrite($append_handle, "    <physicalLocation>Huntington Library</physicalLocation>\n");
      }
      else {
        fwrite($append_handle, "    <physicalLocation>Caltech Archives</physicalLocation>\n");
      }
      fwrite($append_handle, "    <physicalLocation type='boxNumber' displayLabel='Box'>{$data[2]}</physicalLocation>\n");
      fwrite($append_handle, "    <physicalLocation type='folderNumber' displayLable='Folder'>{$data_folder}</physicalLocation>\n");
      fwrite($append_handle, '  </location>' . "\n");

      // accessCondition
      fwrite($append_handle, '  <accessCondition type="use and reproduction">Unpublished works created by George Ellery Hale are in the public domain and may be reproduced freely. Copyright to works by others, and to Hale’s publications, may be held by their respective creators, those creator’s heirs, or their publishers. If you wish to quote or reproduce such works beyond the extent of fair use, please contact the copyright holder to request permission.</accessCondition>' . "\n");

      fwrite($append_handle, '</mods>' . "\n");
      fclose($append_handle);

      echo "\n‣‣‣ {$directory_name}/MODS.xml file created\n\n";

      // create directory structure, copy, and rename image files
      //
      // use 0 as the initial file value in order to calculate which number in
      // the sequence of the files we are on with the $data[17] value
      $file = 0;
      // set up empty array for all the generated PDF files
      $pdf_path_array = [];
      // set up empty array for all the generated OCR files
      $ocr_path_array = [];
      // continue when the file value is less than the total number of files
      while ($file < $data_files) {
        $filetime_start = microtime(true);
        // make the sequential folder with padded zeros, like 0001, 0002, etc.
        $obj_num = str_pad($file + 1, 4, '0', STR_PAD_LEFT);
        $obj_directory = $directory_path . '/' . $obj_num;
        if (!file_exists($obj_directory)) {
          mkdir($obj_directory);
        }
        // calculate the original directory name
        $reel_number_padded = str_pad($data_reel, 3, '0', STR_PAD_LEFT);
        $reel_directory = $reel_source_path . "/Hale Reel#" . $reel_number_padded;
        // calculate the current file number
        $file_number_padded = str_pad($data_first_file + $file, 5, '0', STR_PAD_LEFT);
        $file_name = 'Reel #' . $reel_number_padded . '_' . $file_number_padded . '.tif';
        // rename and copy files into the new structure
        if (file_exists($reel_directory . '/frames/' . $file_name)) {
          $copytime_start = microtime(true);
          copy($reel_directory . '/frames/' . $file_name, $obj_directory . '/OBJ.tiff');
          echo "‣‣‣ {$data_series}_{$data_box}_{$data_folder} {$data_name}\n";
          echo "{$file_name} copied to {$obj_num}/OBJ.tiff\n";
          $copytime = (microtime(true) - $copytime_start);
          echo "  copy time: {$copytime}\n";
          // generate PDF with embedded OCR text
          $tesseracttime_start = microtime(true);
          exec("tesseract {$obj_directory}/OBJ.tiff {$obj_directory}/generated txt hocr pdf 2>/dev/null");
          $tesseracttime = (microtime(true) - $tesseracttime_start);
          echo "  tesseract time: {$tesseracttime}\n";
          // reduce PDF file size
          $gstime_start = microtime(true);
          exec("gs -sDEVICE=pdfwrite -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$obj_directory}/PDF.pdf {$obj_directory}/generated.pdf");
          $gstime = (microtime(true) - $gstime_start);
          echo "  gs time: {$gstime}\n";
          // rename/remove generated files
          rename("{$obj_directory}/generated.txt", "{$obj_directory}/OCR.txt");
          rename("{$obj_directory}/generated.hocr", "{$obj_directory}/HOCR.html");
          unlink("{$obj_directory}/generated.pdf");
          // echo "‣‣‣ {$obj_num}/PDF.pdf created\n";
          // add path to the PDF to an array
          $pdf_path_array[] = "{$obj_directory}/PDF.pdf";
          // add path to the OCR to an array
          $ocr_path_array[] = "{$obj_directory}/OCR.txt";
        }
        $file++;
        $filetime = (microtime(true) - $filetime_start);
        echo "  file time: {$filetime}\n\n";
      }
      // loop through PDF path array to construct pdfunite source files list
      $pdf_paths_string = '';
      foreach ($pdf_path_array as $pdf_path) {
        $pdf_paths_string .= $pdf_path . ' ';
      }
      // create united PDF
      exec("pdfunite {$pdf_paths_string} {$directory_path}/PDF.pdf");
      // loop through OCR path array to construct to concatenate files
      $ocr_paths_string = '';
      foreach ($ocr_path_array as $ocr_path) {
        $ocr_paths_string .= $ocr_path . ' ';
      }
      // create concatenated OCR file
      exec("cat {$ocr_paths_string} > {$directory_path}/OCR.txt");
      // remove individual PDF files
      foreach ($pdf_path_array as $pdf_path) {
        unlink($pdf_path);
      }

      $foldertime = (microtime(true) - $foldertime_start);
      echo "folder time: {$foldertime}\n\n";
    };
    $row++;
  }
  fclose($source_handle);
}
else {
  exit("\n\e[1;91mSTOP!\e[0m source file did not open successfully.\n");
}
