<?php
	global $SESSION, $CFG, $USER;
    require_once("../../../../config.php");
	require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot."/mod/assignment/lib.php");
	print_header();
	
	// Get course ID and assignment ID
    $id     = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a      = optional_param('a', 0, PARAM_INT);           // Assignment ID
	$userid = required_param('userid');
	$grade  = required_param('mark');
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
	$context = get_context_instance(CONTEXT_MODULE,$cm->id);

	// Check user is logged in and capable of submitting
    require_login($course->id, false, $cm);
    require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

	/// Load up the required assignment code
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

	$grading_info = grade_get_grades($course->id, 'mod', 'assignment', $assignment->id, $userid);

	if (!$grading_info->items[0]->grades[$userid]->locked and
		!$grading_info->items[0]->grades[$userid]->overridden) {

		$assignmentinstance->set_grade($userid,$grade);
		
		echo '<script>';
        if (empty($SESSION->flextable['mod-assignment-submissions']->collapse['finalgrade'])) {
			echo 'opener.document.getElementById("g'.$userid.'").innerHTML="'.
			$assignmentinstance->display_grade($grade)."\";";
        }		
		echo '</script>';
		echo '<div align="center">';
		print_heading(get_string('gradeset','assignment_peerreview'),1);
	}
	else {
		print_heading(get_string('unsabletoset','assignment_peerreview'),1);
	}
	echo '<p><a href="#null" onclick="window.close();">'.get_string('close','assignment_peerreview').'</a></p>';
	echo '</div>';
	close_window(1);
	$assignmentinstance->view_footer();
?>