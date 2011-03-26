<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 3/26/11 Time: 12:17 PM
 *
 * Description:
 *  copy an image file from the moodledata/1/icongalleries public folder into the user's course
 */
 
  require_once("../../../../../config.php");

  $courseid = optional_param('courseid', SITEID, PARAM_INT);
  $filename = optional_param('filename', SITEID, PARAM_TEXT); // including base folder

  // Create folders
  $ok = mkdir("$CFG->dataroot/$courseid/icongalleries");
  $ok &= mkdir(dirname("$CFG->dataroot/$courseid/icongalleries/$filename"));
  // Copy File
  if (!copy("$CFG->dataroot/1/icongalleries/$filename", "$CFG->dataroot/$courseid/icongalleries/$filename")) {
      echo "failed to copy $filename...\n";
  }

?>