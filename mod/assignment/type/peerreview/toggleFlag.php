<?php
    // Toggles a flag on a peer review 

	global $CFG, $USER;

    require_once("../../../../config.php");
    require_once($CFG->dirroot."/mod/assignment/lib.php");
    print_header();

	// Get course ID and assignment ID
    $id   = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a    = optional_param('a', 0, PARAM_INT);           // Assignment ID
    $r    = optional_param('r', 0, PARAM_INT);           // Assignment ID
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
	if($r) {
		if(! $review = get_record("assignment_review", "id", $r, "assignment", $assignment->id)) {
			error("Review is incorrect");
		}
	}
	else {
		error("Review not specified");
	}

	// Check user is logged in and capable of submitting
    require_login($course->id, false, $cm);
    require_capability('mod/assignment:submit', get_context_instance(CONTEXT_MODULE, $cm->id));

	/// Toggle the field
	set_field('assignment_review','flagged',($review->flagged==1?'0':'1'),'id',$review->id);
	
	// Report and close
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

	redirect('../../view.php?id='.$cm->id, get_string('review'.($review->flagged==1?'un':'').'flagged','assignment_peerreview'),1);
	$assignmentinstance->view_footer();

?>