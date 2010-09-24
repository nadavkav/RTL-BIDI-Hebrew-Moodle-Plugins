<?php
    // For saving "pre-saved" comments provided for teacher during marking

	global $CFG, $USER;
	
    require_once("../../../../config.php");
    require_once($CFG->dirroot."/mod/assignment/lib.php");
	print_header();

	// Get course ID and assignment ID
    $id   = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a    = optional_param('a', 0, PARAM_INT);           // Assignment ID
	$comments = clean_param(htmlspecialchars(optional_param('comments',NULL,PARAM_RAW)),PARAM_CLEAN);
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
    require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

    if(set_field('assignment_peerreview','savedcomments',$comments,'assignment',$assignment->id)) {
        print_heading(get_string('commentssaved','assignment_peerreview'),'center',1);
    }
    else {
        notify(get_string('unabletosavecomments','assignment_peerreview'));
    }
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

	echo '<p align="center"><a href="#null" onclick="window.close();">'.get_string('close','assignment_peerreview').'</a></p>';
	close_window(1);

	$assignmentinstance->view_footer();
?>