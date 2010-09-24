<?php
    // Sets all unset calculatable marks

    global $CFG;
    require_once("../../../../config.php");
    require_once($CFG->dirroot."/mod/assignment/lib.php");

	// Get course ID and assignment ID
    $id     = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a      = optional_param('a', 0, PARAM_INT);           // Assignment ID
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

	/// Load up the required assignment code
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);
	$assignmentinstance->mass_grade();

    
?>