<?php
// HTML File Upload processor
// (C) Phil Burk, http://www.softsynth.com
// This version handles file upload in a simple way.

require_once("../../../../../config.php");

// Define directory to put file in.
// It must have read/write permissions accessable your web server.
	  //$uploads_dir = "uploads";
    $uploads_dir = $_GET['courseid']."/users/".$_GET['userid'];
    //$path = make_upload_directory($uploads_dir,false);

// Set maximum file size that your script will allow.
    $upfile_size_limit = 500000;

// These must come before anything else is printed so that they get in the header.
    header("Cache-control: private");
    header("Content-Type: text/plain");

// Get posted variables. Assume register_globals is off.
    $duration = strip_tags($_POST['duration']);

// Extract information provided by PHP POST processor.
    $upfile_size = $_FILES['userfile']['size'];
    $raw_name = $_FILES['userfile']['name'];
    // Strip path info to prevent uploads outside target directory.
    $upfile_name = basename($raw_name);

    // NOTE: you can change $upfile_name to anything you want. You can build names
    // based on a database ID or hash index, etc.

	// Print relevent file information provided by PHP POST processor for debugging.
    echo "raw_name     = $raw_name\n";
    echo "name         = $upfile_name\n";
    echo "type         = " . $_FILES['userfile']['type'] . "\n";
    echo "size         = $upfile_size\n";
    echo "Upload dir   = $uploads_dir\n";

// Applet always sends duration in seconds along with file.
    echo "duration     = " . $duration . "\n";

	// WARNING - IMPORTANT SECURITY RELATED INFORMATION!
    // You should to modify these checks to fit your own needs!!!
    // Check to make sure the filename is what you expected to
    // prevent hackers from overwriting other files.
	// ALso don't let people upload ".php" or other script files to your server.
	// Filename should end with ".wav" or ".spx".
	// For applications, we recommend building a filename from scratch based on
	// user information, time, etc.
    // These match the names used by
    // "test/record_upload_wav.html",  "test/record_upload_spx.html"
    // and "speex/record_speex.html".
//    if( (strcmp($upfile_name,"message_12345.wav") != 0) &&
//        (strcmp($upfile_name,"message_12345.spx") != 0) &&
//        (strcmp($upfile_name,"message_xyz.wav") != 0) &&
//        (strcmp($upfile_name,"message_xyz.spx") != 0)
//      )
//    {
//        echo "ERROR - filename $upfile_name rejected by your PHP script.\n";
//    }
    if( $upfile_size > $upfile_size_limit)  {
        echo "ERROR - PHP script says file too large, $upfile_size > $upfile_size_limit\n";
    } else {
      // Move file from temporary server directory to public local directory.
      $fromFile = $_FILES['userfile']['tmp_name'];
      $toFile = $CFG->dataroot . "/" . $uploads_dir . "/" . $upfile_name;
      $moveResult = move_uploaded_file( $fromFile, $toFile );
      if( $moveResult ) {
          echo "SUCCESS - $upfile_name uploaded.\n";

          if (file_exists('/usr/bin/ffmpeg')) {
          $exec_string = '/usr/bin/ffmpeg -i '.$toFile.' '.$toFile.'.mp3';
          exec($exec_string);
          // delete WAV file if we converted to MP3
          //unlink($toFile);
          exec('rm -rf '.$toFile); // Linux specific!
          }
        } else {
          echo "ERROR - move_uploaded_file( $fromFile, $toFile ) failed! See Java Console.\n";
        }
    }

?>