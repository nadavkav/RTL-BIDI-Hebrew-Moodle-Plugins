<?php


// $Id: lib.php,v 1.1.2.4 2009/05/19 14:34:54 jfilip Exp $

/**
 * Elluminate Live! Module
 *
 * Allows Elluminate Live! meetings to be created and managed on an
 * Elluminate Live! server via a Moodle activity module.
 *
 * @version $Id: lib.php,v 1.1.2.4 2009/05/19 14:34:54 jfilip Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */

/**
 * Elluminate Live! role types.
 */
define('ELLUMINATELIVE_ROLE_SERVER_ADIMINISTRATOR', 0);
define('ELLUMINATELIVE_ROLE_APPADMIN', 1);
define('ELLUMINATELIVE_ROLE_MODERATOR', 2);
define('ELLUMINATELIVE_ROLE_PARTICIPANT', 3);

/**
 * Elluminate Live! boundary time values (in minutes).
 */
$elluminate_boundary_times = array (
	-1 => get_string('choose'),
	0 => '0',
	15 => '15',
	30 => '30',
	45 => '45',	
	60 => '60'
);

/**
 * Elluminate Live! boundary time default value.
 */
define('ELLUMINATELIVE_BOUNDARY_DEFAULT', 15);

/**
 * Elluminate Live! seat reservation enabled string.
 */
define('ELLUMINATELIVE_SEAT_RESERVATION_ENABLED', 'preferred');

/**
 * Elluminate Live! recording values.
 */
//define('ELLUMINATELIVE_RECORDING_NONE',      0);
//define('ELLUMINATELIVE_RECORDING_MANUAL',    1);
//define('ELLUMINATELIVE_RECORDING_AUTOMATIC', 3);

define('ELLUMINATELIVE_RECORDING_NONE_NAME', 'off');
define('ELLUMINATELIVE_RECORDING_MANUAL_NAME', 'remote');
define('ELLUMINATELIVE_RECORDING_AUTOMATIC_NAME', 'on');

/**
 * Elluminate Live! recording Type
 */
define('ELLUMINATELIVE_RECORDING_MANUAL', 1);
define('ELLUMINATELIVE_RECORDING_AUTOMATIC', 2);
define('ELLUMINATELIVE_RECORDING_NONE', 3);

/**
 * The Elluminate Live! XML namespace.
 */
define('ELLUMINATELIVE_XMLNS', 'http://www.soapware.org/');

/**
 * How many times should we attempt to create an Elluminate Live! user account by adding an
 * increasing integer on the end of a user name?
 */
define('ELLUMINATELIVE_CREATE_USER_TRIES', 20);

/**
 * The amount of time after which we consider a meeting creation attempt to have failed.
 */
define('ELLUMINATELIVE_SYNC_TIMEOUT', MINSECS * 10);

/**
 * Define the content types for preload files.
 */
define('ELLUMINATELIVE_PRELOAD_WHITEBOARD', 'whiteboard');
define('ELLUMINATELIVE_PRELOAD_MEDIA', 'media');

/**
 * Set to true so that any reminded added will have its time value set to trigger a message sent
 * at the next cron run.
 */
define('REMINDER_DEBUG', false);

/**
 * Reminder types
 */
define('REMINDER_TYPE_DELTA', 0);
define('REMINDER_TYPE_INTERVAL', 1);

/**
 * When adding a new event, we can't calculate the number of available days so
 * we just give a fairly large number of days to choose from.  The actual
 * value can be edited with the event later and then only an appropriate number
 * of days will be avaiable for chosing.
 */
//define('REMINDER_DELTA_DEFAULT', 86400); // 1 day (24 * 60 * 60)
define('REMINDER_DELTA_DEFAULT', DAYSECS);

function elluminate_install() {
	$result = true;
	$timenow = time();
	$sysctx = get_context_instance(CONTEXT_SYSTEM);

	$adminrid = get_field('role', 'id', 'shortname', 'admin');
	$coursecreatorrid = get_field('role', 'id', 'shortname', 'coursecreator');
	$editingteacherrid = get_field('role', 'id', 'shortname', 'editingteacher');
	$teacherrid = get_field('role', 'id', 'shortname', 'teacher');

	/// Fully setup the Elluminate Moderator role.
	if ($result && !$mrole = get_record('role', 'shortname', 'elluminatemoderator')) {
		if ($rid = create_role(get_string('elluminatemoderator', 'elluminate'), 'elluminatemoderator', get_string('elluminatemoderatordescription', 'elluminate'))) {

			$mrole = get_record('role', 'id', $rid);
			$result = $result && assign_capability('mod/elluminate:moderatemeeting', CAP_ALLOW, $mrole->id, $sysctx->id);
		} else {
			$result = false;
		}
	}

	if (!count_records('role_allow_assign', 'allowassign', $mrole->id)) {
		$result = $result && allow_assign($adminrid, $mrole->id);
		$result = $result && allow_assign($coursecreatorrid, $mrole->id);
		$result = $result && allow_assign($editingteacherrid, $mrole->id);
		$result = $result && allow_assign($teacherrid, $mrole->id);
	}

	/// Fully setup the Elluminate Participant role.
	if ($result && !$prole = get_record('role', 'shortname', 'elluminateparticipant')) {
		if ($rid = create_role(get_string('elluminateparticipant', 'elluminate'), 'elluminateparticipant', get_string('elluminateparticipantdescription', 'elluminate'))) {

			$prole = get_record('role', 'id', $rid);
			$result = $result && assign_capability('mod/elluminate:joinmeeting', CAP_ALLOW, $prole->id, $sysctx->id);
		} else {
			$result = false;
		}
	}

	if (!count_records('role_allow_assign', 'allowassign', $prole->id)) {
		$result = $result && allow_assign($adminrid, $prole->id);
		$result = $result && allow_assign($coursecreatorrid, $prole->id);
		$result = $result && allow_assign($editingteacherrid, $prole->id);
		$result = $result && allow_assign($teacherrid, $prole->id);
	}

	return $result;
}

function elluminate_add_instance($elluminate, $facilitatorid = false) {
	global $CFG;
	global $USER;	
	global $COURSE;

	if (!$facilitatorid) {
		$facilitatorid = $USER->id;
	}
	/// The start and end times don't make sense.
	if ($elluminate->timestart > $elluminate->timeend) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);

		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('invalidsessiontimes', 'elluminate', $a), 5);
	}
	if($elluminate->timestart == $elluminate->timeend) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);

		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('samesessiontimes', 'elluminate', $a), 5);		
	}
	$timenow = time();	
	if($elluminate->timestart < $timenow) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);

		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('starttimebeforenow', 'elluminate', $a), 5);		
	}
	
	$yearinseconds = 31536000;
	$timedif = $elluminate->timeend - $elluminate->timestart;		
	if($timedif > $yearinseconds) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);
		
		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('meetinglessthanyear', 'elluminate', $a), 5);
	}
	
	$year_later = $timenow + $yearinseconds;
	if($elluminate->timestart > $year_later) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);
		
		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('meetingstartoverayear', 'elluminate', $a), 5);	
	}		
	
	if (empty ($elluminate->sessionname)) {
		$elluminate->sessionname = $elluminate->name;
	}
	
	$originalname = $elluminate->sessionname;	
	$search = array("<",">","&","#","%","\"","\\","|");	
	$replace = '';	
	$strippedname = str_replace($search, $replace, $elluminate->sessionname);
	
	$databasesafe = array("'");
	$replacedb = '\\\'';	
	$strippedname = str_replace($databasesafe, $replacedb, $strippedname);
	$elluminate->description = str_replace($databasesafe, $replacedb, $elluminate->description);
	
	if(empty($strippedname)) {
		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
			$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('meetingnameempty', 'elluminate'), 5);
	} else {		
		if(!ctype_alnum(substr($strippedname, 0, 1))) {
			redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
			$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('meetingnamemustbeginwithalphanumeric', 'elluminate'), 5);
		}			
	}
	
	if (empty ($elluminate->creator)) {
		$elluminate->creator = $USER->id;
	}
	
	if (empty ($elluminate->boundarytimedisplay)) {
		$elluminate->boundarytimedisplay = 0;
	}
	
	if($elluminate->sessiontype == 0 || $elluminate->sessiontype == 1) {
		$elluminate->groupmode = 0;
		$elluminate->groupingid = 0;
	} else if ($elluminate->sessiontype == 2) {
		$elluminate->groupingid = 0;
	}
	
	if($COURSE->groupmodeforce > 0) {
		$elluminate->groupmode = $COURSE->groupmode;		
	}
	
	$elluminate->timemodified = time();
	$elluminate->seats = 0;
	$elluminate->chairlist = $USER->id;
	$elluminate->sessionname = $strippedname;
	$elluminate->groupparentid = '0';	
	$elluminate->meetinginit = 0;	
	$elluminate->name = htmlentities($elluminate->name);	
	if (!$elluminate->id = insert_record('elluminate', $elluminate)) {
		return false;
	}
	
	$mod = new stdClass;	
	$mod->course = $elluminate->course;
	$mod->module = $elluminate->module;
	$mod->instance = $elluminate->id;
	$mod->idnumber = null;
	$mod->added = time();
	$mod->score = 0;
	$mod->indent = 0;
	$mod->visible = 1;
	$mod->visibleold = 1;
	$mod->groupmode = $elluminate->groupmode;		
	$mod->groupingid = $elluminate->groupingid;
	$mod->groupmembersonly = 0;	
	
	$sql = "SELECT cs.* FROM {$CFG->prefix}course_sections cs" .
			" WHERE cs.course = $elluminate->course AND cs.section = $elluminate->section";
	$course_sections = get_records_sql($sql);
	foreach ($course_sections as $course_section) {
		$mod->section = $course_section->id;
	}
	
	if(!($mod->id = add_course_module($mod))) {
		return false;
	}
	
	if($elluminate->sessiontype == 0 || $elluminate->sessiontype == 1) {
		if($elluminate->groupmode == 0) {			
			$create_result = elluminate_create_meeting(							  
									  $elluminate->timestart, 
									  $elluminate->timeend,
									  stripslashes($strippedname),
									  '',
									  '',
									  $elluminate->sessiontype,
									  $elluminate->seats,
									  $elluminate->boundarytime,
									  $elluminate->recordingmode,
									  $mod->id);
			$meetingid = $create_result->DefaultAdapterMeetingResponseShort->meetingId;
			if($meetingid > 0) {
				$elluminate->meetingid = $meetingid;
				if(!empty($elluminate->meetingid)) {
					$elluminate->meetinginit = 2;
				}						
				$elluminate->chairlist = $create_result->DefaultAdapterMeetingResponseShort->chairList;
				update_record('elluminate', $elluminate);
			} else {
				elluminate_delete_instance($elluminate->id);							  	
				error('An error occured while attempting to initialize your session.<br><br> A likely cause is your Moodle server time and the Elluminate scheduling server time is out of sync.<br><br>Please try again in a few minutes.');	
			}									  				
		}
	}	
	
	if(!empty($elluminate->meetingid)) {
		$elluminate->meetinginit = 2;
	}	
	
	$parentsessionid = $elluminate->id;
	elluminate_grade_item_update($elluminate);
	
	if($elluminate->sessiontype == 2 || $elluminate->sessiontype == 3) {
		if($elluminate->groupmode != 0) {
			elluminate_insert_group_records($elluminate);
		}
	}
	
	delete_records('course_modules', 'id', $mod->id);
	
	return $parentsessionid;
}

function elluminate_insert_group_records($elluminate) {
	$parentsessionid = $elluminate->id;
	$elluminate->id = '';
	$elluminate->meetingid = null;
	$elluminate->groupparentid = $parentsessionid;
	$elluminate->meetinginit = 0;
	
	$search = array("<",">","&","#","%","\"","\\","|");	
	$replace = '';
	$databasesafe = array("'");
	$replacedb = '\\\'';	
	
	if($elluminate->groupmode != 0) {
		if($elluminate->sessiontype == 2) {
			$groups = groups_get_all_groups($elluminate->course);
		} else if ($elluminate->sessiontype == 3) {
			$groups = groups_get_all_groups($elluminate->course, 0, $elluminate->groupingid);
		}		
		$name_string = $elluminate->sessionname;
		$desc = $elluminate->description;
		foreach($groups as $group) {					
			$elluminate->groupid = $group->id;
			if($elluminate->customname > 0) {
				if($elluminate->customname == 1) {
					$sessionname = $group->name;
				} else if($elluminate->customname == 2) {
					$sessionname = $name_string . ' - ' . $group->name;	
				}				
				if(strlen($sessionname) > 64) {
					$stringlength = strlen($sessionname);
					$toomany = ($stringlength - 64) + 5;
					$remainder = $toomany % 2;
					$difference = $toomany / 2;
					$elluminate->sessionname = substr($sessionname, 0, 33 - ($difference + $remainder)) . ' ... '
											   . substr($sessionname, 32 + $difference, $stringlength);	
				} else {
					$elluminate->sessionname = $sessionname;
				} 
			}
			if($elluminate->customdescription == 1) {
				$elluminate->description = '';				
				$elluminate->description = $group->name . ' - ' . $desc;
			}			 		
			 		
			$elluminate->sessionname = str_replace($search, $replace, $elluminate->sessionname);	
			$elluminate->sessionname = str_replace($databasesafe, $replacedb, $elluminate->sessionname);
			$elluminate->description = str_replace($search, $replace, $elluminate->description);	
			$elluminate->description = str_replace($databasesafe, $replacedb, $elluminate->description);

			$elluminate->id = insert_record('elluminate', $elluminate);
		}
	}
}

function elluminate_add_group_instance($elluminate, $cmid, $facilitatorid = false) {
	global $CFG;
	global $USER;
	
	if (!$facilitatorid) {
		$facilitatorid = $USER->id;
	}
	$timenow = time();	
	if($elluminate->timestart < $timenow) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);
		$a->boundarytime = userdate($elluminate->boundarytime);
		
		
		$starttime = $elluminate->timestart;
		$boundary = $elluminate->boundarytime * 60;
		
		$new_boundarytime = floor(($timenow - ($starttime - $boundary)) / 60);
		
		$minutes = date('i',$timenow);
		$millis = date('s',$timenow);	
		$addminutes = 15 - ($minutes % 15);
		$new_boundarytime = $new_boundarytime + $addminutes;
		$new_starttime = $timenow + ($addminutes * 60) - $millis;
		$new_endtime = 	$elluminate->timeend + (900);
	} else {
		$new_boundarytime = $elluminate->boundarytime;
		$new_starttime = $elluminate->timestart;
		$new_endtime = $elluminate->timeend;
	}
	
	$search = array("<",">","&","#","%","\"","\\","|");	
	$replace = '';
	$databasesafe = array("'");
	$replacedb = '\\\'';	
	
	$elluminate->name = str_replace($databasesafe, $replacedb, $elluminate->name);	
	$elluminate->sessionname = str_replace($search, $replace, $elluminate->sessionname);	
	$elluminate->sessionname = str_replace($databasesafe, $replacedb, $elluminate->sessionname);
	$elluminate->description = str_replace($search, $replace, $elluminate->description);	
	$elluminate->description = str_replace($databasesafe, $replacedb, $elluminate->description);
	
	$elluminate->timemodified = time();
	$elluminate->seats = 0;
	$elluminate->meetinginit = 1;
	update_record('elluminate', $elluminate);
	
	$create_result = elluminate_create_meeting(
							  $new_starttime, 
							  $new_endtime,
							  $elluminate->sessionname,
							  '',
							  '',
							  $elluminate->private,
							  $elluminate->seats,
							  $new_boundarytime,
							  $elluminate->recordingmode,
							  $cmid, 
							  $elluminate->creator);
	
	$meetingid = $create_result->DefaultAdapterMeetingResponseShort->meetingId;
	if($meetingid > 0) {
		$elluminate->meetingid = $meetingid;
		$elluminate->meetinginit = 2;
		update_record('elluminate', $elluminate);
		return true;
	} else {
		$elluminate->meetinginit = 0;
		update_record('elluminate', $elluminate);
		error('An error occured while attempting to initialize your session.<br><br> A likely cause is your Moodle server time and the Elluminate scheduling server time is out of sync.<br><br>Please try again in a few minutes.');	
	}						  	
}

function elluminate_group_instance_check($elluminate, $cmid) {
	if(empty($elluminate->meetingid)) {
		for($sleepcounter = 0; $sleepcounter < 18; $sleepcounter++) {
			if($elluminate->meetinginit == 0) {
				if(elluminate_add_group_instance($elluminate, $cmid)) {
					$sleepcounter = 18;	
				}
			} else if ($elluminate->meetinginit == 1) {
				// A meeting init of 1 means someone else has already sent the request to the server
				// the second person will have to wait until the original request is back.
				sleep(5);			
			} else if ($elluminate->meetinginit == 2) {
				$sleepcounter = 18;
			}
	
			return get_record('elluminate', 'id', $elluminate->id);
		}	
	}
}
/**
 * 
 * Prints the editing button on a module "view" page
 *
 * @uses $CFG
 * @param    type description
 * @todo Finish documenting this function
 */
function elluminate_update_module_button($moduleid, $courseid, $string) {
    global $CFG, $USER;

    if (has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_MODULE, $moduleid))) {
        $string = get_string('updatethis', '', $string);

        return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/course/mod.php\" onsubmit=\"this.target='{$CFG->framename}'; return true\">".//hack to allow edit on framed resources
               "<div>".
               "<input type=\"hidden\" name=\"update\" value=\"$moduleid\" />".
               "<input type=\"hidden\" name=\"return\" value=\"true\" />".
               "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />".
               "<input type=\"submit\" value=\"$string\" /></div></form>";
    } else {
        return '';
    }
}

function elluminate_update_instance($elluminate) {
	global $CFG;
	global $USER;
	
	$update_id = $elluminate->update;
	$meeting = get_record('elluminate', 'id', $elluminate->instance);	
	$elluminate->creator = $meeting->creator;
	$elluminate->chairlist = $meeting->chairlist;	
	
	/// The start and end times don't make sense.
	if ($elluminate->timestart > $elluminate->timeend) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);
		
		redirect($CFG->wwwroot . '/course/modedit.php?update=' . $elluminate->coursemodule . '&amp;return=1' 
					, get_string('invalidsessiontimes', 'elluminate', $a), 5);
	}
	if($elluminate->timestart == $elluminate->timeend) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);

		redirect($CFG->wwwroot . '/course/modedit.php?update=' . $elluminate->coursemodule . '&amp;return=1' 
					, get_string('samesessiontimes', 'elluminate', $a), 5);		
	}
	$timenow = time();
	
	$yearinseconds = 31536000;
	$timedif = $elluminate->timeend - $elluminate->timestart;		
	if($timedif > $yearinseconds) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);

		redirect($CFG->wwwroot . '/course/modedit.php?update=' . $elluminate->coursemodule . '&amp;return=1' 
				, get_string('meetinglessthanyear', 'elluminate', $a), 5);			
	}
	
	$year_later = $timenow + $yearinseconds;
	if($elluminate->timestart > $year_later) {
		$a = new stdClass;
		$a->timestart = userdate($elluminate->timestart);
		$a->timeend = userdate($elluminate->timeend);
		
		redirect($CFG->wwwroot . '/course/mod.php?id=' . $elluminate->course . '&amp;section=' .
		$elluminate->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=elluminate', get_string('meetingstartoverayear', 'elluminate', $a), 5);	
	}	
	
	
	
	$elluminate->timemodified = time();
	$elluminate->id = $elluminate->instance;

	/// The start and end times don't make sense.
	if ($elluminate->timestart > $elluminate->timeend) {
		/// Get the course module ID for this instance.
		$sql = "SELECT cm.id
						                    FROM {$CFG->prefix}modules m,
						                    {$CFG->prefix}course_modules cm
						                    WHERE m.name = 'elluminate'
						                    AND cm.module = m.id
						                    AND cm.instance = '{$elluminate->id}'";

		if (!$cmid = get_field_sql($sql)) {
			redirect($CFG->wwwroot . '/mod/elluminate/view.php?id=' . $elluminate->id, 'The meeting start time of ' . userdate($elluminate->timestart) .
			' is after the meeting end time of ' . userdate($end), 5);
		}

		redirect($CFG->wwwroot . '/course/mod.php?update=' . $cmid . '&amp;return=true&amp;' .
		'sesskey=' . $USER->sesskey, 'The meeting start time of ' . userdate($elluminate->timestart) .
		' is after the meeting end time of ' . userdate($end), 5);
	}

	/// If the grade value for attendance has changed, modify any existing attendance records.
	if ($elluminate->grade != $meeting->grade) {
		if ($attendance = get_records('elluminate_attendance', 'elluminateid', $meeting->id)) {
			foreach ($attendance as $attendee) {
				if ($attendee->grade > 0) {
					/// We're using a scale.
					if ($elluminate->grade < 0) {
						$grades = make_grades_menu($elluminate->grade);
						$attendee->grade = key($grades);

						/// We're using a numerical value.
					} else {
						$attendee->grade = $elluminate->grade;
					}

					update_record('elluminate_attendance', $attendee);
					elluminate_update_grades($elluminate, $attendee->userid);
				}
			}
		}
	}

	if (empty ($elluminate->boundarytimedisplay)) {
		$elluminate->boundarytimedisplay = 0;
	}
	
	$elluminate->seats = 0;
	//$elluminate->sessionname = $elluminate->name;

	$search = array("<",">","&","#","%","\"","\\","|");	
	$replace = '';
	$databasesafe = array("'");
	$replacedb = '\\\'';		

	$elluminate->sessionname = str_replace($search, $replace, $elluminate->sessionname);	
	$elluminate->sessionname = str_replace($databasesafe, $replacedb, $elluminate->sessionname);	
	$elluminate->description = addslashes($elluminate->description); 		
	
	if(empty($elluminate->sessionname)) {
		redirect($CFG->wwwroot . '/course/modedit.php?update=' . $update_id . '&amp;return=1', get_string('meetingnameempty', 'elluminate'), 5);
	} else {		
		if(!ctype_alnum(substr($elluminate->sessionname, 0, 1))) {			
			redirect($CFG->wwwroot . '/course/modedit.php?update=' . $update_id . '&amp;return=1', get_string('meetingnamemustbeginwithalphanumeric', 'elluminate'), 5);
		}			
	}	

	if(!empty($meeting->meetingid)) {
		if(!$create_result = elluminate_update_meeting(
								  $meeting->meetingid,
								  $elluminate->timestart, 
								  $elluminate->timeend,
								  $elluminate->sessionname,
								  '',
								  '',
								  $elluminate->private,
								  $elluminate->seats,
								  $elluminate->boundarytime,
								  $elluminate->recordingmode,
								  '',
								  $elluminate->id)) {	
			return false;				  	
		}
	}

	$elluminate->name = htmlentities($elluminate->name);	
	update_record('elluminate', $elluminate);
	elluminate_grade_item_update($elluminate);

	elluminate_check_for_new_groups($elluminate);
	if($elluminate->sessiontype == '2' || $elluminate->sessiontype == '3') {
		if($elluminate->groupmode != 0) {
			elluminate_update_group_records($elluminate);
		}
	}

	return true;
}

function elluminate_update_group_records($elluminate) {
	
	$search = array("<",">","&","#","%","\"");
	$replace = '';	
	$originalname = $elluminate->sessionname;
	$strippedname = str_replace($search, $replace, $elluminate->sessionname);
	$parentsessionid = $elluminate->id;
	$elluminate->id = '';
	$elluminate->meetingid = null;
	$elluminate->groupparentid = $parentsessionid;
	$elluminate->meetinginit = 0;
	if($elluminate->groupmode != 0) {
		$sessions = get_records('elluminate', 'groupparentid', $elluminate->instance);
		$desc = $elluminate->description;
		
		foreach($sessions as $session) {
			$group = get_record('groups', 'id', $session->groupid);
			$session->name = $elluminate->name;
			$elluminate->groupid = $group->id;
			$name_string = $elluminate->name;
			if($elluminate->customname > 0) {
				if($elluminate->customname == 1) {
					$name_string = $group->name;
				} else if($elluminate->customname == 2) {
					$name_string = $name_string . ' - ' . $group->name;	
				}				
				if(strlen($name_string) > 64) {
					$stringlength = strlen($name_string);
					$toomany = ($stringlength - 64) + 5;
					$remainder = $toomany % 2;
					$difference = $toomany / 2;
					$session->sessionname = substr($name_string, 0, 33 - ($difference + $remainder)) . ' ... '
											   . substr($name_string, 32 + $difference, $stringlength);	
				} else {
					$session->sessionname = $name_string;
				} 
			} else {
				$session->sessionname = $elluminate->sessionname;;
			}
			
			if($elluminate->customdescription == 1) {
				$session->description = '';				
				$session->description = $group->name . ' - ' . $desc;
				$session->customdescription = 1;
			} else {
				$session->description = $desc;
				$session->customdescription = 0;	
			}		    
			
			$search = array("<",">","&","#","%","\"","\\","|");	
			$replace = '';
			$databasesafe = array("'");
			$replacedb = '\\\'';		
			
			$elluminate->sessionname = str_replace($search, $replace, $elluminate->sessionname);	
			$elluminate->sessionname = str_replace($databasesafe, $replacedb, $elluminate->sessionname);	
			$elluminate->description = addslashes($elluminate->description); 
			
			$session->customname = $elluminate->customname;		   
		    $session->timestart = $elluminate->timestart;
		    $session->timeend = $elluminate->timeend;
		    $session->recordingmode = $elluminate->recordingmode;
		    $session->boundarytime = $elluminate->boundarytime;
		    $session->boundarytimedisplay = $elluminate->boundarytimedisplay;
		    $session->seats = $elluminate->seats;
		    $session->grade = $elluminate->grade;
		    $session->timemodified = $elluminate->timemodified;
			$session->groupmode = $elluminate->groupmode;
			$session->groupingid = $elluminate->groupingid;
			$session->creator = $elluminate->creator;
			$session->chairlist = $elluminate->chairlist;
       		//Update the scheduling server with changes to meetings that have a meeting id already.
       		if(!empty($session->meetingid)) {
	       		if(!$create_result = elluminate_update_meeting(
								  $session->meetingid,
								  $session->timestart, 
								  $session->timeend,
								  $session->name,
								  '',
								  '',
								  $session->sessiontype,
								  $session->seats,
								  $session->boundarytime,
								  $session->recordingmode,
								  '',
								  $session->id)) {
					return false;				  	
				}
       		} 
       		       		
			update_record('elluminate', $session);				
			
			/// If the grade value for attendance has changed, modify any existing attendance records.
			if ($elluminate->grade != $session->grade) {
				if ($attendance = get_records('elluminate_attendance', 'elluminateid', $session->id)) {
					foreach ($attendance as $attendee) {
						if ($attendee->grade > 0) {
							/// We're using a scale.
							if ($elluminate->grade < 0) {
								$grades = make_grades_menu($elluminate->grade);
								$attendee->grade = key($grades);
		
								/// We're using a numerical value.
							} else {
								$attendee->grade = $elluminate->grade;
							}
		
							update_record('elluminate_attendance', $attendee);
							elluminate_update_grades($session, $attendee->userid);
						}
					}
				}
			}
					
		}
	}
}


function elluminate_update_seats($elluminate, $seats) {
	global $CFG;
	global $USER;
	
	$meeting = get_record('elluminate', 'id', $elluminate->id);
	$elluminate->timemodified = time();
	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meeting->meetingid;	
	$args[0]['type'] = 'xsd:integer';

	$args[1]['name'] = 'reserveSeats';
	$args[1]['value'] = $seats;	
	$args[1]['type'] = 'xsd:integer';

	$result = elluminate_send_command('setMeeting', $args);
		
	update_record('elluminate', $elluminate);

	return true;
}



function elluminate_delete_instance($id) {
	/// Given an ID of an instance of this module,
	/// this function will permanently delete the instance
	/// and any data that depends on it.
	global $USER;
	
	if (!$elluminate = get_record('elluminate', 'id', $id)) {
		return false;
	}
	
	if(!empty($elluminate)) {
        $participant = false;
        if ($elluminate->private) {
	    	//Checks to see if the user is a participant in the private meeting
	        if(elluminate_is_participant_in_meeting($elluminate, $USER->id)) {
	        	//then checks to make sure that the user role has the privilege to join a meeting
	        	$participant = true;
	        }
	    } else {
	    	$participant = true;
	    } 
               
		if($participant == false) {
			error('You need to be invited to this private session in order to delete it.');
		}
    } 
	
	
			
		if($elluminate->sessiontype == 2 || $elluminate->sessiontype == 3) {
			elluminate_check_for_orphaned_group_records($elluminate);
		} else {			
			if(!empty($elluminate->meetingid)) {
				$group_recordings = get_records('elluminate_recordings', 'meetingid', $elluminate->meetingid);
				foreach($group_recordings as $group_recording) {
					elluminate_delete_recording($group_recording->recordingid);
				}		
				elluminate_delete_meeting($elluminate->meetingid);		
			}
		}		
	
	delete_records('elluminate_recordings', 'meetingid', $meeting->meetingid);
	delete_records('elluminate_attendance', 'elluminateid', $elluminate->id);
	elluminate_grade_item_delete($elluminate);
	delete_records('elluminate', 'id', $elluminate->id);	
	return true;
}

function elluminate_check_for_orphaned_group_records($elluminate) {
	$group_meetings = get_records('elluminate', 'groupparentid', $elluminate->id);
	foreach($group_meetings as $group_meeting) {
		
		delete_records('elluminate_attendance', 'elluminateid', $group_meeting->id);	
				
		elluminate_grade_item_delete($group_meeting);						
					
		if(!empty($group_meeting->meetingid)) {
			elluminate_delete_meeting($group_meeting->meetingid);
		}
		
		if(!empty($group_meeting->meetingid)) {
			$group_recordings = get_records('elluminate_recordings', 'meetingid', $group_meeting->meetingid);
			foreach($group_recordings as $group_recording) {
				elluminate_delete_recording($group_recording->recordingid);
			}
			delete_records('elluminate_recordings', 'meetingid', $group_meeting->meetingid);	
		}
		
		
		delete_records('elluminate', 'id', $group_meeting->id);
	}
	
	if(!empty($elluminate->meetingid)) {
		$group_recordings = get_records('elluminate_recordings', 'meetingid', $elluminate->meetingid);
		foreach($group_recordings as $group_recording) {
			elluminate_delete_recording($group_recording->recordingid);
		}		
		elluminate_delete_meeting($elluminate->meetingid);		
	}	
}

function display_grade($grade, $elluminate) {

	static $scalegrades = array (); // Cache scales for each assignment - they might have different scales!!

	if ($elluminate->grade >= 0) { // Normal number
		if ($grade == -1) {
			return '-';
		} else {
			return $grade . ' / ' . $elluminate->grade;
		}

	} else { // Scale
		if (empty ($scalegrades[$elluminate->id])) {
			if ($scale = get_record('scale', 'id', - ($elluminate->grade))) {
				$scalegrades[$elluminate->id] = make_menu_from_list($scale->scale);
			} else {
				return '-';
			}
		}
		if (isset ($scalegrades[$elluminate->id][$grade])) {
			return $scalegrades[$elluminate->id][$grade];
		}
		return '-';
	}
}

function elluminate_user_outline($course, $user, $mod, $elluminate) {
	/// Return a small object with summary information about what a
	/// user has done with a given particular instance of this module
	/// Used for user activity reports.
	/// $return->time = the time they did it
	/// $return->info = a short text description
	if ($attendance = get_record('elluminate_attendance', 'userid', $user->id, 'elluminateid', $elluminate->id)) {
		$result = new stdClass;

		$result->info = get_string('grade') . ': ' . display_grade($attendance->grade, $elluminate);
		$result->time = $attendance->timemodified;

		return $result;
	}
	return NULL;
}

function elluminate_user_complete($course, $user, $mod, $elluminate) {
	/// Print a detailed representation of what a  user has done with
	/// a given particular instance of this module, for user activity reports.
	if ($attendance = get_record('elluminate_attendance', 'userid', $user->id, 'elluminateid', $elluminate->id)) {
		print_simple_box_start();
		echo get_string('attended', 'elluminate') . ': ';
		echo userdate($attendance->timemodified);

		print_simple_box_end();

	} else {
		print_string('notattendedyet', 'elluminate');
	}
}

function elluminate_print_recent_activity($course, $isteacher, $timestart) {
	/// Given a course and a time, this module should find recent activity
	/// that has occurred in elluminate activities and print it out.
	/// Return true if there was output, or false is there was none.

	global $CFG;

	$content = false;
	$meetings = NULL;

	$select = "time > $timestart AND course = {$course->id} AND " .
	"module = 'elluminate' AND action = 'view.meeting'";

	if (!$logs = get_records_select('log', $select, 'time ASC')) {
		return false;
	}

	foreach ($logs as $log) {
		//Create a temp valid module structure (course,id)
		$tempmod = new stdClass;
		$tempmod->course = $log->course;
		$tempmod->id = $log->info;
		//Obtain the visible property from the instance
		$modvisible = instance_is_visible($log->module, $tempmod);

		//Only if the mod is visible
		if ($modvisible) {
			$sql = "SELECT e.name, u.firstname, u.lastname
											                        FROM {$CFG->prefix}elluminate e,
											                        {$CFG->prefix}user u
											                        WHERE e.id = '{$log->info}'
											                        AND u.id = {$log->userid}";

			$meetings[$log->info] = get_record_sql($sql);
			$meetings[$log->info]->time = $log->time;
			$meetings[$log->info]->url = str_replace('&', '&amp;', $log->url);
		}
	}

	if ($meetings) {
		print_headline(get_string('newsubmissions', 'assignment') . ':');
		foreach ($meetings as $meeting) {
			print_recent_activity_note($meeting->time, $meeting, $isteacher, stripslashes($meeting->name), $CFG->wwwroot .
			'/mod/elluminate/' . $meeting->url);
		}
		$content = true;
	}

	return $content;
}

function elluminate_cron() {
	/// Function to be run periodically according to the moodle cron
	/// This function searches for things that need to be done, such
	/// as sending out mail, toggling flags etc ...

	global $CFG;
	
	/// If the plug-in is not configured to connect to Elluminate, return.
	if (empty ($CFG->elluminate_auth_username) || empty ($CFG->elluminate_auth_username)) {
		return true;
	}

	$timenow = time();

	$sql = "SELECT el.id, el.meetingid FROM {$CFG->prefix}elluminate el WHERE el.timestart <= $timenow " .
			"AND el.meetingid IS NOT NULL";
			
	/// Ensure that any new recordings on the server are stored for meetings created by Moodle.	
	if ($sessions = get_records_sql($sql)) {
		
		foreach ($sessions as $session) {
			echo '<br>';
			$filter = 'meetingId = ' . $session->meetingid;
			if ($recordings = elluminate_list_all_recordings_for_meeting($session->meetingid)) {		
				foreach ($recordings as $recording) {					
					if (record_exists('elluminate', 'meetingid', $recording->meetingid)) {
						if (!record_exists('elluminate_recordings', 'recordingid', $recording->recordingid)) {
							$er = new stdClass;
							$er->meetingid = $recording->meetingid;
							$er->recordingid = $recording->recordingid;
							$er->created = $recording->created;
							$er->recordingsize = $recording->size;
							$er->visible = 1;
							$er->groupvisible = 1;
							insert_record('elluminate_recordings', $er);
						}
					}
				}
			}
		}
	}
	return true;
}

function elluminate_grades($elluminateid) {
	if (!$elluminate = get_record('elluminate', 'id', $elluminateid)) {
		return NULL;
	}

	if ($elluminate->grade == 0) { // No grading
		return NULL;
	}

	$return = new stdClass;

	$grades = get_records_menu('elluminate_attendance', 'elluminateid', $elluminateid, '', 'userid,grade');

	if ($elluminate->grade > 0) {
		if ($grades) {
			foreach ($grades as $userid => $grade) {
				if ($grade == -1) {
					$grades[$userid] = '-';
				}
			}
		}
		$return->grades = $grades;
		$return->maxgrade = $elluminate->grade;

	} else { // Scale
		if ($grades) {
			$scaleid = - ($elluminate->grade);
			$maxgrade = "";
			if ($scale = get_record('scale', 'id', $scaleid)) {
				$scalegrades = make_menu_from_list($scale->scale);
				foreach ($grades as $userid => $grade) {
					if (empty ($scalegrades[$grade])) {
						$grades[$userid] = '-';
					} else {
						$grades[$userid] = $scalegrades[$grade];
					}
				}
				$maxgrade = $scale->name;
			}
		}
		$return->grades = $grades;
		$return->maxgrade = $maxgrade;
	}

	return $return;
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $elluminate null means all elluminates
 * @param int $userid specific user only, 0 mean all
 */
function elluminate_update_grades($elluminate = null, $userid = 0, $nullifnone = true) {
	global $CFG;
	if (!function_exists('grade_update')) { //workaround for buggy PHP versions
		require_once ($CFG->libdir . '/gradelib.php');
	}

	if ($elluminate != null) {
		if ($grades = elluminate_get_user_grades($elluminate, $userid)) {
			foreach ($grades as $k => $v) {
				if ($v->rawgrade == -1) {
					$grades[$k]->rawgrade = null;
				}
			}
			elluminate_grade_item_update($elluminate, $grades);
		} else {
			elluminate_grade_item_update($elluminate);
		}

	} else {
		$sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
								                      FROM {$CFG->prefix}elluminate a
								                      INNER JOIN {$CFG->prefix}course_modules cm ON cm.instance = a.id
									                  INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module
									                  WHERE m.name='elluminate'";

		if ($rs = get_recordset_sql($sql)) {
			while ($elluminate = rs_fetch_next_record($rs)) {
				if ($elluminate->grade != 0) {
					elluminate_update_grades($elluminate);
				} else {
					elluminate_grade_item_update($elluminate);
				}
			}
			rs_close($rs);
		}
	}
}

/**
 * Return grade for given user or all users.
 *
 * @param int $elluminateid id of elluminate
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function elluminate_get_user_grades($elluminate, $userid = 0) {
	global $CFG;

	$user = $userid ? "AND u.id = $userid" : "";
    $sql = "SELECT u.id, u.id AS userid, ea.grade AS rawgrade, ea.timemodified AS dategraded
            FROM {$CFG->prefix}user u
            INNER JOIN {$CFG->prefix}elluminate_attendance ea ON ea.userid = u.id
            WHERE ea.elluminateid = {$elluminate->id}
            $user";


	return get_records_sql($sql);
}

/**
 * Create grade item for given elluminate
 *
 * @param object $elluminate object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function elluminate_grade_item_update($elluminate, $grades = NULL) {
	global $CFG;
	if (!function_exists('grade_update')) { //workaround for buggy PHP versions
		require_once $CFG->libdir . '/gradelib.php';
	}

	if (!isset ($elluminate->courseid)) {
		$elluminate->courseid = $elluminate->course;
	}

	if($elluminate->groupmode == 0) {
		if (empty ($elluminate->cmidnumber)) {
			if ($cm = get_coursemodule_from_instance('elluminate', $elluminate->id)) {
				$elluminate->cmidnumber = $cm->id;
			}
		}
	} else {
		if (empty ($elluminate->cmidnumber)) {
			if ($cm = get_coursemodule_from_instance('elluminate', $elluminate->groupparentid)) {
				$elluminate->cmidnumber = $cm->id;
			}
		}
	}

	$params = array (
		'itemname' => $elluminate->name
	);

	if (!empty ($elluminate->cmidnumber)) {
		$params['idnumber'] = $elluminate->cmidnumber;
	}

	if ($elluminate->grade > 0) {
		$params['gradetype'] = GRADE_TYPE_VALUE;
		$params['grademax'] = $elluminate->grade;
		$params['grademin'] = 0;

	} else
		if ($elluminate->grade < 0) {
			$params['gradetype'] = GRADE_TYPE_SCALE;
			$params['scaleid'] = - $elluminate->grade;

		} else {
			$params['gradetype'] = GRADE_TYPE_TEXT; // allow text comments only
		}

	if ($grades === 'reset') {
		$params['reset'] = true;
		$grades = NULL;
	}

	return grade_update('mod/elluminate', $elluminate->courseid, 'mod', 'elluminate', $elluminate->id, 0, $grades, $params);
}

/**
* Delete grade item for given elluminate
*
* @param object $elluminate object
* @return object elluminate
*/
function elluminate_grade_item_delete($elluminate) {
	global $CFG;
	require_once ($CFG->libdir . '/gradelib.php');

	if (!isset ($elluminate->courseid)) {
		$elluminate->courseid = $elluminate->course;
	}

	return grade_update('mod/elluminate', $elluminate->courseid, 'mod', 'elluminate', $elluminate->id, 0, NULL, array (
		'deleted' => 1
	));
}

function elluminate_get_course_moderators($cmid) {
	$ctx = get_context_instance(CONTEXT_MODULE, $cmid);
	/// Get meeting moderators.
	$participants = array ();
	if ($users = elive_get_users_by_capability($ctx, 'mod/elluminate:moderatemeeting', '', 'u.lastname, u.firstname', '', '', '', '', false)) {
		$participants = $users;
	}
	
	if (!empty ($participants)) {
		return $participants;
	}

	return false;
}

function elluminate_get_participants($elluminateid) {
	//Must return an array of user records (all data) who are participants
	//for a given instance of elluminate. Must include every user involved
	//in the instance, independient of his role (student, teacher, admin...)
	//See other modules as example.

	if (!$meeting = get_record('elluminate', 'id', $elluminateid)) {
		return false;
	}

	$participants = array ();
	$cm = get_coursemodule_from_instance('elluminate', $meeting->id, $meeting->course);
	$ctx = get_context_instance(CONTEXT_MODULE, $cm->id);

	/// Get meeting moderators.
	if ($users = elive_get_users_by_capability($ctx, 'mod/elluminate:moderatemeeting', '', 'u.lastname, u.firstname', '', '', '', '', false)) {

		$participants = $users;
	}

	/// Get meeting participants.
	if ($users = elive_get_users_by_capability($ctx, 'mod/elluminate:joinmeeting', '', 'u.lastname, u.firstname', '', '', '', '', false)) {

		foreach ($users as $uid => $user) {
			if (!isset ($participants[$uid])) {
				$participants[$uid] = $user;
			}
		}
	}

	/// Make sure we have the meeting creator as well.
	if (!isset ($participants[$meeting->creator])) {
		$participants[$meeting->creator] = get_record('user', 'id', $meeting->creator);
	}

	if (!empty ($participants)) {
		return $participants;
	}

	return false;
}

function elluminate_scale_used($elluminateid, $scaleid) {
	//This function returns if a scale is being used by one elluminate
	//it it has support for grading and scales. Commented code should be
	//modified if necessary. See forum, glossary or journal modules
	//as reference.

	$return = false;

	$rec = get_record("elluminate", "id", "$elluminateid", "grade", "-$scaleid");

	if (!empty ($rec) && !empty ($scaleid)) {
		$return = true;
	}

	return $return;
}

/**
 * Checks if scale is being used by any instance of elluminate
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any elluminate
 */
function elluminate_scale_used_anywhere($scaleid) {
	if ($scaleid and record_exists('elluminate', 'grade', - $scaleid)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Process the module config options from the main settings page to remove
 * any spaces from the beginning of end of the string input fields.
 *
 * @param &$config Reference to the form config data.
 * @return none
 */
function elluminate_process_options(& $config) {
	$config->server = trim($config->server);
	$config->adapter = trim($config->adapter);
	$config->auth_username = trim($config->auth_username);
	$config->auth_password = trim($config->auth_password);
	$config->elluminate_server_type = trim($config->elluminate_server_type);
}

/**
 * Clean up the roles we created during install.
 *
 * @param none
 * @return bool True on success, False otherwise.
 */
function elluminate_uninstall() {
	$result = true;

	if ($mrole = get_record('role', 'shortname', 'elluminatemoderator')) {
		$result = $result && delete_role($mrole->id);
		$result = $result && delete_records('role_allow_assign', 'allowassign', $mrole->id);
	}

	if ($prole = get_record('role', 'shortname', 'elluminateparticipant')) {
		$result = $result && delete_role($prole->id);
		$result = $result && delete_records('role_allow_assign', 'allowassign', $prole->id);
	}

	return $result;
}

/**
 * Returns an array of user objects reprsenting the participants for a given
 * meeting.
 *
 * @uses $CFG
 * @param int $meetingid The meeting ID to get the participants list for.
 * @param string $fields A comma-separated list of fields to return.
 * @return array An aray of user objects.
 */
function elluminate_get_meeting_participants($meetingid, $moderator = false) {
    global $CFG;
    $users = array();

    if ($moderator) {    
        $participants = elluminate_list_participants($meetingid, ELLUMINATELIVE_ROLE_MODERATOR);
    } else {
        $participants = elluminate_list_participants($meetingid, ELLUMINATELIVE_ROLE_PARTICIPANT);
    }
	
	 
    if (!empty($participants)) {
        foreach ($participants as $participant) {
            if ($participant['user'])
            {
                $sql = "SELECT mu.*
              FROM {$CFG->prefix}user mu
              WHERE mu.id = {$participant['user']}";

                if ($user = get_record_sql($sql)) {
                    $users[] = $user;
                }
      		}
        }
    }
    return $users;
}

/**
 * Find and return all the events assosciated with a given meeting.
 *
 * An event for a specific meeting has the meeting ID in a SPAN tag
 * surrounding the title of the format:
 *
 * <span id="elm-ID"> ... </span>
 *
 * Where ID is the meeting ID in question.
 *
 * @uses $CFG
 * @param long $meetingid The Elluminate Live! meeting ID.
 * @return array An array of events.
 */
function elluminate_get_events($meetingid) {
	global $CFG;

	$sql = "SELECT e.*
					                FROM {$CFG->prefix}event e
					                WHERE e.modulename = 'elluminate'
					                AND e.instance = $meetingid";

	$events = get_records_sql($sql);
	return $events;
}

function elluminate_has_course_event($meetingid) {
	global $CFG;

	if (!$meeting = get_record('elluminate', 'id', $meetingid)) {
		return false;
	}

	$sql = "SELECT *
					                FROM {$CFG->prefix}event
					                WHERE modulename = 'elluminate'
					                AND instance = {$meeting->id}
					                AND courseid = {$meeting->course}";

	return record_exists_sql($sql);
}

/**
 * Find and return a list of users who currently have events assosciated
 * with a given meeting.  This is useful when either adding new users or
 * deleting users from a private event.
 *
 * @uses $CFG
 * @param long $meetingid The Elluminate Live! meeting record ID.
 * @return array An array of user IDs.
 */
function elluminate_get_event_users($meetingid) {
	global $CFG;

	if (!$meeting = get_record('elluminate', 'id', $meetingid)) {
		return false;
	}

	$sql = "SELECT u.id
					                FROM {$CFG->prefix}user u
					                LEFT JOIN {$CFG->prefix}event ON e.userid = u.id
					                WHERE e.modulename = 'elluminate'
					                AND e.instance = {$meeting->id}
					                AND e.courseid = {$meeting->course}";

	return get_records_sql($sql);
}

/**
 * Add a list of users to a given meeting.  Also handles adding the calendar
 * event and any assosciated reminders for the user.  The latter action can
 * only occur with a private meeting.
 *
 * If $moderators is not set to true, the users will be added as participants.
 *
 * @param object  $meeting    The Elluminate Live! activity database record.
 * @param array   $userids    A list of Moodle user IDs to add to the meeting.
 * @param int     $groupid    The group ID this is specifically for.
 * @param boolean $moderators True if the users being added are moderators.
 * @return boolean True on success, False otherwise.
 */
function elluminate_add_users($meeting, $userids, $groupid = 0, $moderators = false) {
	if($meeting->groupmode == 0) {
		$cm = get_coursemodule_from_instance('elluminate', $meeting->id, $meeting->course);
	} else {
		if($meeting->groupparentid != 0) {
			$cm = get_coursemodule_from_instance('elluminate', $meeting->groupparentid, $meeting->course);
		} else {
			$cm = get_coursemodule_from_instance('elluminate', $meeting->id, $meeting->course);
		}
	}
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);	
	$timenow = time();
	/// Basic event record for the database.
	$event = new stdClass;
	$event->name = addslashes(get_string('calendarname', 'elluminate', stripslashes($meeting->name)));
	$event->description = $meeting->description;
	$event->format = 1;
	$event->courseid = 0;
	$event->groupid = 0;
	$event->modulename = 'elluminate';
	$event->instance = $meeting->id;
	$event->eventtype = '';
	$event->visible = 1;
	$event->timestart = $meeting->timestart;
	$duration = $meeting->timeend - $meeting->timestart;
	if ($duration < 0) {
		$event->timeduration = 0;
	} else {
		$event->timeduration = $duration;
	}

	if ($moderators) {
		$role = get_record('role', 'shortname', 'elluminatemoderator');
	} else {
		$role = get_record('role', 'shortname', 'elluminateparticipant');
	}

	foreach ($userids as $userid) {
		if (!role_assign($role->id, $userid, $groupid, $context->id, $timenow)) {
			return false;
		}
	
		/// If this meeting already has a session created, make sure this user is added to it.		
		if ($moderators) {				
			if (!elluminate_add_participant($meeting->id, $userid, true)) {
				return false;
			}
		} else {
			if (!elluminate_add_participant($meeting->id, $userid, false)) {
				return false;
			}
		}
		

		$elluminate = get_record('elluminate', 'id', $meeting->id);
		/// Add a new event for this user.	
		$event->userid = $userid;
		$event->timemodified = time();
		$event = addslashes_object($event);	
	
		$event->id = insert_record('event', $event);
	}
	
	if(!empty($elluminate->meetingid)) {
		$args = array();
			
		$args[0]['name'] = 'meetingId';
		$args[0]['value'] = $elluminate->meetingid;
		$args[0]['type'] = 'xsd:int';
	
		$args[1]['name'] = 'chairList';
		$args[1]['value'] = $elluminate->chairlist;
		$args[1]['type'] = 'xsd:string';
	
		$args[2]['name'] = 'nonChairList';
		$args[2]['value'] = $elluminate->nonchairlist;
		$args[2]['type'] = 'xsd:string';
		
		
		$result = elluminate_send_command('setMeeting', $args);
	}
	
	return true;
}

/**
 * Remove a list of users from a given meeting.  Also handles removing the
 * calendar event and any assosciated reminders for the user.  This action
 * can only occur with a private meeting.
 *
 * If $moderators is not set to true, the users will be removed from the
 *
 * @param object  $meeting    The Elluminate Live! activity database record.
 * @param array   $userids    A list of Moodle user IDs to remove from the meeting.
 * @param int     $groupid    The group ID this is specifically for.
 * @param boolean $moderators True if the users being removed are moderators.
 * @param boolean $force      Whether to force an override of the behviour for not deleting the meeting creator.
 * @return boolean True on success, False otherwise.
 */

function elluminate_del_users($meeting, $userids, $groupid = 0, $moderators = false) {
	$elluminate = get_record('elluminate', 'id', $meeting->id);
	
	if($meeting->groupmode == 0) {
		$cm = get_coursemodule_from_instance('elluminate', $meeting->id, $meeting->course);
	} else {
		if($meeting->groupparentid != 0) {
			$cm = get_coursemodule_from_instance('elluminate', $meeting->groupparentid, $meeting->course);
		} else {
			$cm = get_coursemodule_from_instance('elluminate', $meeting->id, $meeting->course);
		}
	}
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	//$session = get_record('elluminate_session', 'elluminate', $meeting->id);
	$timenow = time();

	$muserids = array ();

	if ($moderators) {
		$role = get_record('role', 'shortname', 'elluminatemoderator');
	} else {
		$role = get_record('role', 'shortname', 'elluminateparticipant');
	}

	/// Remove each user from the meeting on the Elluminate Live! server.
	foreach ($userids as $userid) {
		if ($userid != $meeting->creator) {
			if (!role_unassign($role->id, $userid, $groupid, $context->id)) {
				continue;
			}
			
			$muserids[] = $userid;
			
			if (!elluminate_delete_participant($elluminate->id, $userid)) {
				return false;
			}
		}
	}	

	if(!empty($elluminate->meetingid)) {
		$args[0]['name'] = 'meetingId';
		$args[0]['value'] = $elluminate->meetingid;
		$args[0]['type'] = 'xsd:int';
		
		$args[1]['name'] = 'chairList';
		$args[1]['value'] = $elluminate->chairlist;
		$args[1]['type'] = 'xsd:string';
	
		$args[2]['name'] = 'nonChairList';
		$args[2]['value'] = $elluminate->nonchairlist;
		$args[2]['type'] = 'xsd:string';
	
		$result = elluminate_send_command('setMeeting', $args);
	}
	
	if (empty ($muserids)) {
		return true;
	}

	/// Delete user events.
	if (count($muserids) > 1) {
		$select = "modulename = 'elluminate' AND instance = {$meeting->id} AND " .
		"userid IN (" . implode(', ', $muserids) . ")";
		return delete_records_select('event', $select);
	} else {
		return delete_records('event', 'modulename', 'elluminate', 'instance', $meeting->id, 'userid', $userid);
	}
}

/**
 * Adds or edits an existing calendar event for an assosciated meeting.
 *
 * There aqre two possible meeting configurations:
 * 1. A private meeting where only the people chosen to be particpants
 *    are allowed access.
 * 2. A public meeting where anyone in a given course is allowed to
 *    access to meeting.
 *
 * We must handle adding and removing users to a private meeting and also
 * deleteing unnecessary events when a meeting changes from private to
 * public and vice versa.
 *
 * @uses $CFG
 * @param int $meetingid The meeting ID to edit the calendar event for.
 * @param boolean $delete Whether the meeting is being deleted.
 * @return boolean True on success, False otherwise.
 */
function elluminate_cal_edit($meetingid, $delete = false) {
	global $CFG;

	if (!$meeting = get_record('elluminate', 'id', $meetingid)) {
		return false;
	}
	/// Special action if we're deleting a meeting.
	if ($delete) {
		if ($events = elluminate_get_events($meeting->id)) {
			foreach ($events as $event) {
				delete_records('event', 'id', $event->id);
			}
		}

		return true;
	}

	if ($meeting->private) {
		/// If this meeting has been newly marked private, delete the old, public,
		/// event record.
		$admin = get_admin();

		$sql = "DELETE FROM {$CFG->prefix}event
								                    WHERE modulename = 'elluminate'
								                    AND instance = {$meeting->id}
								                    AND courseid = {$meeting->course}
								                    AND userid = {$admin->id}";

		execute_sql($sql, false);

	} else {
		if (!$meeting->private && !elluminate_has_course_event($meeting->id)) {
			/// Create the new course event.
			$admin = get_admin();

			$event = new stdClass;
			$event->name = get_string('calendarname', 'elluminate', $meeting->name);
			$event->description = $meeting->description;
			$event->format = 1;
			$event->courseid = $meeting->course;
			$event->groupid = 0;
			$event->userid = $admin->id;
			$event->modulename = 'elluminate';
			$event->instance = $meeting->id;
			$event->eventtype = '';
			$event->visible = 1;
			$event->timestart = $meeting->timestart;
			$duration = $meeting->timeend - $meeting->timestart;
			if ($duration < 0) {
				$event->timeduration = 0;
			} else {
				$event->timeduration = $duration;
			}
			$event->timemodified = time();
			$event = addslashes_object($event);
			$event->id = insert_record('event', $event);

			return true;
		}
	}
	
        if (!$elluminate = get_record("elluminate", "id",$meetingid)) {
            error("Course module is incorrect");
        }
        if (!$course = get_record("course", "id", $elluminate->course)) {
            error("Course is misconfigured");
        }
        if (!$cm = get_coursemodule_from_instance("elluminate", $elluminate->id, $course->id)) {
            error("Course Module ID was incorrect");
        }	

	/// Modifying any existing events.
	if ($events = elluminate_get_events($meeting->id)) {
		foreach ($events as $event) {
			/// Delete any non-moderator events if this meeting is public...
			$deleted = false;

			if (empty ($meeting->private) && empty ($event->userid)) {
				if ($elm_id = get_field('elluminate_users', 'elm_id', 'userid', $event->userid)) {
					if (!has_capability('mod/elluminate:moderatemeeting', $context, $USER->id, false)) {
						$deleted = delete_records('event', 'id', $event->id);
					}
				}
			}

			if (!$deleted) {
				$event->name = addslashes(get_string('calendarname', 'elluminate', $meeting->name));
				$event->description = addslashes($meeting->description);

				$event->timestart = $meeting->timestart;
				$duration = $meeting->timeend - $meeting->timestart;

				if ($duration < 0) {
					$event->timeduration = 0;
				} else {
					$event->timeduration = $duration;
				}

				$eventtimemodified = time();

				if (!update_record('event', $event)) {
					return false;
				}
			}
		}
	}

	return true;
}

/**
 * ===========================================================================
 * The following are all functions dealing with handling reminders attached
 * to a calendar event.
 * ===========================================================================
 */

/**
 * Adds a reminder for a calendar event.
 *
 * Please note that the last three parameters are only necessary when using
 * the REMINDER_TYPE_INTERVAL (1).
 *
 * @param int $eventid ID of the event to add a reminder for.
 * @param int $rtype The type of reminder.
 * @param int $timedelta The time before the event to send the reminder.
 * @param int $timeinterval The time between reminders being sent.
 * @param int $timeend The point past which no reminders will be send (the
 *                     default ending time is the event itself).
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_add_reminder($eventid, $rtype = 0, $timedelta = 0, $timeinterval = 0, $timeend = 0) {
	// Make sure the event exists
	if (!$event = get_record('event', 'id', intval($eventid))) {
		error('Invalid event ID: ' . $eventid);
	}

	// Just check to make sure we have a valid reminder type
	switch ($rtype) {
		case REMINDER_TYPE_DELTA :
		case REMINDER_TYPE_INTERVAL :
			break;
		default :
			return false;
			break;
	}

	// Create the new reminder object for the database
	$reminder = new stdClass;
	$reminder->event = intval($event->id);
	$reminder->type = intval($rtype);
	$reminder->timedelta = intval($timedelta);
	$reminder->timeinterval = intval($timeinterval);
	$reminder->timeend = intval($timeend);
	$reminder->id = insert_record('event_reminder', $reminder);

	if (!$reminder->id) {
		return false;
	}

	// Send the reminder immediately (for testing purposes)
	if (REMINDER_DEBUG) {
		elluminate_reminder_send($reminder->id);
		elluminate_reminder_remove($reminder->id);
	}

	return true;
}

/**
 * Removes a calendar event reminder.
 *
 * @param int $reminderid ID of the reminder to delete.
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_remove($reminderid) {
	if (!delete_records('event_reminder', 'id', intval($reminderid))) {
		return false;
	}

	return true;
}

/**
 * Edits an existing calendar event reminder.
 *
 * @param int $reminderid ID of the reminder to edit.
 * @param int $rtype The type of reminder.
 * @param int $timedelta The time before the event to send the reminder.
 * @param int $timeinterval The time between reminders being sent.
 * @param int $timeend The point past which no reminders will be send (the
 *                     default ending time is the event itself).
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_edit($reminderid, $rtype, $timedelta = 0, $timeinterval = 0, $timeend = 0) {

	$reminderid = intval($reminderid);
	$rtype = intval($rtype);
	$timedelta = intval($timedelta);
	$timeinterval = intval($timeinterval);
	$timeend = intval($timeend);

	// Make sure the reminder exists
	if (!$reminder = get_record('event_reminder', 'id', $reminderid)) {
		return false;
	}

	// Modify any parameters that have changed
	if ($rtype and $rtype != $reminder->type) {
		$reminder->type = $rtype;
	}
	if ($timedelta and $timedelta != $reminder->timedelta) {
		$reminder->timedelta = $timedelta;
	}
	if ($timeinterval and $timeinterval != $reminder->timeinterval) {
		$reminder->timeinterval = $timeinterval;
	}
	if ($timeend and $reminder->timeend != $timeend) {
		$reminder->timeend = $timeend;
	}

	// Attempt to update the database record
	if (!update_record('event_reminder', $reminder)) {
		return false;
	}

	return true;
}

/**
 * Checks if a calendar event has any reminders assosicated with it and
 * returns them as an array of objects.  If there are no reminders returns
 * NULL instead.
 *
 * @param int $meetingid ID of the event to check for reminders.
 * @return array An array of reminder objects or NULL.
 */
function elluminate_reminder_get_reminders($meetingid) {
	// Make sure the event exists
	if (!$meeting = get_record('event', 'id', intval($meetingid))) {
		error('Invalid meeting ID: ' . $meetingid);
	}

	// Get records
	if (!$reminders = get_records('event_reminder', 'event', $event->id, 'timedelta ASC')) {
		return NULL;
	}

	return $reminders;
}

/**
 * Displays the HTML to edit / delete existing reminders and / or to add a new
 * reminder to an Elluminate Live! meeting.
 *
 * @param int $meetingid ID of the meeting HTML form we are drawing into (leave
 *                       blank to just draw the form elements to add a new
 *                       reminder.
 * @return none
 */
function elluminate_reminder_draw_form($meetingid = 0) {
	if ($meetingid) {
		if (!$meeting = get_record('elluminate', 'id', intval($meetingid))) {
			return;
		}
	}

	// Setup available number of days and hours
	$days = array ();
	$hours = array ();

	// How many days can we choose from before the event in question?
	if ($meetingid) {
		$delta = $elluminate->meetingtimebegin - time();
	} else {
		$delta = REMINDER_DELTA_DEFAULT;
	}

	// Setup values for the select lists on the form
	$dayscount = floor($delta / (24 * 60 * 60));

	for ($i = 0; $i <= $dayscount; $i++) {
		$days[] = $i;
	}
	for ($i = 0; $i < 24; $i++) {
		$hours[] = $i;
	}

	// Print out any existing event reminders
	if ($meetingid) {
		if ($reminders = elluminate_reminder_get_reminders($meeting->id)) {
?>
<tr>
  <td></td>
  <td>
  <fieldset><legend><?php print_string('formreminders', 'event_reminder'); ?></legend> <?php


			foreach ($reminders as $reminder) {
				$remindername = 'reminder' . $reminder->id;
?>
  <div><input type="checkbox" name="reminderdeleteids[]" value="<?php echo $reminder->id; ?>" /> <?php print_string('formtimebeforeevent', 'event_reminder'); ?>
  <?php


				$day = floor($reminder->timedelta / (24 * 60 * 60));
				$hour = ($reminder->timedelta - ($day * 24 * 60 * 60)) / (60 * 60);

				choose_from_menu($days, $remindername . '_days', $day, '');
				print_string('formdays', 'event_reminder');
				choose_from_menu($hours, $remindername . '_hours', $hour, '');
				print_string('formhours', 'event_reminder');
?></div>
  <br />
  <?php


			}
?> <input type="submit" name="reminder_delete"
    value="<?php print_string('formdeleteselectedreminders', 'event_reminder'); ?>" /> <?php


		}
	}
?></fieldset>
  </td>
</tr>
<tr>
  <td colspan="2">
  <hr />
  <p><?php print_string('formaddnewreminder', 'event_reminder'); ?></p>
  </td>
</tr>
<tr>
  <td></td>
  <td>
  <div><?php print_string('formtimebeforeevent', 'event_reminder'); ?> <?php


	choose_from_menu($days, 'remindernew_days', '', '');
	print_string('formdays', 'event_reminder');
	choose_from_menu($hours, 'remindernew_hours', '', '');
	print_string('formhours', 'event_reminder');
?></div>
  </td>
</tr>
  <?php


}

/**
 * Determines the type of event and returns that type as a string.
 *
 * @param int $eventid The ID of the event.
 * @return string The type of the event.
 */
function elluminate_reminder_event_type($eventid) {
	// Make sure the event exists
	if (!$event = get_record('event', 'id', intval($eventid))) {
		error('Invalid event ID: ' . $eventid);
	}

	$type = 'none';

	// Determine the type of event
	if (!$event->courseid and !$event->groupid and $event->userid) {
		$type = 'user';
	}
	elseif ($event->courseid and !$event->groupid and $event->userid) {
		if ($event->courseid != SITEID) {
			$type = 'course';
		}
	}
	elseif ($event->courseid and $event->groupid and $event->userid) {
		$type = 'group';
	} else {
		$type = 'none';
	}

	return $type;
}

/**
 * Update the interval start time for a record of the interval type.
 *
 * @param int $reminderid ID of the reminder to update the interval for.
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_interval_update($reminderid) {
	if (!$reminder = get_record('event_reminder', 'id', intval($reminderid))) {
		return false;
	}

	// If the reminder type isn't of the Interval variety, we can't udpate
	// the interval, can we?
	if ($reminder->type != REMINDER_TYPE_INTERVAL) {
		return false;
	}

	// Update the value for the next interval
	$reminder->timedelta += $reminder->timeinterval;

	if (!update_record('event_reminder', $reminder)) {
		return false;
	}

	return true;
}

/**
 * Checks a calendar event to see if any reminders assosciated with it should
 * have a message sent out.
 *
 * @param int $eventid ID of the event to check the reminder times for.
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_check($eventid) {
	// Make sure the event exists
	if (!$event = get_record('event', 'id', intval($eventid))) {
		error('Invalid event ID: ' . $eventid);
	}

	// Get records
	if (!$reminders = get_records('event_reminder', 'event', $event->id)) {
		return false;
	}

	// Check each record to see if the time has passed to issue a reminder
	foreach ($reminders as $reminder) {
		switch ($reminder->type) {
			case REMINDER_TYPE_DELTA :
				// If the current time is past the delta before the event,
				// send the message.
				if (time() > $event->timestart - $reminder->timedelta) {
					//notify(userdate(time()) . ' ' . userdate($reminder->timedelta));
					//if (time() > $reminder->timedelta) {
					//                        notify('sending reminder!');
					elluminate_reminder_send($reminder->id);
					elluminate_reminder_remove($reminder->id);
				}
				break;

			case REMINDER_TYPE_INTERVAL :
				if (time() > $event->timeend) {
					// If we are passed the cutoff (end) time for this reminder,
					// delete the reminder from the system.
					reminder_remove_reminder($reminder->id);
				}
				elseif (time() > $event->timedelta) {
					// If we are passed an interval, send a reminder and update
					// the interval start time.
					//                        notify('sending reminder!');
					elluminate_reminder_send($reminder->id);
					elluminate_reminder_interval_udpdate($reminder->id);
				}
				break;

			default :
				return false;
				break;
		}
	}

	return true;
}

/**
 * Sends the reminder message for the specified reminder.
 *
 * @param int $reminderid ID of the reminder to send a message for.
 * @return boolean True on success, False otherwise.
 */
function elluminate_reminder_send($reminderid) {
	// Make sure the reminder exists
	if (!$reminder = get_record('event_reminder', 'id', intval($reminderid))) {
		return false;
	}

	// Get the event record that this reminder belongs to
	if (!$event = get_record('event', 'id', $reminder->event)) {
		return false;
	}

	// Determine the type of event
	$type = elluminate_reminder_event_type($event->id);

	// General message information.
	$userfrom = get_admin();
	$site = get_site();
	$subject = get_string('remindersubject', 'event_reminder', $site->fullname);
	$message = elluminate_reminder_message($event->id, $type);

	// Send the reminders to user(s) based on the type of event.
	switch ($type) {
		case 'user' :
			// Send a reminder to the user
			if (!empty ($CFG->messaging)) {
				// use message system
			} else {
				$user = get_record('user', 'id', $event->userid);
				email_to_user($user, $userfrom, $subject, $message);
				//email_to_user($user, $userfrom, 'Reminder Test', 'Testing!');
			}
			break;

		case 'course' :
			// Get all the users in the course and send them the reminder
			$users = get_course_users($event->courseid);

			foreach ($users as $user) {
				if (!empty ($CFG->messaging)) {
					// use message system
				} else {
					email_to_user($user, $userfrom, $subject, $message);
				}
			}
			break;

		case 'group' :
			// Get all the users in the group and send them the reminder
			$users = get_group_users($event->groupid);

			foreach ($users as $user) {
				if (!empty ($CFG->messaging)) {
					// use message system
				} else {
					email_to_user($user, $userfrom, $subject, $message);
				}
			}
			break;

		default :
			return false;
			break;
	}

	return true;
}

/**
 * Returns a formatted upcoming event reminder message.
 *
 * @param int $eventid The ID of the event to format a message for.
 * @param string $type The type of event to format the message for.
 */
function elluminate_reminder_message($eventid, $type) {
	// Make sure the event exists
	if (!$event = get_record('event', 'id', intval($eventid))) {
		error('Invalid event ID: ' . $eventid);
	}

	switch ($type) {
		case 'user' :
			$message = get_string('remindermessageuser', 'event_reminder');
			break;

		case 'course' :
			// Get the course record to format message variables
			$course = get_record('course', 'id', $event->courseid);
			$message = get_string('remindermessagecourse', 'event_reminder', $course->fullname);
			break;

		case 'group' :
			// Get the group record to format message variables
			$group = get_record('groups', 'id', $event->groupid);
			$message = get_string('remindermessagegroup', 'event_reminder', $group->name);
			break;

		default :
			return NULL;
			break;
	}

	// Add the date for the event and the description for the event to the end of the message.
	$message .= userdate($event->timestart);
	$message .= get_string('remindereventdescription', 'event_reminder', html2text($event->description));

	return $message;
}

/**
 * ===========================================================================
 * The following are all functions that deal with sending web services calls
 * to an Elluminate Live! server.
 * ===========================================================================
 */

/**
 * Sends a command to an Elluminate Live! server via the web services interface.
 *
 * The structure of the command arguments array is a two-dimensional array in
 * the following format:
 *   $args[]['name']  = argument name;
 *   $args[]['value'] = argument value;
 *   $args[]['type']  = argument type (i.e. 'xsd:string');
 *
 * @uses $CFG
 * @param string $command The name of the command.
 * @param array $args Command arguments.
 * @return mixed|boolean The result object/array or False on failure.
 */
 /*

*/
/**
 * ## PUt something here
 *
 *
 *
 *
 */


function elluminate_send_soap_command_overide_errors($cfgServerUrl, $cfgUsername, $cfgPassword, $command, $args = NULL) {
	global $CFG;

	if (file_exists($CFG->dirroot . '/mod/elluminate/elivenusoap.php')) {
		require_once ($CFG->dirroot . '/mod/elluminate/elivenusoap.php');
	} else {
		error('No SOAP library files found!');
	}

	if (substr($cfgServerUrl, strlen($cfgServerUrl) - 1, 1) != '/') {
		$cfgServerUrl .= '/webservice.event';
	} else {
		$cfgServerUrl .= 'webservice.event';
	}

	/// Encode parameters and the command and adapter name.
	$params = array ();

	if (!empty ($args)) {
		foreach ($args as $arg) {
			$params[$arg['name']] = $arg['value'];
		}
	}

	/// Connect to the server.
	$client = new elive_soap_client($cfgServerUrl);
	//$client = new soap_client($wsdlfile, true);
	$client->xml_encoding = "UTF-8";
	//$client->setCredentials($username,$password,'basic');

	/// Add authentication headers.
	$client->setHeaders('<sas:BasicAuth xmlns:sas="http://soap-authentication.org/basic/2001/10" mustUnderstand="1">
					                    <Name>' . $cfgUsername . '</Name>
					                                <Password>' . $cfgPassword . '</Password>
					            </sas:BasicAuth>');

	$mynamespace = 'http://sas.elluminate.com/';

	/// Send the call to the server.
	$result = $client->call($command, $params, $mynamespace);
	//        $result = $client->call('request', $params);

	$result = elluminate_fix_object($result);

	/// If there is an error, notify the user.
	//if(is_$result['success'] != null)
	/*
	if (is_object($result)) {
		if (array_key_exists('success', $result) && $result->success == false) {
			/// Check for an HTML 404 error.
			if (!empty ($client->response) && ((strstr($client->response, 'HTTP') !== false) && strstr($client->response, '404') !== false)) {
				error('Server not found.');
			}

			$str = '<b>Elluminate Live! error:<br /><br /></b> <i>' . $result->message . '</i>';

			error($str);

			return false;
		}
	}
	*/
	return $result;
}

/**
 * Fix objects being returned as associative arrays (to fit with PHP5 SOAP support)
 *
 * @link /lib/soaplib.php - SEE FOR MORE INFO
 */
 /*

*/

/**
 * This tests for a valid connection to the configured Elluminate Live! server's
 * web service interface.
 *
 * @uses $CFG
 * @param string $serverurl The URL pointing to the Elluminate Live! manager (optional).
 * @param string $adapter   The adapter name (optional).
 * @param string $username  The authentication username (optional).
 * @param string $password  The authentication password (optional).
 * @return boolean True on successful test, False otherwise.
 */
function elluminate_test_connection($serverurl = '', $serveradapter = '',
									$username = '', $password = '',
									$boundary = '', $prepopulate = '',
									$ws_debug = '') {
	global $CFG;

	if (empty ($serverurl)) {
		$serverurl = $CFG->elluminate_server;
	}
	if (empty ($serveradapter)) {
		$serveradapter = $CFG->elluminate_adapter;
	}
	if (empty ($username)) {
		$username = $CFG->elluminate_auth_username;
	}
	if (empty ($password)) {
		$password = $CFG->elluminate_auth_password;
	}
	
	updateInsertTable('config', 'name', 'elluminate_server', $serverurl);	
	updateInsertTable('config', 'name', 'elluminate_adapter', $serveradapter);
	updateInsertTable('config', 'name', 'elluminate_auth_username', $username);
	updateInsertTable('config', 'name', 'elluminate_auth_password', $password);
	updateInsertTable('config', 'name', 'elluminate_boundary_default', $boundary);
	updateInsertTable('config', 'name', 'elluminate_pre_populate_moderators', $prepopulate);
	updateInsertTable('config', 'name', 'elluminate_ws_debug', $ws_debug);
	
	

	$args = array ();

	//## This is required as the test command fails unless there is at least one argument
	/*$args[0]['name'] = 'test';
	$args[0]['value'] = true;
	$args[0]['type'] = 'xsd:boolean';*/
	
	//function ($command, $serverurl, $serveradapter, $auth_username, $auth_password, $args = NULL) {
	$result = elluminate_send_command('test', $args, $serverurl, $serveradapter, $username, $password);
	
	if ($result != 'true') {
		return false;
	}
	return true;
}

function updateInsertTable($table, $search_param, $name, $value) {
	$update_config_obj = new Stdclass;
	$update_config_obj->name = $name;
	$update_config_obj->value = $value;
	if($exists = get_record($table, $search_param, $name)) {
		$update_config_obj->id = $exists->id;
		update_record($table, $update_config_obj);
	} else {
		insert_record($table, $update_config_obj);
	}	
}


/**
 * ##CHANGE DESCRIPTION!!!!
 *
 * Given a returned user object from an Elluminate Live! server, process
 * the object into a new, Moodle-useable object.
 *
 * The return object (on success) is of the following format:
 *   $user->userid
 *   $user->loginname
 *   $user->email
 *   $user->firstname
 *   $user->lastname
 *   $user->role
 *   $user->deleted
 *
 * @param object $useradapter The returned 'User Adapter' from the server.
 * @return object An object representing the usser.
 */
function elluminate_process_user($id, $loginname, $email, $firstname, $lastname) {
	$user = new stdClass;
	$user->userid = $id;
	$user->loginname = $loginname;
	$user->email = $email;
	$user->firstname = $firstname;
	$user->lastname = $lastname;

	if (isadmin($id)) {
		$role = ELLUMINATELIVE_ROLE_APPADMIN;
	} else
		if (isteacherinanycourse($id)) {
			$role = ELLUMINATELIVE_ROLE_MODERATOR;
		} else {
			$role = ELLUMINATELIVE_ROLE_PARTICIPANT;
		}

	$user->role = $role;

	if (!get_record('user', 'id', $id, 'deleted', 'false')) {
		$meeting->deleted = false;
	} else {
		$meeting->deleted = true;
	}

	return $user;
}

/**
 * Given a returned meeting object from an Elluminate Live! server, process
 * the object into a new, Moodle-useable object.
 *
 * The return object (on sucess) is of the following format:
 *   $meeting->meetingid
 *   $meeting->facilitatorid
 *   $meeting->privatemeeting
 *   $meeting->name
 *   $meeting->password
 *   $meeting->start
 *   $meeting->end
 *   $meeting->deleted
 *
 * @param object $meetingadapter The returned 'Meeting Adapter' from the server.
 * @return object An object representing the meeting.
 */
function elluminate_process_meeting($meetingadapter) {
	global $USER;

	$meeting = new stdClass;

	$meeting->meetingid = $meetingadapter->meetingId;
	$meeting->facilitatorid = $USER->id;

	//        switch ($meetingadapter->PrivateMeeting) {
	//            case 'true':
	//                $meeting->privatemeeting = true;
	//                break;
	//            case 'false':
	//                $meeting->privatemeeting = false;
	//                break;
	//        }

	$meeting->privatemeeting = false;
	$meeting->name = $meetingadapter->name;
	$meeting->password = ''; //$meetingadapter->Password;
	$meeting->start = substr($meetingadapter->startTime, 0, 10);
	$meeting->end = substr($meetingadapter->endTime, 0, 10);
	
	//        switch ($meetingadapter->Deleted) {
	//            case 'true':
	//                $meeting->deleted = true;
	//                break;
	//            case 'false':
	//                break;
	//        }
	$meeting->deleted = false;

	// If $meeetingadapter is a ListMeetingShort object
	if (!array_key_exists('reserveSeats', $meetingadapter)) {
		$args = array ();

		$args[0]['name'] = 'meetingId';
		$args[0]['value'] = $meeting->meetingid;
		$args[0]['type'] = 'xsd:integer';

		$result = elluminate_send_command('listMeetingLong', $args);

		if (is_string($result)) {
			return false;
		} else
			if (is_object($result)) {
				foreach ($result as $entry) {
					if (!empty ($entry)) {
						$meeting->boundaryTime = $entry->boundaryTime;
						$meeting->recordingModeType = $entry->recordingModeType;
						$meeting->reserveSeats = $entry->reserveSeats;
						$meeting->chairList = $meetingadapter->chairList;
						$meeting->nonChairList = $meetingadapter->nonChairList;
					}
				}
			}

	} else {
		$meeting->boundaryTime = $meetingadapter->boundaryTime;
		$meeting->recordingModeType = $meetingadapter->recordingModeType;
		$meeting->reserveSeats = $meetingadapter->reserveSeats;
		$meeting->chairList = $meetingadapter->chairList;
		$meeting->nonChairList = $meetingadapter->nonChairList;
	}

	return $meeting;
}

/**
 * Process a collection of preload objects from the ELM server.
 * @param object $obj The return object from the web services call.
 * @return array An array of preload objects.
 */
function elluminate_process_preload_list($obj) {
	$preloads = array ();
	foreach ($obj as $entry) {
		if (!empty($entry) && is_object($entry)) {			
			$preload = new stdClass;
			$preload->presentationid = $entry->presentationId;
			$preload->description = $entry->description;
			$preload->size = $entry->size;
			$preload->creatorId = $entry->creatorId;	
			array_push($preloads, $preload);
		}
	}
	
	return $preloads;
}

/**
 * Given a returned participant list object from an Elluminate Live! server,
 * process the object into a new, Moodle-useable array of objects.
 *
 * The return array (on sucess) is of the following format:
 *   $participants['user'] = user object
 *   $participants['role'] = user role value
 *
 * @param object $plist The returned 'Participant List Adapter' from the server.
 * @return array An array representing the list of participants and their meeting roles.
 */
function elluminate_process_participant_list($elluminate) {

	$retusers = array ();
	$i = 0;
	// Moderators
	$chairtoken = strtok($elluminate->chairlist, ",");
	
	while ($chairtoken) {
		$retusers[$i]['user'] = $chairtoken;
		$retusers[$i]['role'] = ELLUMINATELIVE_ROLE_MODERATOR;
		$chairtoken = strtok(",");
		$i++;
	}

	// Participant
	$nonchairtoken = strtok($elluminate->nonchairlist, ",");

	while ($nonchairtoken) {
		$retusers[$i]['user'] = $nonchairtoken;
		$retusers[$i]['role'] = ELLUMINATELIVE_ROLE_PARTICIPANT;
		$nonchairtoken = strtok(",");
		$i++;
	}

	return $retusers;

}

/**
 * Create a new Elluminate Live! account for the supplied Moodle user ID.
 *
 * @param int $userid The Moodle user ID.
 * @return object|boolean The Elluminate Live! user object on success, False otherwise.
 */
function elluminate_new_user($userid, $password) {
	if (!$user = get_record('user', 'id', $userid)) {
		return false;
	}

	/// Determine what role to create this user as.
	$role = ELLUMINATELIVE_ROLE_PARTICIPANT;

	/// Admin = Application Administrator
	if (isadmin($user->id)) {
		$role = ELLUMINATELIVE_ROLE_APPADMIN;

		/// Editing Teachers = Moderator
	} else
		if (isteacherinanycourse($user->id)) {
			$role = ELLUMINATELIVE_ROLE_MODERATOR;
		}

	/// Let's give it a whirl!
	$result = elluminate_create_user($user->username, $password, $user->email, $user->firstname, $user->lastname, $role);

	if (!empty ($result)) {
		return $result;
	}

	debugging('Could not create ELM user account for ' . fullname($user), DEBUG_DEVELOPER);

	return false;
}

/**
 * Map an Elluminate Live! role value to it's string name.
 *
 * @param int $role An Elluminate Live! role value.
 * @return string The string name of the role value.
 */
function elluminate_role_name($role) {
	switch ($role) {
		case ELLUMINATELIVE_ROLE_APPADMIN :
			$string = 'Application Administrator';
			break;
		case ELLUMINATELIVE_ROLE_MODERATOR :
			$string = 'Moderator';
			break;
		case ELLUMINATELIVE_ROLE_PARTICIPANT :
			$string = 'Participant';
			break;
		default :
			$string = '';
			break;
	}

	return $string;
}

/**
 * Get a specific user record from the Elluminate Live! server.
 *
 * See the comments for the elluminate_process_user() function for the format of the
 * returned user records returned in the array.
 *
 * @param int $userid The Elluminate Live! user ID.
 * @return object|boolean The Elluminate Live! user record or False on failure.
 */
function elluminate_get_user($userid) {

	if ($user = get_record('user', 'id', $userid)) {
		return elluminate_process_user($user->id, $user->username, $user->email, $user->firstname, $user->lastname);
	}

	return false;
}

/**
 * Create's a user on the configured Elluminate Live! server and, if successful,
 * stores the mapping between the Moodle user ID and the Elluminate information.
 *
 * See the comments for the elluminate_process_user() function for the format of the
 * returned user records returned in the array.
 *
 * @param string $loginname Login name for this user.
 * @param string $loginpassword Login password for this user.
 * @param string $email The email address for this user.
 * @param string $firstname The first name for this user.
 * @param string $lastname The last name for this user.
 * @param int $role The Elluminate Live! Manager role.
 * @param int $tries Used to append an integer to a username if the username
 *                   we tried to create already exists on the server.
 * @return object|boolean The user object or False on failure.
 */
function elluminate_create_user($loginname, $loginpassword, $email, $firstname, $lastname, $role, $tries = 0) {

	if ($user = get_record('user', 'username', $loginname, 'email', $email)) {
		$elluminate_user = new Object();
		$elluminate_user->userid = $user->id;
		$elluminate_user->elm_id = $user->id;
		$elluminate_user->elm_username = $loginname;
		$elluminate_user->elm_password = $loginpassword;
		$elluminate_user->elm_role = $role;
		$elluminate_user->timecreated = time();

		if (!$elluminate_user->id = insert_record('elluminate_users', $elluminate_user)) {
			return false;
		}

		return elluminate_process_user($user->id, $loginname, $loginpassword, $email, $firstname, $lastname);

	}

	return false;
}

/**
 * Changes the password on the Elluminate Live! server for a given user.
 *
 * @param int $userid The Elluminate Live! user ID.
 * @param string $password The new password.
 * @return boolean True on success, False otherwise.
 */
function elluminate_change_password($userid, $password) {

	$elmuser = get_record('elluminate_users', 'elm_id', $userid);
	$elmuser->password = $password;

	return update_record('elluminate_users', $elmuser);
}

/**
 * Delete's a user account on the Elluminate Live! server for a given user.
 *
 * @param int $userid The Elluminate Live! user ID.
 * @return boolean True on success, False otherwise.
 */
function elluminate_delete_user($userid) {

	return delete_records('elluminate_users', 'elm_id', $userid);

}

/**
 * Get a list of participants fora given meeting.
 *
 * The returned array is of the following structure:
 * array[]['user'] = user object
 * array[]['role'] = user role as a string
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @param int $role The role type to return. (Default 0: return all)
 * @return array|boolean An array of users and roles or False on failure.
 */
function elluminate_list_participants($id, $role = 0) {
	/// Make sure the supplied role value is valid.

	switch ($role) {
		case 0 :
		case ELLUMINATELIVE_ROLE_MODERATOR :
		case ELLUMINATELIVE_ROLE_PARTICIPANT :
			break;
		default :
			return false;
	}
	
	$elluminate = get_record('elluminate', 'id', $id);
	
	if (empty($elluminate)) {
		return false;
	} else {		
		$participants = elluminate_process_participant_list($elluminate);

		/// Return all participants for this meeting.
		if ($role == 0) {
			return $participants;
			/// Return only the selected role type for this meeting.
		} else {
			$retusers = array ();

			foreach ($participants as $participant) {
				if ($participant['role'] == $role) {
					$retusers[] = $participant;
				}
			}
			return $retusers;
		}
	}		
}

/**
 * Determine if the user is a participant of the given meeting.
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @param int $userid The Elluminate Live! user ID.
 * @param boolean $moderator Is the user being added as a moderator? (default False)
 * @return boolean True if the user is a participant, False otherwise.
 */
 function elluminate_is_participant_in_meeting($elluminate, $userid) {
	$meetingparticipants = array ();	
	
	if (empty($elluminate)) {
		return false;
	} else {
		$meetingparticipants = elluminate_process_participant_list($elluminate);
		if (!empty ($meetingparticipants)) {
				foreach ($meetingparticipants as $participant) {
					if ($userid == $participant['user'] && ELLUMINATELIVE_ROLE_MODERATOR == $participant['role']) {
						return true;
					}
					if ($userid == $participant['user'] && ELLUMINATELIVE_ROLE_PARTICIPANT == $participant['role']) {
						return true;
					}
				}
		} else {
			return false;
		}
	}
	return false;
}
 
function elluminate_is_participant($id, $userid, $moderator = false) {
	$meetingparticipants = array ();	
	$elluminate = get_record('elluminate', 'id', $id);
	
	if (empty($elluminate)) {
		return false;
	} else {
		$meetingparticipants = elluminate_process_participant_list($elluminate);
		if (!empty ($meetingparticipants)) {
			if ($moderator) {
				foreach ($meetingparticipants as $participant) {
					if ($userid == $participant['user'] && ELLUMINATELIVE_ROLE_MODERATOR == $participant['role']) {
						return true;
					}
				}
			} else {
				foreach ($meetingparticipants as $participant) {
					if ($userid == $participant['user'] && ELLUMINATELIVE_ROLE_PARTICIPANT == $participant['role']) {
						return true;
					}
				}
			}
		} else {
			return false;
		}
	}
	return false;
}

/**
 * Add a user as a participant to a given meeting.
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @param int $userid The Elluminate Live! user ID.
 * @param boolean $moderator Is the user being added as a moderator? (default False)
 * @return boolean True on success, False otherwise.
 */
function elluminate_add_participant($id, $userid, $moderator = false) {
	/// Make sure this user is not already a participant or moderator for this meeting.
	if (elluminate_is_participant($id, $userid)) {
		return true;
	}

	/// Make sure any existing participants are included in the list.
	$participants = elluminate_list_participants($id);	
	$moderatoruserlist = '';
	$participantuserlist = '';

	$moderatorusercount = 0;
	$participantusercount = 0;
	
	if (!empty ($participants)) {

		foreach ($participants as $participant) {
			if (ELLUMINATELIVE_ROLE_MODERATOR == $participant['role']) {
				if ($moderatorusercount > 0) {
					$moderatoruserlist .= ',';
				}
				$moderatoruserlist .= $participant['user'];
				$moderatorusercount++;
			} else {
				if ($participantusercount) {
					$participantuserlist .= ',';
				}
				$participantuserlist .= $participant['user'];
				$participantusercount++;
			}
		}
	}

	/// Append the new user we're adding.
	if ($moderator) {
		if ($moderatorusercount) {
			$moderatoruserlist .= ',';
		}
		$moderatoruserlist .= $userid;
	} else {
		if ($participantusercount) {
			$participantuserlist .= ',';
		}
		$participantuserlist .= $userid;
	}
	
	$elluminate->chairlist = $moderatoruserlist;
	$elluminate->nonchairlist = $participantuserlist;
	
	set_field('elluminate', 'chairlist', $moderatoruserlist, 'id', $id);
	set_field('elluminate', 'nonchairlist', $participantuserlist, 'id', $id);
	return true;
}

/**
 * Delete a participant(s) from a given meeting.
 * ## Needs to be tested
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @param int $userid The Elluminate Live! user ID.
 * @return boolean True on success, False otherwise.
 */
function elluminate_delete_participant($id, $muserids) {
	$chairListArray = array ();
	$nonCharListArray = array ();
	
	$elluminate = get_record('elluminate', 'id', $id);
	
	if (empty($elluminate)) {
		return false;
	} else
		if (is_object($elluminate)) {
				$chairListArray = string_token_to_array($elluminate->chairlist, ",");
				$nonCharList = string_token_to_array($elluminate->nonchairlist, ",");
		}

	$remove = array($muserids);
	$newChairList = remove_items_from_array($chairListArray, $remove);
	$newNonChairList = remove_items_from_array($nonCharList, $remove);

	$chairList = implode(",", $newChairList);
	$nonChairList = implode(",", $newNonChairList);

	set_field('elluminate', 'chairlist', $chairList, 'id', $id);
	set_field('elluminate', 'nonchairlist', $nonChairList, 'id', $id);	
	return true;
}

/**
 * Get a list of meetings from the Elluminate Live! server.
 *
 * See the comments for the elluminate_processmeeting() function for the format
 * of the returned meeting records returned in the array.
 *
 * @param int $role The Elluminate Live! role type to fetch.
 * @return mixed|boolean An array of user objects or False on failure.
 */
function elluminate_list_meetings() {
	$result = elluminate_send_command('listMeetings');

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {
			if (!empty ($result->Collection->Entry)) {
				$retmeetings = array ();
				if (is_array($result->Collection->Entry)) {
					foreach ($result->Collection->Entry as $entry) {
						$retmeetings[] = elluminate_process_meeting($entry->MeetingAdapter);
					}
				} else {
					$retmeetings[] = elluminate_process_meeting($result->Collection->Entry->MeetingAdapter);
				}

				return $retmeetings;
			}
		}

	return false;
}

/**
 * Create a new Elluminate Live! meeting on the server.
 *
 * @param int $start The start date and time of the meeting.
 * @param int $end The end date and time of the meeting.
 * @param string $name The name of the meeting.
 * @param string $facilitator The user ID of the creator of this meeting.
 * @param string $password The password for this meeting.
 * @param boolean $private Is this meeting a private or public meeting?
 * @param int $seats The number of seats to reserve for his meeting.
 * @return object|boolean The newly created meeting object or False on failure.
 */
function elluminate_create_meeting($start, $end, $name, $facilitator, $password = '', $sessiontype, $seats = 0, $boundaryTime, $recordingModeType, $cmid, $creatorid) {
	return elluminate_set_meeting('', $start, $end, $name, $facilitator, $password, $sessiontype, $seats, $boundaryTime, $recordingModeType, $cmid, $creatorid);
}

/**
 * Modify an existing Elluminate Live! meeting on the server.
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @param int $start The start date and time of the meeting.
 * @param int $end The end date and time of the meeting.
 * @param string $name The name of the meeting.
 * @param string $facilitator The user ID of the creator of this meeting.
 * @param string $password The password for this meeting.
 * @param boolean $private Is this meeting a private or public meeting?
 * @param int $seats The number of seats to reserve for his meeting.
 * @return object|boolean The newly created meeting object or False on failure.
 */
function elluminate_update_meeting($meeting, $start, $end, $name, $facilitator, $password = '', $sessiontype, $seats = 0, $boundaryTime, $recordingModeType, $cmid = '') {

	$result = elluminate_set_meeting($meeting, $start, $end, $name, $facilitator, $password, $sessiontype, $seats, $boundaryTime, $recordingModeType, $cmid);
	/// Check for an error.
	if (isset ($result->Detail->Stack->Trace)) {
		$return = '';

		foreach ($result->Detail->Stack->Trace as $trace) {
			$return .= $trace . "\n";
		}

		return $return;
	}

	return $result;
}

function elluminate_set_meeting($meeting, $start, $end, $name, $facilitator, $password, $sessiontype, $seats, $boundaryTime, $recordingModeType, $cmid = '', $creatorid = '') {
	global $USER;
	global $CFG;
	
	$args = array ();
	$i = 0;

	if(!empty($meeting)) {
		$args[0]['name'] = 'meetingId';
		$args[0]['value'] = $meeting;	
		$args[0]['type'] = 'xsd:integer';
	}
	
	if(empty($creatorid)) {
		$args[1]['name'] = 'creatorId';
		$args[1]['value'] = $USER->id;
		$args[1]['type'] = 'xsd:string';
	} else {
		$args[1]['name'] = 'creatorId';
		$args[1]['value'] = $creatorid;
		$args[1]['type'] = 'xsd:string';
	}
	
	$args[2]['name'] = 'startTime';
	$args[2]['value'] = $start . '000';
	$args[2]['type'] = 'xsd:long';
	
	$args[3]['name'] = 'endTime';
	$args[3]['value'] = $end . '000';
	$args[3]['type'] = 'xsd:long';
	
	$args[4]['name'] = 'name';
	$args[4]['value'] = $name;
	$args[4]['type'] = 'xsd:string';
	
	$args[5]['name'] = 'reserveSeats';
	$args[5]['value'] = $seats;
	$args[5]['type'] = 'xsd:integer';

	$currChairList = '';
	if(!empty($meeting)) {
		if ($meeting != '') {
			if($sessiontype == 1) {
				$moderators = elluminate_get_meeting_participants($meeting->meetingid, '', true);
				foreach ($moderators as $moderator) {
					if ($currChairList == '') {
						$currChairList = $moderator->id;				
						
					} else {
						$currChairList = $currChairList . ',' . $moderator->id;
					}
				}
			}
		} else {
			if($CFG->elluminate_pre_populate_moderators == 1) {
				$moderators = elluminate_get_course_moderators($cmid);
				foreach ($moderators as $moderator) {
					if ($currChairList == '') {
						$currChairList = $moderator->id;				
						
					} else {
						$currChairList = $currChairList . ',' . $moderator->id;
					}					
				}
			} else {
				if(empty($creatorid)) {
					$currChairList = $USER->id;
				} else {
					$currChairList = $creatorid;					
				}
			}						
		}
	} else {
		if($CFG->elluminate_pre_populate_moderators == 1) {
			$moderators = elluminate_get_course_moderators($cmid);
			foreach ($moderators as $moderator) {
				if ($currChairList == '') {
					$currChairList = $moderator->id;									
				} else {
					$currChairList = $currChairList . ',' . $moderator->id;
				}					
			}
		} else {
			if(empty($creatorid)) {
					$currChairList = $USER->id;
			} else {
				$currChairList = $creatorid;					
			}
		}
	}		

	$args[6]['name'] = 'chairList';
	$args[6]['value'] = $currChairList;
	$args[6]['type'] = 'xsd:string';

	$args[7]['name'] = 'boundaryTime';
	$args[7]['value'] = $boundaryTime;
	$args[7]['type'] = 'xsd:integer';

	$args[8]['name'] = 'recordingModeType';
	$args[8]['value'] = $recordingModeType;
	$args[8]['type'] = 'xsd:integer';
	
	$args[9]['name'] = 'mustBeSupervised';
	if($CFG->elluminate_must_be_supervised == 1) {
		$args[9]['value'] = true;
	} else {
		$args[9]['value'] = false;
	}
	$args[9]['type'] = 'xsd:boolean';
	
	$args[10]['name'] = 'raiseHandOnEnter';
	if($CFG->elluminate_raise_hand == 1) {
		$args[10]['value'] = true;
	} else {
		$args[10]['value'] = false;
	}
	$args[10]['type'] = 'xsd:boolean';
	
	$args[11]['name'] = 'permissionsOn';
	if($CFG->elluminate_permissions_on == 1) {
		$args[11]['value'] = true;
	} else {
		$args[11]['value'] = false;
	}
	$args[11]['type'] = 'xsd:boolean';
	
	$args[12]['name'] = 'openChair';
	if($CFG->elluminate_open_chair == 1) {
		$args[12]['value'] = true;
	} else {
		$args[12]['value'] = false;
	}
	$args[12]['type'] = 'xsd:boolean';

	$result = elluminate_send_command('setMeeting', $args);

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {
			return $result;
		}
	return false;
	
}

/**
 * Delete a meeting on the Elluminate Live! server.
 *
 * @param long $meetingid The Elluminate Live! meeting ID.
 * @return boolean True on success, False otherwise.
 */
function elluminate_delete_meeting($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('removeMeeting', $args);

	if (is_string($result)) {
		return true;
	}

	return false;
}

/**
 * Get a meeting object from the Elluminate Live! server.
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @return object|boolean The meeting object or False on failure.
 */
function elluminate_get_meeting($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('listMeetingLong', $args);

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {
			foreach ($result as $entry) {
				return elluminate_process_meeting($entry);
			}
		}

	return false;
}

/**
 * Get a meeting object from the Elluminate Live! server.
 *
 * @param int $meetingid The Elluminate Live! meeting ID.
 * @return object|boolean The meeting object or False on failure.
 */
function elluminate_get_meeting_full_response($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('listMeetingLong', $args);

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {
			foreach ($result as $entry) {
				return $entry;
			}
		}

	return false;
}



/**
 * Delete the recording for the given recording ID.
 *
 * @param $string $recordingid Recording ID to identify the recording.
 * @return bool True on success, False otherwise.
 */
function elluminate_delete_recording($recordingid) {
	$args = array ();

	$args[0]['name'] = 'recordingId';
	$args[0]['value'] = $recordingid;
	$args[0]['type'] = 'xsd:string';

	$result = elluminate_send_command('removeRecording', $args);
	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {
			if ($result->id == $recordingid) {

				// If deleted correctly, remove on Moodle side as well.
				delete_records('elluminate_recordings', 'recordingid', $recordingid);
				return true;
			}
		}

	return false;
}

function setRecordingObject($entry) {
	$recording = new stdClass;
	$recording->recordingid = $entry->id;
	$recording->meetingid = $entry->meetingId;
	$recording->roomname = $entry->name;
	$recording->size = $entry->size;
	$recording->facilitator = 0; // There is no facilitator required for SAS
	$recording->created = substr($entry->creationDate, 0, 10);
	return $recording;

}

/**
 * Set parameters for an Elluminate Live! meeting.
 *
 * Only really useful for setting the forced recording status.
 *
 * @param long $meetingid The Elluminate Live! meeting ID.
 * @param string $costcenter The cost center.
 * @param string $moderatornotes The moderator teleconference notes.
 * @param string $usernotes The user/participant teleconference notes.
 * @param string $recordingstatus The default recording mode for the meeting (ON/OFF/REMOTE).
 * @return object|boolean A meeting parameters object or False on failure.
 */
function elluminate_set_meeting_parameters($meetingid, $costcenter = '', $moderatornotes = '', $usernotes = '', $recordingstatus = '') {
	$args = array ();
	$i = 0;

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:long';

	$args[1]['name'] = 'costCenter';
	$args[1]['value'] = $costcenter;
	$args[1]['type'] = 'xsd:string';

	$args[2]['name'] = 'moderatorNotes';
	$args[2]['value'] = $moderatornotes;
	$args[2]['type'] = 'xsd:string';

	$args[3]['name'] = 'userNotes';
	$args[3]['value'] = $usernotes;
	$args[3]['type'] = 'xsd:string';

	$args[4]['name'] = 'recordingStatus';
	$args[4]['value'] = $recordingstatus;
	$args[4]['type'] = 'xsd:string';

	$result = elluminate_send_command('updateMeetingParameters', $args);

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result) && isset ($result->MeetingParametersAdapter)) {
			$parameters = new stdClass;

			$parameters->meetingid = $result->MeetingParametersAdapter->MeetingId;
			$parameters->costcenter = $result->MeetingParametersAdapter->CostCenter;
			$parameters->moderatornotes = $result->MeetingParametersAdapter->ModeratorNotes;
			$parameters->usernotes = $result->MeetingParametersAdapter->UserNotes;
			$parameters->recordingstatus = $result->MeetingParametersAdapter->RecordingStatus;

			return $parameters;
		}

	return false;
}


//TODO ELM
/*
function elluminate_get_meeting_parameters($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:long';

	$result = elluminate_send_command('getMeetingParameters', $args);

	if (is_string($result)) {
		return false;
	} else
			$parameters = stdClass;
		if (is_object($result) && isset ($result->MeetingParametersAdapter)) {

			$parameters->meetingid = $result->MeetingParametersAdapter->MeetingId;
			$parameters->costcenter = $result->MeetingParametersAdapter->CostCenter;
			$parameters->moderatornotes = $result->MeetingParametersAdapter->ModeratorNotes;
			$parameters->usernotes = $result->MeetingParametersAdapter->UserNotes;
			$parameters->recordingstatus = $result->MeetingParametersAdapter->RecordingStatus;

			return $parameters;
		}

	return false;
}
*/

/**
 * Get server parameters for a specific meeting.
 *
 * @param long $meetingid The Elluminate Live! meeting ID.
 * @return object|boolean A server parameters object or False on failure.
 */
function elluminate_get_server_parameters($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:long';

	//TODO ELM
	// This command in elm current is getServerParameters rather than the SAS command 
	$result = elluminate_send_command('getServerConfiguration', $args);

	if (is_string($result)) {
		return false;
	} else
		if (is_object($result) && isset ($result->ServerParametersAdapter)) {
			$parameters = new stdClass;

			$parameters->meetingid = $result->ServerParametersAdapter->MeetingId;
			$parameters->boundaryminutes = $result->ServerParametersAdapter->BoundaryMinutes;
			$parameters->seats = $result->ServerParametersAdapter->Seats;
			$parameters->supervised = $result->ServerParametersAdapter->Supervised;
			$parameters->fullpermissions = $result->ServerParametersAdapter->FullPermissions;
			
			return $parameters;
		}

	return false;
}

/**
 * Get a list of recordings from the Elluminate Live! server and return them in
 * a Moodle object format:
 *
 *  $recording->recordingid - Recording ID.
 *  $recording->meetingid   - Meeting ID.
 *  $recording->roomname    - Meeting name.
 *  $recording->facilitator - Facilitator user ID.
 *  $recording->created     - Date/time recording created.
 *
 * @param $string filter
 * @return array|boolean An array of recording object or False on failure.
 */
function elluminate_list_recordings($meetingId) {

	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingId;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('listRecordingShort', $args);

	if (is_string($result)) {
		return false;
	} else {
		$recordings = array ();
		if (is_array($result)) {
			foreach ($result as $dummy_entry) {
				$entry = getArrayEntry($dummy_entry, 0);
				$recordings[] = setRecordingObject($entry);
			}
		} else {
			$entry = getArrayEntry($result, 0);
			$recordings[] = setRecordingObject($entry);
		}
		return $recordings;
	}

}

/**
 * Get a list of recent recorded meetings based upon the user's system authority:
 *  - admins can see all recent meeting recordings
 *  - teachers see recent recordings in their courses
 *  - students see recent recordings they participated in
 *
 * The return array is of the format where each entry is an object that consists
 * of the following information:
 *
 *  $entry->name        = meeting name
 *  $entry->recordingid = recording ID
 *
 * @uses $USER
 * @param none
 * @return array An array of recorded meeting information.
 */
function elluminate_recent_recordings() {
	global $USER;
	/*
	    if (!$elmuser = get_record('elluminate_users', 'userid', $USER->id)) {
	        return NULL;
	    }
	*/

	/// Get the five most recent recordings.
	if (!$recordings = get_records('elluminate_recordings', '', '', 'created DESC', '*', '', 5)) {
		return NULL;
	}

	$return = array ();

	$type = 'student';

	if (isadmin($USER->id)) {
		$type = 'admin';
	} else
		if (isteacherinanycourse($USER->id, false)) {
			$type = 'teacher';
		}

	$rids = array ();

	foreach ($recordings as $recording) {
		$meeting = get_record('elluminate', 'meetingid', $recording->meetingid);
		$meeting->name = stripslashes($meeting->name);

		if (in_array($meeting->id, $rids)) {
			continue;
		}

		if ($meeting->private == false) {
			$return[] = createRecordingEntry($meeting, $recording);
		} else {
			// ## REFACTOR THIS MESS!!!!
			switch ($type) {
				case 'admin' :
					$return[] = createRecordingEntry($meeting, $recording);
					break;

				case 'teacher' :
					if (isteacher($meeting->course, $USER->id, false)) {
						$return[] = createRecordingEntry($meeting, $recording);
					}

					break;

				case 'student' :
					if (elluminate_is_participant($recording->meetingid, $USER->id)) {
						$return[] = createRecordingEntry($meeting, $recording);
					}
					break;

				default :
					break;
			}
		}
	}

	return $return;
}

function createRecordingEntry($meeting, $recording) {
	$entry = new stdClass;
	$entry->meetingid = $meeting->meetingid;
	$entry->name = $meeting->name;
	$entry->recordingid = $recording->id;
	$entry->created = $recording->created;

	$rids[] = $meeting->id;
	return $entry;
}

/**
 * Login a user and load a meeting object from the Elluminate Live! server.
 *
 * @param long $meetingid The Elluminate Live! meeting ID.
 * @param int $userid A Moodle user ID.
 * @return file|boolean A meeting.jnlp attachment to load a meeting or False on failure.
 */
function elluminate_build_meeting_jnlp($meetingid, $userid, $private, $ismoderator) {
	
	if (!$user = get_record('user', 'id', $userid)) {
		return false;
	}

	$args = array ();

	$name = trim($user->firstname) . ' ' . trim($user->lastname);
	$search = array("<",">","&","#","%","\"","\\","|");	
	$replace = '';	
	$strippedname = str_replace($search, $replace, $name);

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:integer';

	$args[1]['name'] = 'displayName';
	$args[1]['value'] = $strippedname; 
	$args[1]['type'] = 'xsd:string';	

	if($private) {
		$args[2]['name'] = 'userId';
		$args[2]['value'] = $user->id;
		$args[2]['type'] = 'xsd:string';
	} else {
		if($ismoderator) {
			$args[2]['name'] = 'userId';
			$args[2]['value'] = $user->id;
			$args[2]['type'] = 'xsd:string';	
		}
	}

	$result = elluminate_send_command('buildMeetingJNLPUrl', $args);

	if (!is_string($result)) {
		return false;
	} else {		
		header('Location:' . $result);
		return true;
	}
}

/**
 * Login a user and load a recording object from the Elluminate Live! server.
 *
 * @param long $recordingid The Elluminate Live! recording ID.
 * @param int $userid A Moodle user ID.
 * @return file|boolean A meeting.jnlp attachment to load a meeting or False on failure.
 */
function elluminate_build_recording_jnlp($recordingid, $userid) {
	if (!$user = get_record('user', 'id', $userid)) {
		return false;
	}

	$args = array ();

	$args[0]['name'] = 'recordingId';
	$args[0]['value'] = $recordingid;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('buildRecordingJNLPUrl', $args);

	if (!is_string($result)) {
		return false;
	} else {
		header('Location:' . $result);
		return true;
	}
}

/**
 * Get the maximum number of seats available with the current server license.
 *
 * @param none
 * @return int|boolean The maximum number of seats available with the current
 *                     license or false on failure.
 */
function elluminate_get_max_seats() {

	$result = elluminate_get_server_configuration();

	return $result->sessionQuota;
}

/**
 * Get the maximum number of seats available across the specified time span.
 *
 * @param int $start The beginning time.
 * @param int $end The ending time.
 * @param string $exclude A comma-separated list of meeting ID's to exclude from this search.
 * @return int|boolean The maximum number of seats avaialble or false on failure.
 */
function elluminate_get_max_available_seats($start, $end, $exclude = '') {

	// ## The maxAvailableSeats command does not exist in the SAS Default Adapter
	$result = elluminate_get_server_configuration();

	return $result->sessionQuotaAvailable;
}

/**
 * Get the server configuration parameters in object form.
 *
 * @param none
 * @return object|boolean The configuration object or False on failure.
 */
function elluminate_get_server_configuration() {
	$config = new stdClass;

	$args = array ();

	// ## Hack to make sas accept command with no arg.
	$args[0]['name'] = 'dummyArg';
	$args[0]['value'] = '';
	$args[0]['type'] = 'xsd:string';

	$result = elluminate_send_command('getServerConfiguration', $args);
	if (is_string($result)) {
		return false;
	} else
		if (is_object($result)) {			
			$config->boundaryTime = $result->boundaryTime;
			$config->diskQuota = $result->diskQuota;
			$config->diskQuotaAvailable = $result->diskQuotaAvailable;
			$config->maxAvailableTalkers = $result->maxAvailableTalkers;
			$config->raiseHandOnEnter = $result->raiseHandOnEnter;
			$config->sessionQuota = $result->sessionQuota;
			$config->sessionQuotaAvailable = $result->sessionQuotaAvailable;
			$config->timeZone = $result->timeZone;

			return $config;
		}
}

/**
 * Check to see if seat reservation is enabled on the Elluminate Live! server.
 * ## Currently there is no way to determine seat reservation checking through
 * ## the SAS default adapter.  Currently we simply return the value 'false'
 *
 * @param none
 * @return bool True if seat reservation is enabled on the server, False otherwise.
 */
function elluminate_seat_reservation_check() {
	return true;
}

/**
 * Create a new preload file.
 *
 * @param string $type     The type of preload file: 'whiteboard' or 'media'
 * @param string $name     The preload file name.
 * @param string $mimetype The file mime type.
 * @param int    $length   The file length, in bytes.
 * @param int    $ownerid  The ELM user ID who is adding this file (optional).
 * @return object|bool The created preload object or, False on error.
 */
function elluminate_create_preload($type, $name, $mimetype, $length, $ownerid = '') {
	$args = array ();

	if ($type != ELLUMINATELIVE_PRELOAD_WHITEBOARD && $type != ELLUMINATELIVE_PRELOAD_MEDIA) {
		return false;
	}

	$args[0]['name'] = 'type';
	$args[0]['value'] = $type;
	$args[0]['type'] = 'xsd:string';

	$args[1]['name'] = 'name';
	$args[1]['value'] = $name;
	$args[1]['type'] = 'xsd:string';

	$args[2]['name'] = 'mimeType';
	$args[2]['value'] = $mimetype;
	$args[2]['type'] = 'xsd:string';

	$args[3]['name'] = 'length';
	$args[3]['value'] = $length;
	$args[3]['type'] = 'xsd:long';

	if (!empty ($ownerid)) {
		$args[4]['name'] = 'ownerId';
		$args[4]['value'] = $ownerid;
		$args[4]['type'] = 'xsd:string';
	}

	$result = elluminate_send_command('createPreload', $args);
	if (is_string($result)) {
		return false;
	} else {		
		if (is_object($result)) {			
			return $result;
		}
	}
	return false;
}

/**
 * Create a new preload file.
 *
 * @param string $type     The type of preload file: 'whiteboard' or 'media'
 * @param string $name     The preload file name.
 * @param string $mimetype The file mime type.
 * @param int    $length   The file length, in bytes.
 * @param int    $ownerid  The ELM user ID who is adding this file (optional).
 * @return object|bool The created preload object or, False on error.
 */
function elluminate_upload_presentation_content($filename, $description, $content, $creatorid, $actual_filename) {
	$args = array ();

	$memory_limit = ini_get("memory_limit");
	$maxinputtime = ini_get("max_input_time");
	$maxexecutiontime = ini_get("max_execution_time");
	
	ini_set('memory_limit', '-1');
	ini_set('max_input_time', '-1');
	ini_set('max_execution_time', '-1');

	$args[0]['name'] = 'filename';
	$args[0]['value'] = $filename;
	$args[0]['type'] = 'xsd:string';

	$args[1]['name'] = 'description';
	$args[1]['value'] = $filename;
	$args[1]['type'] = 'xsd:string';

	$args[2]['name'] = 'content';
	$args[2]['value'] = base64_encode($content);
	$args[2]['type'] = 'xs:base64Binary';

	$args[3]['name'] = 'creatorId';
	$args[3]['value'] = $creatorid;
	$args[3]['type'] = 'xsd:long';

	$result = elluminate_send_command('uploadPresentationContent', $args);

	if (is_string($result)) {
		ini_set('memory_limit', $memory_limit);
		ini_set('max_input_time', $maxinputtime);
		ini_set('max_execution_time', $maxexecutiontime);
		
		return false;
	} else {		
		if (is_object($result) && isset ($result->DefaultAdapterPresentationResponse)) {				
			$preload = new stdClass;
			$preload->presentationid = $result->DefaultAdapterPresentationResponse->presentationId;
			$preload->description = addslashes($actual_filename);
			$preload->size = $result->DefaultAdapterPresentationResponse->size;
			$preload->creatorid = $result->DefaultAdapterPresentationResponse->creatorId;
			
			insert_record('elluminate_preloads', $preload);		
				
			ini_set('memory_limit', $memory_limit);
			ini_set('max_input_time', $maxinputtime);
			ini_set('max_execution_time', $maxexecutiontime);				
				
			return $preload;
		}
	}
	
	ini_set('memory_limit', $memory_limit);
	ini_set('max_input_time', $maxinputtime);
	ini_set('max_execution_time', $maxexecutiontime);
	return false;
}

/**
 * Delete a preload file from the ELM server.
 *
 * @param long $preloadid The preload ID.
 * @return bool True on success, False otherwise.
 */
function elluminate_delete_preload($presentationid, $sessionid) {
	$args = array ();

	$args[0]['name'] = 'presentationId';
	$args[0]['value'] = $presentationid;
	$args[0]['type'] = 'xsd:long';

	$args[1]['name'] = 'sessionId';
	$args[1]['value'] = $sessionid;
	$args[1]['type'] = 'xsd:string';

	$result = elluminate_send_command('removeSessionPresentation', $args);

	delete_records('elluminate_preloads', 'meetingid', $sessionid);

	return true;
}

/**
 * Associate a preload with a specific meeting.
 *
 * @param long $preloadid The preload ID.
 * @param long $meetingid The meeting ID.
 * @return bool True on success, False otherwise.
 */
function elluminate_set_session_presentation($presentationid, $sessionid) {
	$args = array ();


	$args[0]['name'] = 'sessionId';
	$args[0]['value'] = $sessionid;
	$args[0]['type'] = 'xsd:long';

	$args[1]['name'] = 'presentationId';
	$args[1]['value'] = $presentationid;
	$args[1]['type'] = 'xsd:long';

	$result = elluminate_send_command('setSessionPresentation', $args);	
	
	if (is_string($result)) {
		delete_records('elluminate_preloads', 'presentationid', $presentationid);
		return false;
	} else {		
			$preload = get_record('elluminate_preloads', 'presentationid', $presentationid);
			$preload->meetingid = $sessionid;
			
			update_record('elluminate_preloads', $preload);
				
			return $result;
	}	
}

/**
 * Delete a preload from a specific meeting instance.
 *
 * @param long $preloadid The preload ID.
 * @param long $meetingid The meeting ID.
 * @return bool True on success, False otherwise.
 */
function elluminate_delete_meeting_preload($preloadid, $meetingid) {
	$args = array ();

	$args[0]['name'] = 'preloadId';
	$args[0]['value'] = $preloadid;
	$args[0]['type'] = 'xsd:long';

	$args[1]['name'] = 'meetingId';
	$args[1]['value'] = $meetingid;
	$args[1]['type'] = 'xsd:long';

	$result = elluminate_send_command('removeMeetingPreload', $args);
	
	if (!empty ($result)) {
		return false;
	}
	
	return true;

}

/**
 * Get a list of all the preloads associated with a given meeting.
 *
 * @param long $meetingid The meeting ID.
 * @return array|bool An array of preload objects or, False on error.
 */
function elluminate_list_meeting_preloads($meetingid) {
	$preload = get_record('elluminate_preloads', 'meetingid', $meetingid);	
	
	if (empty ($preload)) {
		return false;
	} else {		
		if (is_object($preload)) {
			return $preload;
		}
	return false;
	}
}

/**
 * Return the URL linking to the support page on the configured Elluminagte Live! server.
 *
 * @param none
 * @return string The URL pointing to the support page.
 */
function elluminate_support_link() {
	return 'http://www.elluminate.com/support/'; /*
					    global $CFG;
					
					    /// Create the correct URL of the endpoint based upon the configured server address.
					    $serverurl = $CFG->elluminate_server;
					
					    if (substr($serverurl, strlen($serverurl) - 1, 1) != '/') {
					        $serverurl .= '/support.help';
					    } else {
					        $serverurl .= 'support.help';
					    }
					
					    return $serverurl;*/
}

/**
 * Convert a string tokens to an array providing a delimeter
 *
 * @param $str = The string to be converted to an array
 * @param $del = The delimeter
 */
function string_token_to_array($str, $del) {
	$array = array ();
	$i = 0;

	$token = strtok($str, $del);

	while ($token) {
		$array[$i] = $token;
		$token = strtok($del);
		$i++;
	}

	return $array;
}

function remove_items_from_array($mainlist, $itemstoremove) {
	$newarray = array ();
	
	foreach ($mainlist as $mainlistitem) {
		if (!in_array($mainlistitem, $itemstoremove)) {
			$newarray[] = $mainlistitem;
		}
	}

	return $newarray;
}

function getArrayEntry($result, $index) {
	$i = 0;
	foreach ($result as $entry) {
		if ($i == $index) {
			return $entry;
		}
	}
}

function checkListForUserById($user, $userList) {
	foreach ($userList as $entry) {
		if ($entry->id == $user->id)
			return true;
	}
	return false;
}

function elluminate_list_all_recordings() {
	$args = array ();

	$args[0]['name'] = 'startTime';
	$args[0]['value'] = getLastDayTimeInMilliseconds();
	$args[0]['type'] = 'xsd:long';

	$args[1]['name'] = 'endTime';
	$args[1]['value'] = getCurrentTimeInMilliseconds();
	$args[1]['type'] = 'xsd:long';

	/// 31535940000 -- # of miliseconds in a year

	$result = elluminate_send_command('listRecordingShort', $args);

	if (is_string($result)) {
		return false;
	} else {
		$recordings = array ();
		if (is_array($result)) {
			foreach ($result as $dummy_entry) {
				$entry = getArrayEntry($dummy_entry, 0);
				$recordings[] = setRecordingObject($entry);
			}
		} else {
			$entry = getArrayEntry($result, 0);
			$recordings[] = setRecordingObject($entry);
		}
		return $recordings;
	}
}

function elluminate_list_all_recordings_for_meeting($meetingid) {
	$args = array ();

	$args[0]['name'] = 'meetingId';
	$args[0]['value'] = $meetingid;
	$args[0]['type'] = 'xsd:integer';

	$result = elluminate_send_command('listRecordingShort', $args);

	if (is_string($result)) {
		return false;
	} else {
		$recordings = array ();
		if (is_array($result)) {
			foreach ($result as $dummy_entry) {
				$entry = getArrayEntry($dummy_entry, 0);
				$recordings[] = setRecordingObject($entry);
			}
		} else {
			$entry = getArrayEntry($result, 0);
			$recordings[] = setRecordingObject($entry);
		}
		return $recordings;
	}
}

function getCurrentTimeInMilliseconds() {
	$currDate = date('U') * 1000;
	$currDate = number_format($currDate, 0, '.', '');
	return $currDate;
}

function getLastDayTimeInMilliseconds() {
	$lastDay = date('U') * 1000 - (24 * 60 * 60 * 1000);
	$lastDay = number_format($lastDay, 0, '.', '');
	return $lastDay;
}

/**
 * ===========================================================================
 * The following are all functions that deal with sending web services calls
 * to an Elluminate Live! server.
 * ===========================================================================
 */



/**
 * Sends a command to an Elluminate Live! server via the web services interface.
 *
 * The structure of the command arguments array is a two-dimensional array in
 * the following format:
 *   $args[]['name']  = argument name;
 *   $args[]['value'] = argument value;
 *   $args[]['type']  = argument type (i.e. 'xsd:string');
 *
 * @uses $CFG
 * @param string $command The name of the command.
 * @param array $args Command arguments.
 * @return mixed|boolean The result object/array or False on failure.
 */
    function elluminate_send_command($command, $args = NULL, $server_url_param = '', $server_adapter_param = '', $auth_username_param = '', $auth_password_param = '') {
        global $CFG;
		
        if (!empty($server_url_param) || !empty($server_adapter_param) ||
            !empty($auth_password_param) || !empty($server_url_param)) {

			$serverurl = $server_url_param;
			$serveradapter = $server_adapter_param;
			$auth_username = $auth_username_param;
			$auth_password = $auth_password_param;

        } else {        
	        if (empty($CFG->elluminate_server) || empty($CFG->elluminate_adapter) ||
	            empty($CFG->elluminate_auth_username) || empty($CFG->elluminate_auth_password)) {
	
	            debugging('Module not correctly configured');
	            return false;
	        } else {
	       	    /// Create the correct URL of the endpoint based upon the configured server address.
	        	$serverurl = $CFG->elluminate_server;
				$serveradapter = $CFG->elluminate_adapter;
				$auth_username = $CFG->elluminate_auth_username;
				$auth_password = $CFG->elluminate_auth_password;
	        }
        }
        
       /* if (file_exists($CFG->libdir . '/soap/nusoap.php')) {
            require_once($CFG->libdir . '/soap/nusoap.php');
        } else if (file_exists($CFG->libdir . '/nusoap/nusoap.php')) {
            require_once($CFG->libdir . '/nusoap/nusoap.php');
        } else {
            error('No SOAP library files found!');
        }*/
            
		if (file_exists($CFG->dirroot . '/mod/elluminate/elivenusoap.php')) {
			require_once ($CFG->dirroot . '/mod/elluminate/elivenusoap.php');
		} else {
			error('No SOAP library files found!');
		}       

        if (substr($serverurl, strlen($serverurl) - 1, 1) != '/') {
            $serverurl .= '/webservice.event';
        } else {
            $serverurl .= 'webservice.event';
        }

    /// Connect to the server.
        $client = new elive_soap_client($serverurl);
        $client->xml_encoding = "UTF-8";

    /// Encode parameters and the command and adapter name.
        $params = '';
        if (!empty($args)) {
            foreach ($args as $arg) {
            	$typeArray = explode(":", $arg['type']);
            	$type_ns = $typeArray[0];
            	$type_val = $typeArray[1];
                $params .= $client->serialize_val($arg['value'], $arg['name'],
                                                  $type_val, false,$type_ns, false, 'encoded');
            }
        }		
    /// Add authentication headers.
        $client->setHeaders(
            '<sas:BasicAuth xmlns:sas="http://sas.elluminate.com/" mustUnderstand="1">
              <sas:Name>' . $auth_username . '</sas:Name>
              <sas:Password>' . $auth_password . '</sas:Password>
            </sas:BasicAuth>'
        );

    /// Send the call to the server.    
        $result = $client->call($command, $params);

    /// If there is an error, notify the user.
        if (!empty($client->error_str) || !empty($client->fault)) {
        /// Check for an HTML 404 error.
            if (!empty($client->response) && ((strstr($client->response, 'HTTP') !== false) &&
                  strstr($client->response, '404') !== false)) {

                debugging('Elluminate Live! Server not found');
                return false;
            }

            echo '<p align="center"><b>Fault:</b></p>';
            $str = '<b>Elluminate Live! error:<br /><br />Call:</b> <i>' . $command . '</i>';

            if (!empty($CFG->elluminate_ws_debug) && !empty($client->debug_str)) {
                $str .= '<br /><br /><b>Debug string:</b> <i>' . $client->debug_str . '</i>';
            }

            if (!empty($client->response)) {
                $str .= '<br /><br /><b>Client response:</b> <i>' . $client->response . '</i>';
            }

            if (!empty($result->faultcode)) {
                $str .= '<br /><br /><b>Result->faultcode:</b> <i>' . $result->faultcode . '</i>';
            }

            if (!empty($result->faultstring)) {
                $str .= '<br /><br /><b>Result->faultstring:</b> <i>' . $result->faultstring . '</i>';
            }

            if (!empty($result->faultdetail)) {
                $str .= '<br /><br /><b>Result->faultdetail:</b><br /><i>' . $result->faultdetail . '</i>';
            }

            debugging($str, DEBUG_DEVELOPER);
            return false;
        }

        $result = elluminate_fix_object($result);
        return $result;
    }

/**
 * Fix objects being returned as associative arrays (to fit with PHP5 SOAP support)
 *
 * @link /lib/soaplib.php - SEE FOR MORE INFO
 */
    function elluminate_fix_object($value) {
        if (is_array($value)) {
            $value = array_map('elluminate_fix_object', $value);
            $keys = array_keys($value);
            /* check for arrays of length 1 (they get given the key "item"
            rather than 0 by nusoap) */
            if (1 === count($value) && 'item' === $keys[0]) {
               $value = array_values($value);
            }
            else {
                /* cast to object if it is an associative array with at least
                one string key */
                foreach ($keys as $key) {
                    if (is_string($key)) {
                        $value = (object) $value;
                        break;
                    }
                }
            }
        }
        return $value;
    }

	function elive_groups_print_activity_menu($cm, $urlroot, $return=false, $groupid) {
	    global $CFG, $USER, $SESSION;
		
	    // groupings are ignored when not enabled
	    if (empty($CFG->enablegroupings)) {
	        $cm->groupingid = 0;
	    }			
		
		$groupmode = groups_get_activity_groupmode($cm);
		if($groupmode == '1' || $groupmode == '2') {				
				
		} else {
			if ($return) {
	            return '';
	        } else {
	            return;
	        }
		}	
		
	    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	    if (has_capability('moodle/site:accessallgroups', $context)) {
	        $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping (all if groupings not used)	        
	    } else {
	    	if($groupmode == '1') {
	        	$allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid); // only assigned groups
	    	} else if ($groupmode == '2') {
	    		$allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping (all if groupings not used)
	    	}	        
	    }

	    $activegroup = groups_get_group($groupid);
		
	    $groupsmenu = array();
	    //$groupsmenu[0] = get_string('allparticipants');
	    if ($allowedgroups) {
	        foreach ($allowedgroups as $group) {
	            $groupsmenu[$group->id] = format_string($group->name);
	        }
	    }
	    
	    $grouplabel = 'Groups:';
	    if($groupid != 0) {
	    	$output = popup_form($urlroot.'&amp;group=', $groupsmenu, 'selectgroup', $activegroup->id, '', '', '', true, 'self', $grouplabel);
	    } else {
	    	$output = popup_form($urlroot.'&amp;group=', $groupsmenu, 'selectgroup', 0, '', '', '', true, 'self', $grouplabel);
	    }
	    
	
	    $output = '<div class="groupselector">'.$output.'</div>';

	    if ($return) {
	        return $output;
	    } else {
	        echo $output;
	    }
	}
	
	function elluminate_check_for_new_groups($elluminate) {
		
		if($elluminate->groupmode == 0) {
			return;
		}
		
		if($elluminate->sessiontype == 2) {
			$availablegroups = groups_get_all_groups($elluminate->course, 0);
		} else if ($elluminate->sessiontype == 3) {
			$availablegroups = groups_get_all_groups($elluminate->course, 0, $elluminate->groupingid);
		} else {
			return;
		}
		
		$available_groups = array();
		foreach ($availablegroups as $availablegroup) {
			array_push($available_groups, $availablegroup->id);
		}
		
		if($elluminate->groupparentid == 0) {
			$elluminate_sessions = get_records('elluminate', 'groupparentid', $elluminate->id);
		} else {
			$elluminate_sessions = get_records('elluminate', 'groupparentid', $elluminate->groupparentid);
		}
		
		$current_groups = array();
		foreach ($elluminate_sessions as $elluminate_session) {
			array_push($current_groups, $elluminate_session->groupid);
		}
		
		$group_differences = array_diff($available_groups, $current_groups);
		if(count($group_differences) > 0) {
			elluminate_insert_additional_group_records($elluminate, $group_differences);
		}		
	}
	
	function elluminate_check_for_group_change($cm, $elluminate) {			
		if($cm->groupingid == 0) {
			if($cm->groupmode > 0) {
				if($elluminate->groupparentid > 0) {
					$elluminate_parent = get_record('elluminate', 'id', $elluminate->groupparentid);		
				} else {					
					$elluminate_parent = $elluminate;
				}
				
				$elluminate_parent->sessiontype = 2;
				$elluminate_parent->groupmode = $cm->groupmode;		
				update_record('elluminate', $elluminate_parent);
				
				$sessions = get_records('elluminate', 'groupparentid', $elluminate_parent->id);
				foreach ($sessions as $session) {
					$session->sessiontype = 2;		
					$session->groupmode = $cm->groupmode;
					update_record('elluminate', $session);
				} 
			} else {
				// This is to handle a group to no groups change.
				if($elluminate->groupparentid > 0) {
					//is a child
					$elluminate_parent = get_record('elluminate', 'id', $elluminate->groupparentid);		
				} else {					
					//is the parent
					$elluminate_parent = $elluminate;
				}
				
				$elluminate_parent->sessiontype = 0;
				$elluminate_parent->groupmode = $cm->groupmode;		
				update_record('elluminate', $elluminate_parent);
				
				$sessions = get_records('elluminate', 'groupparentid', $elluminate_parent->id);
				foreach ($sessions as $session) {
					$session->sessiontype = 0;		
					$session->groupmode = $cm->groupmode;
					update_record('elluminate', $session);
				}	
			}
		} else {					
			// This is to handle a group to no groups change.
				if($elluminate->groupparentid > 0) {
					//is a child
					$elluminate_parent = get_record('elluminate', 'id', $elluminate->groupparentid);		
				} else {					
					//is the parent
					$elluminate_parent = $elluminate;
				}
				
				$elluminate_parent->sessiontype = 3;
				$elluminate_parent->groupmode = $cm->groupmode;		
				update_record('elluminate', $elluminate_parent);
				
				$sessions = get_records('elluminate', 'groupparentid', $elluminate_parent->id);
				foreach ($sessions as $session) {
					$session->sessiontype = 3;		
					$session->groupmode = $cm->groupmode;
					update_record('elluminate', $session);
				}
				
		}
	}
	
	function elluminate_insert_additional_group_records($elluminate, $groupids) {
		$search = array("<",">","&","#","%","\"","\\");
		
		if($elluminate->groupparentid != 0) {
			$elluminate = get_record('elluminate', 'id', $elluminate->groupparentid);	
		}
		
		$replace = '';	
		$originalname = $elluminate->sessionname;
		$strippedname = str_replace($search, $replace, $elluminate->sessionname);
		$parentsessionid = $elluminate->id;
		$elluminate->id = '';
		$elluminate->meetingid = null;
		$elluminate->groupparentid = $parentsessionid;
		$elluminate->meetinginit = 0;
		
		$name_string = $elluminate->sessionname;
		foreach($groupids as $groupid) {	
			$group = groups_get_group($groupid);							
			$elluminate->groupid = $group->id;
			if($elluminate->customname > 0) {
				if($elluminate->customname == 1) {
					$sessionname = $group->name;
				} else if($elluminate->customname == 2) {
					$sessionname = $name_string . ' - ' . $group->name;	
				}				
				if(strlen($sessionname) > 64) {
					$stringlength = strlen($sessionname);
					$toomany = ($stringlength - 64) + 5;
					$remainder = $toomany % 2;
					$difference = $toomany / 2;
					$elluminate->sessionname = substr($sessionname, 0, 33 - ($difference + $remainder)) . ' ... '
											   . substr($sessionname, 32 + $difference, $stringlength);	
				} else {
					$elluminate->sessionname = $sessionname;
				} 
			}
			
				$search = array("<",">","&","#","%","\"","\\","|");	
				$replace = '';
				$databasesafe = array("'");
				$replacedb = '\\\'';	
				
				$elluminate->name = str_replace($databasesafe, $replacedb, $elluminate->name);	
				$elluminate->sessionname = str_replace($search, $replace, $elluminate->sessionname);	
				$elluminate->sessionname = str_replace($databasesafe, $replacedb, $elluminate->sessionname);
				$elluminate->description = str_replace($search, $replace, $elluminate->description);	
				$elluminate->description = str_replace($databasesafe, $replacedb, $elluminate->description);
			
			if (!$elluminate->id = insert_record('elluminate', $elluminate)) {
				return false;
			}			
			elluminate_grade_item_update($elluminate);
		}
	}
	
	function elluminate_get_groupings_select_array($courseid) {
	    global $CFG, $USER, $SESSION;	

		$groupings = get_records('groupings', 'courseid', $courseid, 'name ASC');
		$groupingsarray = array();
		foreach($groupings as $grouping) {
			$groupingsarray[$grouping->id] = $grouping->name;
		}
		return $groupingsarray;
	}
	
	function elluminate_get_groupings_single_select_array($courseid, $groupingid) {
	    global $CFG, $USER, $SESSION;	

		$groupings = get_records('groupings', 'courseid', $courseid, 'id', $groupingid, 'name ASC');
		$groupingsarray = array();
		foreach($groupings as $grouping) {
			$groupingsarray[$grouping->id] = $grouping->name;
		}
		return $groupingsarray;
	}
	
/**
 * Who has this capability in this context?
 *
 * This can be a very expensive call - use sparingly and keep
 * the results if you are going to need them again soon.
 * 
 * Note if $fields is empty this function attempts to get u.*
 * which can get rather large - and has a serious perf impact
 * on some DBs.
 *
 * @param $context - object
 * @param $capability - string capability
 * @param $fields - fields to be pulled
 * @param $sort - the sort order
 * @param $limitfrom - number of records to skip (offset)
 * @param $limitnum - number of records to fetch
 * @param $groups - single group or array of groups - only return
 *               users who are in one of these group(s).
 * @param $exceptions - list of users to exclude
 * @param view - set to true when roles are pulled for display only
 *               this is so that we can filter roles with no visible
 *               assignment, for example, you might want to "hide" all
 *               course creators when browsing the course participants
 *               list.
 * @param boolean $useviewallgroups if $groups is set the return users who
 *               have capability both $capability and moodle/site:accessallgroups
 *               in this context, as well as users who have $capability and who are
 *               in $groups.
 */
function elive_get_users_by_capability($context, $capability, $fields='', $sort='',
        $limitfrom='', $limitnum='', $groups='', $exceptions='', $doanything=true,
        $view=false, $useviewallgroups=false) {
    global $CFG;

    $ctxids = substr($context->path, 1); // kill leading slash
    $ctxids = str_replace('/', ',', $ctxids);

    // Context is the frontpage
    $isfrontpage = false;
    $iscoursepage = false; // coursepage other than fp
    if ($context->contextlevel == CONTEXT_COURSE) {
        if ($context->instanceid == SITEID) {
            $isfrontpage = true;
        } else {
            $iscoursepage = true;
        }
    }

    // What roles/rolecaps are interesting?
    $caps = "'$capability'";
    if ($doanything===true) {
        $caps.=",'moodle/site:doanything'";
        $doanything_join='';
        $doanything_cond='';
    } else {
        // This is an outer join against
        // admin-ish roleids. Any row that succeeds
        // in JOINing here ends up removed from
        // the resultset. This means we remove
        // rolecaps from roles that also have
        // 'doanything' capabilities.
        
        /*
         * MOOD-128 : Adminisrator could not join session as moderator after creating it,
         * 			  if "pre-populate moderator" setting is "yes" at Modules
         * MOOD-124 : could not assign Moodle administator as moderator at the
         * 			  session was not created by administrator
         * 
         * We need to remove this outer join so that administrators and course creators 
         * show up in the moderators and participant lists.
         * 
         */
        
        //$doanything_join="LEFT OUTER JOIN (
        //                      SELECT DISTINCT rc.roleid
        //                      FROM {$CFG->prefix}role_capabilities rc
        //                     WHERE rc.capability='moodle/site:doanything'
        //                            AND rc.permission=".CAP_ALLOW."
        //                            AND rc.contextid IN ($ctxids)
        //                  ) dar
        //                     ON rc.roleid=dar.roleid";
        //$doanything_cond="AND dar.roleid IS NULL";
        $doanything_join = '';
        $doanything_cond = '';
    }

    // fetch all capability records - we'll walk several
    // times over them, and should be a small set

    $negperm = false; // has any negative (<0) permission?
    $roleids = array();

    $sql = "SELECT rc.id, rc.roleid, rc.permission, rc.capability,
                   ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel
            FROM {$CFG->prefix}role_capabilities rc
            JOIN {$CFG->prefix}context ctx on rc.contextid = ctx.id
            $doanything_join
            WHERE rc.capability IN ($caps) AND ctx.id IN ($ctxids)
                  $doanything_cond
            ORDER BY rc.roleid ASC, ctx.depth ASC";
    if ($capdefs = get_records_sql($sql)) {
        foreach ($capdefs AS $rcid=>$rc) {
            $roleids[] = (int)$rc->roleid;
            if ($rc->permission < 0) {
                $negperm = true;
            }
        }
    }
        
    $roleids = array_unique($roleids);

    if (count($roleids)===0) { // noone here!
        return false;
    }

    // is the default role interesting? does it have
    // a relevant rolecap? (we use this a lot later)
    if (in_array((int)$CFG->defaultuserroleid, $roleids, true)) {
        $defaultroleinteresting = true;
    } else {
        $defaultroleinteresting = false;
    }

    //
    // Prepare query clauses
    //
    $wherecond = array();

    // Non-deleted users. We never return deleted users.
    $wherecond['nondeleted'] = 'u.deleted = 0';

    /// Groups
    if ($groups) {
        if (is_array($groups)) {
            $grouptest = 'gm.groupid IN (' . implode(',', $groups) . ')';
        } else {
            $grouptest = 'gm.groupid = ' . $groups;
        }
        $grouptest = 'ra.userid IN (SELECT userid FROM ' .
            $CFG->prefix . 'groups_members gm WHERE ' . $grouptest . ')';

        if ($useviewallgroups) {
            $viewallgroupsusers = get_users_by_capability($context,
                    'moodle/site:accessallgroups', 'u.id, u.id', '', '', '', '', $exceptions);
            $wherecond['groups'] =  '('. $grouptest . ' OR ra.userid IN (' .
                                    implode(',', array_keys($viewallgroupsusers)) . '))';
        } else {
            $wherecond['groups'] =  '(' . $grouptest .')';
        }
    }

    /// User exceptions
    if (!empty($exceptions)) {
        $wherecond['userexceptions'] = ' u.id NOT IN ('.$exceptions.')';
    }

    /// Set up hidden role-assignments sql
    if ($view && !has_capability('moodle/role:viewhiddenassigns', $context)) {
        $condhiddenra = 'AND ra.hidden = 0 ';
        $sscondhiddenra = 'AND ssra.hidden = 0 ';
    } else {
        $condhiddenra = '';
        $sscondhiddenra = '';
    }

    // Collect WHERE conditions
    $where = implode(' AND ', array_values($wherecond));
    if ($where != '') {
        $where = 'WHERE ' . $where;
    }

    /// Set up default fields
    if (empty($fields)) {
        if ($iscoursepage) {
            $fields = 'u.*, ul.timeaccess as lastaccess';
        } else {
            $fields = 'u.*';
        }
    }

    /// Set up default sort
    if (empty($sort)) { // default to course lastaccess or just lastaccess
        if ($iscoursepage) {
            $sort = 'ul.timeaccess';
        } else {
            $sort = 'u.lastaccess';
        }
    }
    $sortby = $sort ? " ORDER BY $sort " : '';

    // User lastaccess JOIN
    if ((strpos($sort, 'ul.timeaccess') === FALSE) and (strpos($fields, 'ul.timeaccess') === FALSE)) {  // user_lastaccess is not required MDL-13810
        $uljoin = '';
    } else {
        $uljoin = "LEFT OUTER JOIN {$CFG->prefix}user_lastaccess ul 
                         ON (ul.userid = u.id AND ul.courseid = {$context->instanceid})";
    }

    //
    // Simple cases - No negative permissions means we can take shortcuts
    //
    if (!$negperm) { 

        // at the frontpage, and all site users have it - easy!
        if ($isfrontpage && !empty($CFG->defaultfrontpageroleid)
            && in_array((int)$CFG->defaultfrontpageroleid, $roleids, true)) {
            
            return get_records_sql("SELECT $fields
                                    FROM {$CFG->prefix}user u
                                    WHERE u.deleted = 0
                                    ORDER BY $sort",
                                   $limitfrom, $limitnum);
        }

        // all site users have it, anyway
        // TODO: NOT ALWAYS!  Check this case because this gets run for cases like this:
        // 1) Default role has the permission for a module thing like mod/choice:choose
        // 2) We are checking for an activity module context in a course
        // 3) Thus all users are returned even though course:view is also required 
        if ($defaultroleinteresting) {
            $sql = "SELECT $fields
                    FROM {$CFG->prefix}user u
                    $uljoin
                    $where
                    ORDER BY $sort";
            return get_records_sql($sql, $limitfrom, $limitnum);
        }

        /// Simple SQL assuming no negative rolecaps.
        /// We use a subselect to grab the role assignments
        /// ensuring only one row per user -- even if they
        /// have many "relevant" role assignments.
        $select = " SELECT $fields";
        $from   = " FROM {$CFG->prefix}user u
                    JOIN (SELECT DISTINCT ssra.userid
                          FROM {$CFG->prefix}role_assignments ssra
                          WHERE ssra.contextid IN ($ctxids)
                                AND ssra.roleid IN (".implode(',',$roleids) .")
                                $sscondhiddenra
                          ) ra ON ra.userid = u.id
                    $uljoin ";
        return get_records_sql($select.$from.$where.$sortby, $limitfrom, $limitnum);
    }

    //
    // If there are any negative rolecaps, we need to
    // work through a subselect that will bring several rows
    // per user (one per RA).
    // Since we cannot do the job in pure SQL (not without SQL stored
    // procedures anyway), we end up tied to processing the data in PHP
    // all the way down to pagination.
    //
    // In some cases, this will mean bringing across a ton of data --
    // when paginating, we have to walk the permisisons of all the rows
    // in the _previous_ pages to get the pagination correct in the case
    // of users that end up not having the permission - this removed.
    //

    // Prepare the role permissions datastructure for fast lookups
    $roleperms = array(); // each role cap and depth
    foreach ($capdefs AS $rcid=>$rc) {

        $rid       = (int)$rc->roleid;
        $perm      = (int)$rc->permission;
        $rcdepth   = (int)$rc->ctxdepth;
        if (!isset($roleperms[$rc->capability][$rid])) {
            $roleperms[$rc->capability][$rid] = (object)array('perm'  => $perm,
                                                              'rcdepth' => $rcdepth);
        } else {
            if ($roleperms[$rc->capability][$rid]->perm == CAP_PROHIBIT) {
                continue;
            }
            // override - as we are going
            // from general to local perms
            // (as per the ORDER BY...depth ASC above)
            // and local perms win...
            $roleperms[$rc->capability][$rid] = (object)array('perm'  => $perm,
                                                              'rcdepth' => $rcdepth);
        }
        
    }

    if ($context->contextlevel == CONTEXT_SYSTEM
        || $isfrontpage
        || $defaultroleinteresting) {

        // Handle system / sitecourse / defaultrole-with-perhaps-neg-overrides
        // with a SELECT FROM user LEFT OUTER JOIN against ra -
        // This is expensive on the SQL and PHP sides -
        // moves a ton of data across the wire.
        $ss = "SELECT u.id as userid, ra.roleid,
                      ctx.depth
               FROM {$CFG->prefix}user u
               LEFT OUTER JOIN {$CFG->prefix}role_assignments ra 
                 ON (ra.userid = u.id
                     AND ra.contextid IN ($ctxids)
                     AND ra.roleid IN (".implode(',',$roleids) .")
                     $condhiddenra)
               LEFT OUTER JOIN {$CFG->prefix}context ctx
                 ON ra.contextid=ctx.id
               WHERE u.deleted=0";
    } else {
        // "Normal complex case" - the rolecaps we are after will
        // be defined in a role assignment somewhere.
        $ss = "SELECT ra.userid as userid, ra.roleid,
                      ctx.depth
               FROM {$CFG->prefix}role_assignments ra 
               JOIN {$CFG->prefix}context ctx
                 ON ra.contextid=ctx.id
               WHERE ra.contextid IN ($ctxids)
                     $condhiddenra
                     AND ra.roleid IN (".implode(',',$roleids) .")";
    }

    $select = "SELECT $fields ,ra.roleid, ra.depth ";
    $from   = "FROM ($ss) ra
               JOIN {$CFG->prefix}user u
                 ON ra.userid=u.id
               $uljoin ";

    // Each user's entries MUST come clustered together
    // and RAs ordered in depth DESC - the role/cap resolution
    // code depends on this.
    $sort .= ' , ra.userid ASC, ra.depth DESC';
    $sortby .= ' , ra.userid ASC, ra.depth DESC ';

    $rs = get_recordset_sql($select.$from.$where.$sortby);

    //
    // Process the user accounts+RAs, folding repeats together...
    //
    // The processing for this recordset is tricky - to fold
    // the role/perms of users with multiple role-assignments
    // correctly while still processing one-row-at-a-time
    // we need to add a few additional 'private' fields to
    // the results array - so we can treat the rows as a
    // state machine to track the cap/perms and at what RA-depth
    // and RC-depth they were defined.
    //
    // So what we do here is:
    // - loop over rows, checking pagination limits
    // - when we find a new user, if we are in the page add it to the
    //   $results, and start building $ras array with its role-assignments
    // - when we are dealing with the next user, or are at the end of the userlist
    //   (last rec or last in page), trigger the check-permission idiom
    // - the check permission idiom will
    //   - add the default enrolment if needed
    //   - call has_capability_from_rarc(), which based on RAs and RCs will return a bool
    //     (should be fairly tight code ;-) )
    // - if the user has permission, all is good, just $c++ (counter)
    // - ...else, decrease the counter - so pagination is kept straight,
    //      and (if we are in the page) remove from the results
    // 
    $results = array();

    // pagination controls
    $c = 0;
    $limitfrom = (int)$limitfrom;
    $limitnum = (int)$limitnum;

    //
    // Track our last user id so we know when we are dealing
    // with a new user... 
    //
    $lastuserid  = 0;
    //
    // In this loop, we 
    // $ras: role assignments, multidimensional array
    // treat as a stack - going from local to general
    // $ras = (( roleid=> x, $depth=>y) , ( roleid=> x, $depth=>y))
    //
    while ($user = rs_fetch_next_record($rs)) {

        //error_log(" Record: " . print_r($user,1));

        //
        // Pagination controls 
        // Note that we might end up removing a user
        // that ends up _not_ having the rights,
        // therefore rolling back $c
        //
        if ($lastuserid != $user->id) {

            // Did the last user end up with a positive permission?
            if ($lastuserid !=0) {
                if ($defaultroleinteresting) {
                    // add the role at the end of $ras
                    $ras[] = array( 'roleid' => $CFG->defaultuserroleid,
                                    'depth'  => 1 );
                }
                if (has_capability_from_rarc($ras, $roleperms, $capability, $doanything)) {
                    $c++;
                } else {
                    // remove the user from the result set,
                    // only if we are 'in the page'
                    if ($limitfrom === 0 || $c >= $limitfrom) {
                        unset($results[$lastuserid]);
                    }
                }
            }

            // Did we hit pagination limit?
            if ($limitnum !==0 && $c >= ($limitfrom+$limitnum)) { // we are done!
                break;
            }

            // New user setup, and $ras reset
            $lastuserid = $user->id;
            $ras = array();
            if (!empty($user->roleid)) {
                $ras[] = array( 'roleid' => (int)$user->roleid,
                                'depth'  => (int)$user->depth );
            }

            // if we are 'in the page', also add the rec
            // to the results...
            if ($limitfrom === 0 || $c >= $limitfrom) {
                $results[$user->id] = $user; // trivial
            }
        } else {
            // Additional RA for $lastuserid
            $ras[] = array( 'roleid'=>(int)$user->roleid,
                            'depth'=>(int)$user->depth );
        }

    } // end while(fetch)

    // Prune last entry if necessary
    if ($lastuserid !=0) {
        if ($defaultroleinteresting) {
            // add the role at the end of $ras
            $ras[] = array( 'roleid' => $CFG->defaultuserroleid,
                            'depth'  => 1 );
        }
        if (!has_capability_from_rarc($ras, $roleperms, $capability, $doanything)) {
            // remove the user from the result set,
            // only if we are 'in the page'
            if ($limitfrom === 0 || $c >= $limitfrom) {
                if (isset($results[$lastuserid])) {
                    unset($results[$lastuserid]);
                }
            }
        }
    }

    return $results;
}	

?>
