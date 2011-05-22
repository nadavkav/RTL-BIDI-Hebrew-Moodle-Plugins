<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 2/17/11 Time: 3:07 PM
 *
 * Description:
 *
 */
  require_once("../../../../../config.php");
  global $CFG,$COURSE,$USER;

  function __autoload($class_name) {
    require_once ("lib/php/$class_name.php");
  }
  // set this to true if you want to use the log file
  $logIt = false;
  // max nb of MB/file
  $maxSize = 2;
  // where to upload the files
  //$upload_dir  = 'images/dragndrop';

  // save user's files (images) inside the course (it is a safe place, especially... because it gets backed up)
  $uploads_dir = $_GET['courseid']."/users/".$_GET['userid'];
  //$uploads_dir = "{$COURSE->id}/users/{$USER->id}";
  $upload_dir = make_upload_directory($uploads_dir,false);

  // error messages
  $error_message[0] = "Unknown problem with upload.";
  $error_message[1] = "Uploaded file too large.";
  $error_message[2] = "Uploaded file too large.";
  $error_message[3] = "File was only partially uploaded.";
  $error_message[4] = "Choose a file to upload.";

  if ($logIt) {
    $log = new Logging();
    $log->lwrite("upload script started");
  }
  //print( "_FILES: " ); print_r( $_FILES ); print( "\n" );
  //print( "_POST: " ); print_r( $_POST ); print( "\n" );

  function uploadFinished($status, $fileList) {
    $fileListJSON='';
    if (!empty($fileList)) {
      foreach ($fileList as $fileInfo) {
        $fileListJSON .= "[\"".implode('", "', $fileInfo)."\"],";
      }
      $fileListJSON = substr_replace($fileListJSON ,"",-1);
    }
    die ("{\"status\":\"$status\", \"fileList\":[$fileListJSON]}");
  }

  $num_files = count($_FILES);
  $ids = array_keys($_FILES);
  $index=0;
  if ($num_files == 0) {
    if ($logIt) if ($logIt) $log->lwrite("no file to upload");
      uploadFinished ("no file to upload", null);
  }

  foreach ($_FILES as $arrfile) {
    if ($arrfile['size']>($maxSize*1048576)) {
        if ($logIt) $log->lwrite("error #1: max size exceeded");
          uploadFinished ("Sorry, maximum ".$maxSize."MB!", null);
    }
    $filename = $arrfile['name'];
      if ($logIt) $log->lwrite("uploading $filename...");
    $tmpname = $arrfile['tmp_name'];
    $error=$arrfile['error'];
    $upload_file = $upload_dir . "/" . basename($filename);
    $img_file = $CFG->wwwroot."/file.php/{$_GET['courseid']}/users/{$_GET['userid']}/".basename($filename);
    if (!preg_match("/(gif|jpg|jpeg|png)$/i",$filename)) {
        if ($logIt) $log->lwrite("error #2: Failed: the file is not an image");
          uploadFinished ("Failed: the file is not an image", null);
      } else {
          if (is_uploaded_file($tmpname)) {
              if (move_uploaded_file($tmpname, $upload_file)) {
                  //$filenames[]=array($ids[$index++], $upload_file);
		  //echo $img_file;
		  $filenames[]=array($ids[$index++], $img_file );
              } else {
            if ($logIt) $log->lwrite("error #3:".$error_message[$error]);
                uploadFinished ($error_message[$error], null);
              }
          } else {
            uploadFinished ($error_message[$_FILES['user_file']['error'][$i]], null);
          }
      }
      if ($logIt) $log->lwrite("success: ".$num_files." file(s) uploaded");
      uploadFinished ("success", $filenames);
  }

  if ($logIt) $log->lwrite("upload script ended");

?>