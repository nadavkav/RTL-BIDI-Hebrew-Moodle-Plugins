<?php

  require_once("../../../../../config.php");
  global $CFG;

  $uploads_dir = $_GET['courseid']."/users/".$_GET['userid'];

  // Extract information provided by PHP POST processor.
  $upfile_size = $_FILES['userfile']['size'];
  $raw_name = $_FILES['userfile']['name'];

  // Strip path info to prevent uploads outside target directory.
  $upfile_name = basename($raw_name);

	// Print relevent file information provided by PHP POST processor for debugging.
  echo "raw_name     = $raw_name\n";
  echo "name         = $upfile_name\n";
  echo "type         = " . $_FILES['userfile']['type'] . "\n";
  echo "size         = $upfile_size\n";
  echo "Upload dir   = $uploads_dir\n";

  $fromFile = $_FILES['userfile']['tmp_name'];
  $toFile = $CFG->dataroot . "/" . $uploads_dir . "/" . $upfile_name;
  if (!empty($upfile_name)) {
    $moveResult = move_uploaded_file( $fromFile, $toFile );
    if( $moveResult ) {
        echo "SUCCESS - $upfile_name uploaded.\n";
    }
  }

print_r($_POST);
print_r($_FILES);
print_r($_SERVER);
?>