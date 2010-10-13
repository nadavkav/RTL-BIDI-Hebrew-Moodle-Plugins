<?php // $Id: loadmeeting.php,v 1.1.2.3 2009/03/19 20:26:50 jfilip Exp $


/**
 * Elluminate Live! meeting load script.
 *
 * @version $Id: loadmeeting.php,v 1.1.2.3 2009/03/19 20:26:50 jfilip Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';

    $id = required_param('id', PARAM_INT);


    if (!$elluminate = get_record('elluminate', 'id', $id)) {
        error('Could not get meeting (' . $id . ')');
    }

    if (!$course = get_record('course', 'id', $elluminate->course)) {
        error('Invalid course.');
    }

	if($elluminate->groupmode == 0 && $elluminate->groupparentid == 0) {
	    if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->id, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else if ($elluminate->groupmode != 0 && $elluminate->groupparentid != 0){
		if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->groupparentid, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else if ($elluminate->groupmode != 0 && $elluminate->groupparentid == 0){
	    if (! $cm = get_coursemodule_from_instance('elluminate', $elluminate->id, $course->id)) {
	        error('Course Module ID was incorrect');
	    }
	} else {
		error('Elluminate Live! Group Error');
	}

    
    /// Check to see if groups are being used here
    $groupmode    = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);

    if (empty($currentgroup)) {
        $currentgroup = 0;
    }    
    
    $elluminate->cmidnumber = $cm->id;

/// Some capability checks.
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/elluminate:view', $context);
    if (!$cm->visible){
        require_capability('moodle/course:viewhiddenactivities', $context);
    }
    
    /// Determine level of access to this meeting.
    $ismoderator = $USER->id == $elluminate->creator || has_capability('mod/elluminate:moderatemeeting', $context, $USER->id, false);

    $isparticipant = has_capability('mod/elluminate:joinmeeting', $context, $USER->id, false);

	if ($elluminate->sessiontype == 1 && !$ismoderator && !$isparticipant) {
        error('You must be invited to this meeting.');
    }	

	/// Do we need to assign a grade for this meeting?
    if (($elluminate->grade !== 0) && !$ismoderator) {
    /// Get the grade value for this meeting (either scale or numerical value).
        if ($elluminate->grade < 0) {
            $grades = make_grades_menu($elluminate->grade);
            $ugrade = key($grades);
        } else {
            $ugrade = $elluminate->grade;
        }

        if (!$grade = get_record('elluminate_attendance', 'elluminateid', $elluminate->id,
                                 'userid', $USER->id)) {

            $grade = new stdClass;
            $grade->elluminateid 	 = $elluminate->id;
            $grade->userid           = $USER->id;
            $grade->grade            = $ugrade;
            $grade->timemodified     = time();

            insert_record('elluminate_attendance', $grade);
            elluminate_update_grades($elluminate, $USER->id);
        } else {
            $grade->attended = $ugrade;

            update_record('elluminate_attendance', $grade);
            elluminate_update_grades($elluminate, $USER->id);
        }
    }

    if (!empty($cm)) {
        $cmid = $cm->id;
    } else {
        $cmid = 0;
    }

    add_to_log($elluminate->course, 'elluminate', 'view meeting', 'loadmeeting.php?id=' .
               $elluminate->id, $elluminate->id, $cmid, $USER->id);

	if(empty($elluminate->meetingid)) {
		elluminate_group_instance_check($elluminate, $cm->id);
	}
    
    $modinsession = false; 
    if($ismoderator) {    	
    	$modinsession = elluminate_is_participant($elluminate->id, $USER->id, true);
    }
    
	/// Load the meeting.
	if(!empty($elluminate->meetingid)) {
	    if (!elluminate_build_meeting_jnlp($elluminate->meetingid, $USER->id, $elluminate->private, $modinsession)) {
	        error('Could not launch Elluminate Live! meeting.');
	    }
	} else {
		error('Could not launch Elluminate Live! meeting.  Error in initialization.');
	}


?>
