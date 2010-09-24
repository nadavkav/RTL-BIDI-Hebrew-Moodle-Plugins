<?php
    // Allows teachers to create criteria

    require_once("../../../../config.php");
    require_once($CFG->dirroot."/mod/assignment/lib.php");

	// Get course ID and assignment ID
    $id   = optional_param('id', 0, PARAM_INT);          // Course module ID
    $a    = optional_param('a', 0, PARAM_INT);           // Assignment ID
    if ($id) {
        if (! $cm = get_coursemodule_from_id('assignment', $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $assignment = get_record("assignment", "id", $cm->instance)) {
            error("assignment ID was incorrect");
        }

        if (! $course = get_record("course", "id", $assignment->course)) {
            error("Course is misconfigured");
        }
    } else {
        if (!$assignment = get_record("assignment", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $assignment->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

	// Check user is logged in and capable of grading
    require_login($course->id, false, $cm);
    require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

	/// Load up the required assignment code
    require('assignment.class.php');
    $assignmentclass = 'assignment_peerreview';
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

	// Header
	$navigation = build_navigation(get_string('criteria', 'assignment_peerreview'), $assignmentinstance->cm);
	print_header_simple(format_string($assignmentinstance->assignment->name,true), "", $navigation,
                '', '', true, update_module_button($cm->id, $course->id, $assignmentinstance->strassignment), navmenu($course, $cm));

	// Get form description and create form
    require_once("criteria_form.php");
	$mform = new assignment_peerreview_criteria_form(null,array('moduleID'=>$cm->id,'assignmentID'=>$a,'grade'=>$assignmentinstance->assignment->grade,'reward'=>$assignmentinstance->assignment->var1));	
	
	// Redirect if form was cancelled
	if ($mform->is_cancelled()){
		redirect('../../view.php?id='.$cm->id,get_string('updatecancelled', 'assignment_peerreview'),1);
	}
	
	// Gather and store gathered data
	else if ($fromform=$mform->get_data()) {
		
		// Translate form into database record object
		for($i=0; $i<$fromform->option_repeats; $i++) {
			if($fromform->criterionDescription[$i] != '') {
				$criterion = new Object;
				$criterion->assignment = $a;
				$criterion->ordernumber = $i;
				$criterion->textshownwithinstructions = $fromform->criterionDescription[$i];
				$criterion->textshownatreview = $fromform->criterionReview[$i];
				$criterion->value = $fromform->value[$i];
				
				// Insert/Update record in database
				if($existingRecord = get_record('assignment_criteria','assignment',$a,'ordernumber',$i)) {
					$criterion->id = $existingRecord->id;
					update_record('assignment_criteria',$criterion);
				}
				else {
					insert_record('assignment_criteria',$criterion);
				}
			}
		}

		redirect($CFG->wwwroot.'/mod/assignment/view.php?id='.$cm->id.'#criteria', get_string('criteriaupdated', 'assignment_peerreview'), 3);
	}
	
	// Show form (possibly new form, update form or invalid data)
	else {
        // Print tabs
        $tabs = array();
        $row  = array();
        $inactive = array();
        $activated = array();
        $row[] = new tabobject('criteria', "", get_string('criteria', 'assignment_peerreview'));
        $row[] = new tabobject('submissions', "../../submissions.php?id=".$cm->id, get_string('submissions', 'assignment_peerreview'));
        $tabs[] = $row;
        $currenttab = 'criteria';
        $inactive[] = 'criteria';
        $activated[] = 'criteria';
        print_tabs($tabs, $currenttab, $inactive, $activated);
	
		print_heading(get_string('criteria', 'assignment_peerreview'));
        if(optional_param('updated',false,PARAM_BOOL)) {
            notify(get_string('criteriaupdated','assignment_peerreview'),'notifysuccess');
        }
            
		if(record_exists('assignment_review','assignment',$assignmentinstance->assignment->id,'complete','1')) {
			echo '<div style="color:#ff6600;background:#ffff00;margin:5px 20px;padding:5px;text-align:center;font-weight:bold;">'.get_string('criteriachangewarning','assignment_peerreview').'</div>';
		}

			// Get criteria from database
		if($criteriaList = get_records_list('assignment_criteria','assignment',$a,'ordernumber')) {
		
			// Fill form with data
			$toform = new Object;
			$toform->criterionDescription = array();
			$toform->criterionReview = array();
			$toform->value = array();
			foreach($criteriaList as $i=>$criterion) {
				$toform->criterionDescription[] = $criterion->textshownwithinstructions;
				$toform->criterionReview[] = $criterion->textshownatreview;
				$toform->value[] = (int)($criterion->value);
			}
			$mform->set_data($toform);
			
		}
        else {
            echo notify(get_string('mustentercriteria','assignment_peerreview'));
        }
		
		// Help for criteria
		echo '<div style="text-align:center;">';
		helpbutton('criteriawriting', get_string('criteriawriting','assignment_peerreview'), 'assignment/type/peerreview', true,true);
		echo '</div>';

		
		// Show form
		$mform->display(); 
	}
	
    print_footer($assignmentinstance->course);
?>
