<?php // $Id: loadrecording.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $

/**
 * Elluminate Live! recording load script.
 * 
 * @version $Id: loadrecording.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';



    $id = required_param('id', PARAM_INT);


    if (!$recording = get_record('elluminate_recordings', 'id', $id)) {
        error('Could not get recording (' . $id . ')');
    }

	/*
    if (!$meeting = get_record('elluminate_session', 'meetingid', $recording->meetingid)) {
        error('Could not get meeting (' . $recording->meetingid . ')');
    }
    */

    if (!$elluminate = get_record('elluminate', 'meetingid', $recording->meetingid)) {
        error('Could not load activity record.');
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

    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/elluminate:viewrecordings', $context);


	/*
    if (!$elmuser = get_record('elluminate_users', 'userid', $USER->id)) {
    /// If this is a public meeting and the user is a member of this course,
    /// they can join the meeting.
        if (empty($elluminate->private) && has_capability('moodle/course:view', $context)) {
            if (!elluminate_new_user($USER->id, random_string(10))) {
               error('Could not create new Elluminate Live! user account!');
            }
            
            $elmuser = get_record('elluminate_users', 'userid', $USER->id);
            
            if (!elluminate_add_participant($meeting->meetingid, $elmuser->elm_id)) {
                error('Could not add you as a participant to this meeting.');
            }
        } else {
            error('You must have an Elluminate Live! user account to access this resource.');
        }
    }
	
	
    if (!elluminate_is_participant($meeting->meetingid, $elmuser->elm_id, true) &&
        !elluminate_is_participant($meeting->meetingid, $elmuser->elm_id)) {
        if ($elluminate->private) {
            error('You must be a participant of the given meeting to access this resource.');
        } else if  (has_capability('moodle/course:view', $context)) {
            if (!elluminate_add_participant($meeting->meetingid, $elmuser->elm_id)) {
                error('Could not add you as a participant to this meeting.');
            }
        }
    }
	*/
    if (!empty($cm)) {
        $cmid = $cm->id;
    } else {
        $cmid = 0;
    }

    add_to_log($elluminate->course, 'elluminate', 'view recording', 'loadrecording.php?id=' .
               $recording->id, $elluminate->id, $cmid, $USER->id);

/// Load the recording.
    if (!elluminate_build_recording_jnlp($recording->recordingid, $USER->id)) {
        error('Could not load Elluminate Live! recording');
    }

?>
