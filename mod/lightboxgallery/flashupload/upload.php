<?php

    require('../../../config.php');
    require($CFG->libdir.'/filelib.php');

    $id = optional_param('id', PARAM_INT);
    $userid = optional_param('user', PARAM_INT);
    $subdir    = optional_param('sub', '', PARAM_PATH);

	if (! $course = get_record("course", "id", $id) ) {
	    error("That's an invalid course id");
    }
	if (! $basedir = make_upload_directory("$course->id")) {
        error("The site administrator needs to fix the file permissions");
    }

	$upload_path = $basedir.$subdir."/";
    echo $upload_path ;

	// copy uploaded file to the  moodledata-folder
	if (!move_uploaded_file($_FILES["Filedata"]["tmp_name"], $upload_path . clean_filename($_FILES["Filedata"]["name"]))) {
		//header("HTTP/1.0 500 Internal Server Error");
		echo "There was a problem with the upload";
		exit(0);
	} else {
	    add_to_log($course->id, 'upload', 'upload', '/files/index.php?id='.$course->id."&wdir=".$subdir, $upload_path . $_FILES["Filedata"]["name"],0,$userid);
		echo "Flash requires that we output something or it won't fire the uploadSuccess event";
	}

?>