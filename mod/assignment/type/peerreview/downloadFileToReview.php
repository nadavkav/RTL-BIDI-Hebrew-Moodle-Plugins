<?php
    // Allows a student to download a peer's submission and records that it has been downloaded

	global $CFG, $USER;

    require_once("../../../../config.php");
    require_once($CFG->dirroot."/mod/assignment/lib.php");

	// Get course ID and assignment ID
    $id   = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a    = optional_param('a', 0, PARAM_INT);           // Assignment ID
    if ($id) {
        if (! $cm = get_coursemodule_from_id('assignment', $id)) {
            error("Course Module ID was incorrect");
        }
    }
	else {
		error("Course module is incorrect");
    }
	if($a) {
        if (! $assignment = get_record("assignment", "id", $a)) {
            error("assignment ID was incorrect");
        }

        if (! $course = get_record("course", "id", $assignment->course)) {
            error("Course is misconfigured");
        }
	}
	else {
		error("Assignment not specified");
	}

	// Check user is logged in and capable of submitting
    require_login($course->id, false, $cm);
    require_capability('mod/assignment:submit', get_context_instance(CONTEXT_MODULE, $cm->id));

	/// Load up the required assignment code
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

	// Determine which file to send
	if($reviewsToDownload = get_records_select('assignment_review','assignment=\''.$a.'\' and reviewer=\''.$USER->id.'\'ORDER BY id ASC')) {
        $reviewsToDownload = array_values($reviewsToDownload);
        while(count($reviewsToDownload)>0 && $reviewsToDownload[0]->complete==1) {
            array_shift($reviewsToDownload);
        }
        if(count($reviewsToDownload)!=0) {
        
            // Set the file status to downloaded
            set_field('assignment_review','downloaded','1','id',$reviewsToDownload[0]->id);
            set_field('assignment_review','timemodified',time(),'id',$reviewsToDownload[0]->id);

            // Send the file, force download
            require_once($CFG->libdir.'/filelib.php');
            $filearea = $CFG->dataroot.'/'.$assignmentinstance->file_area_name($reviewsToDownload[0]->reviewee);
            $files = get_directory_list($filearea, '', false);
            send_file($filearea.'/'.$files[0], assignment_peerreview::FILE_PREFIX.(2-count($reviewsToDownload)+1).'.'.$assignmentinstance->assignment->fileextension,60,0,false,true);

        }
        else {
            error(get_string('reviewscomplete','assignment_peerreview'));
        }
    }
    else {
        error(get_string('reviewsnotallocated','assignment_peerreview'));
    }
?>