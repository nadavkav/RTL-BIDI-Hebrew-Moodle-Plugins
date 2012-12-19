<?php

    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    require_course_login($id);
    @header('Content-Type: text/html; charset=utf-8');

    $filename = 'audiorecorder_'.strftime("%H%M%S",time()).'.wav';
    //echo $filename;
    $uploads_dir = $COURSE->id."/users/".$USER->id;
    // create a folder for the audio files, if none exist.
    $path = make_upload_directory($uploads_dir,false);

?>
<div style="margin: 5px auto;display: table;"><iframe src="audio_applet_iframe.php?courseid=<?php echo $COURSE->id ?>&userid=<?php echo $USER->id ?>" height="300" width="480"></iframe></div>
