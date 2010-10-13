<?php // $Id: attendance.php,v 1.1.2.3 2009/03/19 20:26:50 jfilip Exp $

/**
 * Displays an attendance report for a meeting configured to track attendance.
 *
 * @version $Id: attendance.php,v 1.7 2009/03/19 20:26:50 jfilip Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


 	require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';
    require_once $CFG->libdir . '/tablelib.php';

    $id = required_param('id', PARAM_INT);

    if (!$meeting = get_record('elluminate', 'id', $id)) {
        error('Incorrect meeting ID (' . $meetingid . ')');
    }

    if (!$course = get_record('course', 'id', $meeting->course)) {
        error('Invalid course!');
    }

	if($meeting->sessiontype == 0 || $meeting->sessiontype == 1) {
    	if (!$cm = get_coursemodule_from_instance("elluminate", $meeting->id, $course->id)) {
	        error('Invalid course module.');
	    }
	} else {
		if($meeting->groupparentid == 0) {
			if (!$cm = get_coursemodule_from_instance("elluminate", $meeting->id, $course->id)) {
		        error('Invalid course module.');
		    }	
		} else {
			$meeting = get_record('elluminate', 'id', $meeting->groupparentid);
			if (!$cm = get_coursemodule_from_instance("elluminate", $meeting->id, $course->id)) {
		        error('Invalid course module.');
		    }
		}
	}
    $meeting->cmidnumber = $cm->id;

/// Some capability checks.
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/elluminate:viewattendance', $context);
    $canmanage = has_capability('mod/elluminate:manageattendance', $context);

    require_course_login($course, true, $cm);

	/// Check to see if groups are being used here
    $groupmode    = $meeting->groupmode;
    $currentgroup = groups_get_activity_group($cm, true);

    if (empty($currentgroup)) {
        $currentgroup = 0;
    }

/// Process any attendance modifications.
    if ($canmanage && ($data = data_submitted($CFG->wwwroot . '/mod/elluminate/attendance.php')) && confirm_sesskey()) {
        foreach ($data->userids as $idx => $userid) {
            if ($data->attendance[$idx] > 0) {
                if ($ea = get_record('elluminate_attendance', 'userid', $userid,
                                     'elluminateid', $meeting->id)) {
                    if (empty($ea->grade)) {
                        $ea->grade = $meeting->grade;

                        update_record('elluminate_attendance', $ea);
                        elluminate_update_grades($meeting, $userid);
                    }

                } else {
                    $ea = new Object();
                    $ea->userid       = $userid;
                    $ea->elluminateid = $meeting->id;
                    $ea->grade        = $meeting->grade;

                    insert_record('elluminate_attendance', $ea);
                    elluminate_update_grades($meeting, $userid);

                }
            } else {
                if ($ea = get_record('elluminate_attendance', 'userid', $userid,
                                     'elluminateid', $meeting->id)) {
                    if (!empty($ea->grade)) {
                        $ea->grade = 0;

                        update_record('elluminate_attendance', $ea);
                        elluminate_update_grades($meeting, $userid);

                    }
                }
            }
        }
    }


    $strattendancefor   = get_string('attendancefor', 'elluminate', stripslashes($meeting->name));
    $strelluminates = get_string('modulenameplural', 'elluminate');
    $strelluminate  = get_string('modulename', 'elluminate');

/// Print header.
    $navigation = build_navigation($strattendancefor, $cm);
    print_header_simple(format_string($meeting->name), "",
                        $navigation, "", "", true, '');


	/// Get a list of user IDs for students who are allowed to participate in this meeting.
    $userids = array();
    // Get meeting participants.	
	if($meeting->sessiontype == 0) {
		$course_users = get_records_sql("select u.id from mdl_role_assignments ra, mdl_context con, mdl_course c, mdl_user u where ra.userid=u.id and ra.contextid=con.id and con.instanceid=c.id and c.id=" . $meeting->course);		                             	
	    $userids = array_keys($course_users);       	        	    
	} else if ($meeting->sessiontype == 1) {
		$userids = explode(',', $meeting->nonchairlist);	
	} else if ($meeting->sessiontype == 2) {
		//This will get all the people in the course
		$course_users = get_records_sql("select u.id from mdl_role_assignments ra, mdl_context con, mdl_course c, mdl_user u where ra.userid=u.id and ra.contextid=con.id and con.instanceid=c.id and c.id=" . $meeting->course);		                             	
	    $userids = array_keys($course_users);		
	} else if ($meeting->sessiontype == 3) {
		$userids = array_keys(groups_get_grouping_members($meeting->groupingid, 'distinct u.id', 'u.id'));
	}
	
	/// Only care about non-moderators of the activity.
	if($meeting->sessiontype == 1) {
		$userids = implode(', ', $userids);
	} else {	
		if ($moderators = elive_get_users_by_capability($context, 'mod/elluminate:moderatemeeting',
	                                              'u.id', '', '', '', '', '', false)) {	
	        $userids = implode(', ', array_diff($userids, array_keys($moderators)));
	    } else {
	        $userids = implode(', ', $userids);
	    }
	}

    $select = 'SELECT u.id, u.firstname, u.lastname ';
    $from   = 'FROM '.$CFG->prefix.'user u ';
    $where  = 'WHERE u.id IN (' . $userids . ') ';
    $order  = 'ORDER BY u.firstname ASC, u.lastname ASC ';
    $sql    = $select.$from.$where.$order;

    $usersavail = get_records_sql($sql);
    $table = new flexible_table('meeting-attendance-', $meeting->id);

    $tablecolumns = array('fullname', 'attended');
    $tableheaders = array(get_string('fullname'), get_string('attended', 'elluminate'));

    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($CFG->wwwroot . '/mod/elluminate/attendance.php?id=' . $meeting->id);

    $table->set_attribute('cellspacing', '1');
    $table->set_attribute('cellpadding', '8');
    $table->set_attribute('align', 'center');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->setup();

    if (!empty($usersavail)) {
        $stryes = get_string('yes');
        $strno  = get_string('no');
        $yesno  = array(0 => $strno, 1 => $stryes);


        foreach ($usersavail as $useravail) {
	            $sql = "SELECT a.*
	                    FROM {$CFG->prefix}elluminate_attendance a
	                    WHERE a.userid = {$useravail->id}
	                    AND a.elluminateid = '{$meeting->id}'
	                    AND a.grade > 0";

	        /// Display different form items depending on whether we're using a scale
	        /// or numerical value for an attendance grade.
	        $attended = get_record_sql($sql);

	            if ($canmanage) {
	                if ($attended) {
		                if ($meeting->grade > 0) {
		                    $select = choose_from_menu($yesno, 'attendance[]', 1, NULL, '', '', true);
		                } else {
		                    $select = choose_from_menu(make_grades_menu($meeting->grade), 'attendance[]', $attended->grade, get_string('no'), '', -1, true);
		                }
		            } else {
		                if ($meeting->grade > 0) {
		                    $select = choose_from_menu($yesno, 'attendance[]', 0, NULL, '', '', true);
		                } else {
		                    $select = choose_from_menu(make_grades_menu($meeting->grade), 'attendance[]', -1, get_string('no'), '', -1, true);
		                }
		            }
	        	} else {
	                if ($attended) {
	                    $select = $stryes;
	                } else {
	                    $select = $strno;
	                }
            	}

	            $formelem = $canmanage ? '<input type="hidden" name="userids[]" value="' . $useravail->id . '" />' : '';
	            $table->add_data(array($formelem . fullname($useravail), $select));

    	}
    }

    if ($meeting->grade < 0) {
        print_heading(get_string('attendancescalenotice', 'elluminate'), 'center', '3');
    }

    $sesskey = !empty($USER->sesskey) ? $USER->sesskey : '';
	print_simple_box_start('center', '50%');

 	if ($canmanage && !empty($usersavail)) {
        echo '<form input action="' . $CFG->wwwroot . '/mod/elluminate/attendance.php" method="post">';
        echo '<input type="hidden" name="id" value="' . $meeting->id . '"/>';
        echo '<input type="hidden" name="sesskey" value="' . $sesskey . '" />';

        $table->print_html();

        echo '<center><input type="submit" value="' . get_string('updateattendance', 'elluminate') . '" />';
        echo '</form>';
    } else {
        $table->print_html();
    }
	
	print_simple_box_end();
    print_footer($course);

?>
