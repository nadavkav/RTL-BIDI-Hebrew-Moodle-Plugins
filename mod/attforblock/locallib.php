<?php
global $CFG;
require_once($CFG->libdir.'/gradelib.php');

define('ONE_DAY', 86400);   // Seconds in one day
define('ONE_WEEK', 604800);   // Seconds in one week

function show_tabs($cm, $context, $currenttab='sessions')
{
    $toprow = array();
    if (has_capability('mod/attforblock:manageattendances', $context) or
    has_capability('mod/attforblock:takeattendances', $context) or
    has_capability('mod/attforblock:changeattendances', $context)) {
        $toprow[] = new tabobject('sessions', 'manage.php?id='.$cm->id,
        get_string('sessions','attforblock'));
    }

    if (has_capability('mod/attforblock:manageattendances', $context)) {
        $toprow[] = new tabobject('add', "sessions.php?id=$cm->id&amp;action=add",
        get_string('add','attforblock'));
    }
    if (has_capability('mod/attforblock:viewreports', $context)) {
        $toprow[] = new tabobject('report', 'report.php?id='.$cm->id,
        get_string('report','attforblock'));
        $toprow[] = new tabobject('advancedreport', 'adv_report.php?id='.$cm->id,
        get_string('advancedreport','attforblock'));
    }
    if (has_capability('mod/attforblock:export', $context)) {
        $toprow[] = new tabobject('export', 'export.php?id='.$cm->id,
        get_string('export','quiz'));
    }
    if (has_capability('mod/attforblock:changepreferences', $context)) {
        $toprow[] = new tabobject('settings', 'attsettings.php?id='.$cm->id,
        get_string('settings','attforblock'));

    }
    if (has_capability('mod/attforblock:manageattendances', $context) or
    has_capability('mod/attforblock:takeattendances', $context) or
    has_capability('mod/attforblock:changeattendances', $context)) {
        $toprow[] = new tabobject('teachers', 'addteachers.php?id='.$cm->id,
        get_string('teachers','attforblock'));
        $toprow[] = new tabobject('subjects', 'addsubjects.php?id='.$cm->id,
        get_string('subjects','attforblock'));
        $toprow[] = new tabobject('sessiontitles', 'addtitles.php?id='.$cm->id,
        get_string('sessiontitles','attforblock'));
        $toprow[] = new tabobject('scan', 'scan.php?id='.$cm->id,
        get_string('scan','attforblock'));

    }
    $tabs = array($toprow);
    print_tabs($tabs, $currenttab);
}


//getting settings for course

function get_statuses($courseid, $onlyvisible = true)
{
    if ($onlyvisible) {
        $result = get_records_select('attendance_statuses', "courseid = $courseid AND visible = 1 AND deleted = 0", 'grade DESC');
    } else {
        $result = get_records_select('attendance_statuses', "courseid = $courseid AND deleted = 0", 'grade DESC');
    }
    return $result;
}

//getting settings for course

function get_allstatuses($onlyvisible = true)
{
    if ($onlyvisible) {
        $result = get_records_select('attendance_statuses', "visible = 1 AND deleted = 0", 'courseid ASC, grade DESC');
    } else {
        $result = get_records_select('attendance_statuses', "deleted = 0", 'courseid ASC, grade DESC');
    }
    return $result;
}

//getting scans for course

function get_scans($courseid)
{
    if ($courseid) {
        $result = get_records_select('attendance_scan_logs', "courseid = $courseid");
    } else {
        $result = get_records_select('attendance_scan_logs');
    }
    return $result;
}

//getting teachers for course

function get_teachers($courseid, $onlyvisible = true)
{
    if ($onlyvisible) {
        $result = get_records_select('attendance_teachers', "deleted = 0" , 'teacher ASC');
    } else {
        $result = get_records_select('attendance_teachers','' , 'teacher ASC');
    }
    return $result;
}

//getting sessiontitles for course

function get_sessiontitles($courseid, $onlyvisible = true)
{
    if ($onlyvisible) {
        $result = get_records_select('attendance_sessiontitles', "deleted = 0", 'sessiontitle ASC');
    } else {
        $result = get_records_select('attendance_sessiontitles', '', 'sessiontitle ASC');
    }
    return $result;
}


//getting subjects for course

function get_subjects($courseid, $onlyvisible = true)
{
    if ($onlyvisible) {
        $result = get_records_select('attendance_subjects', "deleted = 0", 'subject ASC');
    } else {
        $result = get_records_select('attendance_subjects', '', 'subject ASC');
    }
    return $result;
}

//gets attendance status for a student, returns count

function get_attendance($userid, $course, $statusid=0)
{
    global $CFG;
    $qry = "SELECT count(*) as cnt
		  	  FROM {$CFG->prefix}attendance_log al
			  JOIN {$CFG->prefix}attendance_sessions ats
			    ON al.sessionid = ats.id
			 WHERE ats.courseid = $course->id
			  	AND ats.sessdate >= $course->startdate
	         	AND al.studentid = $userid";
    if ($statusid) {
        $qry .= " AND al.statusid = $statusid";
    }

    return count_records_sql($qry);
}


function get_attendance_by_period($userid, $course, $statusid=0)
{
    global $CFG, $course;
    $qry = "SELECT count(*) as cnt
		  	  FROM {$CFG->prefix}attendance_log al
			  JOIN {$CFG->prefix}attendance_sessions ats
			    ON al.sessionid = ats.id
			 WHERE ats.courseid = $course->id
	         	AND al.studentid = $userid";


    if ($statusid) {
        $qry .= " AND al.statusid = $statusid";
    }

    return count_records_sql($qry);
}


function get_grade($userid, $course)
{
    global $CFG;
    $logs = get_records_sql("
            SELECT l.id, l.statusid, l.statusset
            FROM {$CFG->prefix}attendance_log l
            JOIN {$CFG->prefix}attendance_sessions s
            ON l.sessionid = s.id
            WHERE l.studentid = $userid
            AND s.courseid  = $course->id
            AND s.sessdate >= $course->startdate");
    $result = 0;
    if ($logs) {
        $stat_grades = records_to_menu(get_records('attendance_statuses', 'courseid', $course->id), 'id', 'grade');
        foreach ($logs as $log) {

            if(isset($stat_grades[$log->statusid])){ // Added as per Juan Carlos Rodr&#237;guez-del-Pino

                $result +=$stat_grades[$log->statusid];// Added as per Juan Carlos Rodr&#237;guez-del-Pino

            } // Added as per Juan Carlos Rodr&#237;guez-del-Pino

            // $result += $stat_grades[$log->statusid]; // Omitted as per Juan Carlos Rodr&#237;guez-del-Pino
        }
    }

    return $result;
}



//temporary solution, for support PHP 4.3.0 which minimal requirement for Moodle 1.9.x
function local_array_intersect_key($array1, $array2) {
    $result = array();
    foreach ($array1 as $key => $value) {
        if (isset($array2[$key])) {
            $result[$key] = $value;
        }
    }
    return $result;
}

function get_maxgrade($userid, $course)
{
    global $CFG;
    $logs = get_records_sql("
            SELECT l.id, l.statusid, l.statusset
            FROM {$CFG->prefix}attendance_log l
            JOIN {$CFG->prefix}attendance_sessions s
            ON l.sessionid = s.id
            WHERE l.studentid = $userid
            AND s.courseid  = $course->id
            AND s.sessdate >= $course->startdate");
    $maxgrade = 0;
    if ($logs) {
        $stat_grades = records_to_menu(get_records('attendance_statuses', 'courseid', $course->id), 'id', 'grade');
        foreach ($logs as $log) {
            $ids = array_flip(explode(',', $log->statusset));
            //			$grades = array_intersect_key($stat_grades, $ids); // require PHP 5.1.0 and higher
            $grades = local_array_intersect_key($stat_grades, $ids); //temporary solution, for support PHP 4.3.0 which minimal requirement for Moodle 1.9.x
            $maxgrade += max($grades);
        }
    }

    return $maxgrade;
}

function get_percent_adaptive($userid, $course) // NOT USED
{
    global $CFG;
    $logs = get_records_sql("
            SELECT l.id, l.statusid, l.statusset
            FROM {$CFG->prefix}attendance_log l
            JOIN {$CFG->prefix}attendance_sessions s
            ON l.sessionid = s.id
            WHERE l.studentid = $userid
            AND s.courseid  = $course->id
            AND s.sessdate >= $course->startdate");
    $result = 0;
    if ($logs) {
        $stat_grades = records_to_menu(get_records('attendance_statuses', 'courseid', $course->id), 'id', 'grade');

        $percent = 0;
        foreach ($logs as $log) {
            $ids = array_flip(explode(',', $log->statusset));
            $grades = array_intersect_key($stat_grades, $ids);
            $delta = max($grades) - min($grades);
            $percent += $stat_grades[$log->statusid] / $delta;
        }
        $result = $percent / count($logs) * 100;
    }
    if (!$dp = grade_get_setting($course->id, 'decimalpoints')) {
        $dp = $CFG->grade_decimalpoints;
    }

    return sprintf("%0.{$dp}f", $result);
}

function get_percent($userid, $course)
{
    global $CFG;

    $maxgrd = get_maxgrade($userid, $course);
    if ($maxgrd == 0) {
        $result = 0;
    } else {
        $result = get_grade($userid, $course) / $maxgrd * 100;
    }
    if ($result < 0) {
        $result = 0;
    }
    if (!$dp = grade_get_setting($course->id, 'decimalpoints')) {
        $dp = $CFG->grade_decimalpoints;
    }

    return sprintf("%0.{$dp}f", $result);
}

function set_current_view($courseid, $view) {
    global $SESSION;

    return $SESSION->currentattview[$courseid] = $view;
}

function get_current_view($courseid) {
    global $SESSION;

    if (isset($SESSION->currentattview[$courseid]))
    return $SESSION->currentattview[$courseid];
    else
    return 'all';
}

function set_current_makeupnote($courseid, $makeupnoteselected) {
    global $SESSION;

    return $SESSION->currentmakeupnote[$courseid] = $makeupnoteselected;
}

function get_current_makeupnote($courseid) {
    global $SESSION;

    if (isset($SESSION->currentmakeupnote[$courseid]))
    return $SESSION->currentmakeupnote[$courseid];
    else
    return 'all';
}

function set_current_sicknote($courseid, $sicknoteselected) {
    global $SESSION;

    return $SESSION->currentsicknote[$courseid] = $sicknoteselected;
}

function get_current_sicknote($courseid) {
    global $SESSION;

    if (isset($SESSION->currentsicknote[$courseid]))
    return $SESSION->currentsicknote[$courseid];
    else
    return 'all';
}


function set_current_student($courseid, $student) {
    global $SESSION;

    return $SESSION->currentstudent[$courseid] = $student;
}

function get_current_student($courseid) {
    global $SESSION;

    if (isset($SESSION->currentstudent[$courseid]))
    return $SESSION->currentstudent[$courseid];
    else
    return '-1';
}


function set_current_teacher($courseid, $teacherselected) {
    global $SESSION;

    return $SESSION->currentteacher[$courseid] = $teacherselected;
}

function get_current_teacher($courseid) {
    global $SESSION;

    if (isset($SESSION->currentteacher[$courseid]))
    return $SESSION->currentteacher[$courseid];
    else
    return '-1';
}

function set_current_subject($courseid, $subjectselected) {
    global $SESSION;

    return $SESSION->currentsubject[$courseid] = $subjectselected;
}

function get_current_subject($courseid) {
    global $SESSION;

    if (isset($SESSION->currentsubject[$courseid]))
    return $SESSION->currentsubject[$courseid];
    else
    return '-1';
}

function set_current_status($courseid, $statusselected) {
    global $SESSION;

    return $SESSION->currentstatus[$courseid] = $statusselected;
}

function get_current_status($courseid) {
    global $SESSION;

    if (isset($SESSION->currentstatus[$courseid]))
    return $SESSION->currentstatus[$courseid];
    else
    return '-1';
}

function get_current_datefrom($courseid) {
    global $SESSION;

    if (isset($SESSION->currentdatefrom[$courseid]))
    return $SESSION->currentdatefrom[$courseid];
    else
    return '-1';
}

function set_current_datefrom($courseid, $datefrom) {
    global $SESSION;

    return $SESSION->currentdatefrom[$courseid] = $datefrom;
}

function get_current_dateto($courseid) {
    global $SESSION;

    if (isset($SESSION->currentdateto[$courseid]))
    return $SESSION->currentdateto[$courseid];
    else
    return '-1';
}

function set_current_dateto($courseid, $dateto) {
    global $SESSION;

    return $SESSION->currentdateto[$courseid] = $dateto;
}

function print_row($left, $right) {
    echo "\n<tr><td nowrap=\"nowrap\" align=\"right\" valign=\"top\" class=\"cell c0\">$left</td><td align=\"left\" valign=\"top\" class=\"info c1\">$right</td></tr>\n";
}

function print_attendance_table($user,  $course) {

    $complete = get_attendance($user->id, $course);
    $percent = get_percent($user->id, $course).'&nbsp;%';
    $grade = get_grade($user->id, $course);

    echo '<table border="0" cellpadding="0" cellspacing="0" class="list">';
    print_row(get_string('sessionscompleted','attforblock').':', "<strong>$complete</strong>");
    $statuses = get_statuses($course->id);
    foreach($statuses as $st) {
        print_row($st->description.': ', '<strong>'.get_attendance($user->id, $course, $st->id).'</strong>');
    }
    print_row(get_string('attendancepercent','attforblock').':', "<strong>$percent</strong>");
    print_row(get_string('attendancegrade','attforblock').':', "<strong>$grade</strong> / ".get_maxgrade($user->id, $course));
    print_row('&nbsp;', '&nbsp;');
    echo '</table>';

}

function print_user_attendaces($user, $cm,  $course = 0, $printing = null) {
    global $CFG, $COURSE, $mode;

    echo '<table class="userinfobox">';
    if (!$printing) {
        echo '<tr>';
        echo '<td colspan="2">'.
        helpbutton('studentview', get_string('attendancereport','attforblock'), 'attforblock', true, false, '', true).
	    		"<a href=\"view.php?id={$cm->id}&amp;student={$user->id}&amp;mode=$mode&amp;printing=yes\" >[".get_string('versionforprinting','attforblock').']</a></td>';
        echo '</tr>';

    }

    echo '<tr>';
    echo '<td class="left side">';
    print_user_picture($user->id, $COURSE->id, $user->picture, true);
    echo '</td>';
    echo '<td class="generalboxcontent">';
    echo '<h1><b>'.fullname($user).'</b></h1>';
    if ($course) {
        // this course only print summary report
        echo '<hr />';
        $complete = get_attendance($user->id, $course);
        if($complete) {
            print_attendance_table($user,  $course);
        } else {
            echo get_string('attendancenotstarted','attforblock');
        }
    } else {
        // all courses print summary reports
        $stqry = "SELECT ats.id,ats.courseid
					FROM {$CFG->prefix}attendance_log al
					JOIN {$CFG->prefix}attendance_sessions ats
					  ON al.sessionid = ats.id
				   WHERE al.studentid = {$user->id}
				GROUP BY ats.courseid
				ORDER BY ats.courseid asc";
        $recs = get_records_sql_menu($stqry);
        If($recs = get_records_sql_menu($stqry)) {
            foreach ($recs as $id => $courseid) {
                echo '<hr />';
                echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="list1">';
                $nextcourse = get_record('course', 'id', $courseid);
                echo '<tr><td valign="top"><strong>'.$nextcourse->fullname.'</strong></td>';
                echo '<td align="right">';
                $complete = get_attendance($user->id, $nextcourse);
                if($complete) {
                    print_attendance_table($user,  $nextcourse);
                } else {
                    echo get_string('attendancenotstarted','attforblock');
                }
                echo '</td></tr>';
                echo '</table>';
            }}
    }


    if ($course) {
        // this course only print detail report
        $stqry = "SELECT ats.sessdate,ats.sessiontitle,ats.subject,ats.teacher,ats.description,al.statusid,al.remarks,al.makeupnotes,al.sicknote
					FROM {$CFG->prefix}attendance_log al
					JOIN {$CFG->prefix}attendance_sessions ats
					  ON al.sessionid = ats.id
				   WHERE ats.courseid = {$course->id} AND al.studentid = {$user->id}
				ORDER BY ats.sessdate asc";
        if ($sessions = get_records_sql($stqry)) {
            $statuses = get_statuses($course->id);
            ?>
<div id="mod-assignment-submissions">
<table
    align="left"
    cellpadding="3"
    cellspacing="0"
    class="submissions"
>
    <tr>
        <th>#</th>
        <th align="center"><?php print_string('date')?></th>
        <th align="center"><?php print_string('time')?></th>
        <th align="center"><?php print_string('sessiontitle','attforblock')?></th>
        <th align="center"><?php print_string('subject','attforblock')?></th>
        <th align="center"><?php print_string('teacher','attforblock')?></th>
        <th align="center"><?php print_string('description','attforblock')?></th>
        <th align="center"><?php print_string('makeupnotes','attforblock')?></th>
        <th align="center"><?php print_string('sicknote','attforblock')?></th>
        <th align="center"><?php print_string('status','attforblock')?></th>
        <th align="center"><?php print_string('remarks','attforblock')?></th>
    </tr>
    <?php
    $i = 1;
    foreach($sessions as $key=>$session)
    {
        ?>
    <tr>
        <td align="center"><?php echo $i++;?></td>
        <td><?php echo userdate($session->sessdate, get_string('strftimedmyw', 'attforblock')); //userdate($students->sessdate,'%d.%m.%y&nbsp;(%a)', 99, false);?></td>
        <td><?php echo userdate($session->sessdate, get_string('strftimehm', 'attforblock')); ?></td>
        <td><?php echo empty($session->sessiontitle) ? get_string('notitle', 'attforblock') : $session->sessiontitle;  ?></td>
        <td><?php echo empty($session->subject) ? get_string('nosubject', 'attforblock') : $session->subject;  ?></td>
        <td><?php echo empty($session->teacher) ? get_string('noteacher', 'attforblock') : $session->teacher;  ?></td>
        <td><?php echo empty($session->description) ? get_string('nodescription', 'attforblock') : $session->description;  ?></td>
        <td><?php echo get_string(($session->makeupnotes), 'attforblock');?></td>
        <td><?php echo get_string(($session->sicknote), 'attforblock');?></td>
        <td><?php echo $statuses[$session->statusid]->description ?></td>
        <td><?php echo $session->remarks;?></td>
    </tr>
    <?php
    }
    echo '</table>';
        } else {
            print_heading(get_string('noattforuser','attforblock'));
        }
    }
    echo '</td></tr><tr><td>&nbsp;</td></tr></table></div>';
}


function print_attendance_line($student,  $course) {

    echo '<table class="generaltable"><thead><tr>';

    foreach($statuses as $st) {
        echo '<th>'.$st->description.'</th>';
    }
    echo '<th>'.get_string('grade').'</th>';
    echo '<th>%</th></tr><tr><td></td>';
    echo'</tr></thead><tbody><tr>';

    foreach($statuses as $st) {
        echo '<td>'.get_attendance($student->id, $course, $st->id).'</td>';
    }
    echo '<td>'.get_grade($student, $course).'&nbsp;/&nbsp;'.get_maxgrade($student->id, $course).'</td>';
    echo '<td>'.get_percent($student, $course).'%'.'</td>';
    echo '</tr></tbody></table>';
}

function print_user_attendaces_report($student, $cm,  $course = 0) {
    global $CFG, $course;
    $userid = $student->id;
    if ($course) {
        // this course only print summary report
        $complete = get_attendance($student, $course);
        if($complete) {
            print_attendance_line($student,  $course);
        } else {
            echo get_string('attendancenotstarted','attforblock');
        }
    } else {
        // all courses print summary reports
        $stqry = "
            SELECT ats.id,ats.courseid
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id}
            GROUP BY ats.courseid
            ORDER BY ats.courseid asc";
        $recs = get_records_sql_menu($stqry);
        If($recs = get_records_sql_menu($stqry)) {
            foreach ($recs as $id => $courseid) {
                echo '<table class="generaltable">';
                $nextcourse = get_record('course', 'id', $courseid);
                echo '<tr><td valign="top"><strong>'.$nextcourse->fullname.'</strong></td>';
                echo '<td align="right">';
                $complete = get_attendance($student->id, $nextcourse);
                if($complete) {
                    print_attendance_line($student,  $nextcourse);
                } else {
                    echo get_string('attendancenotstarted','attforblock');
                }
                echo '</td></tr>';
                echo '</table>';
            }}
    }
}

function print_outstanding_items($user, $cm,  $course = 0, $printing = null) {
    global $CFG, $COURSE, $mode;

    echo '<table class="userinfobox">';
    if (!$printing) {
        echo '<tr>';
        echo '<td colspan="2">'.
        helpbutton('studentview', get_string('attendancereport','attforblock'), 'attforblock', true, false, '', true).
	    		"<a href=\"view.php?id={$cm->id}&amp;student={$user->id}&amp;mode=$mode&amp;printing=yes\" >[".get_string('versionforprinting','attforblock').']</a></td>';
        echo '</tr>';
    }

    echo '<tr>';
    echo '<td class="left side">';
    print_user_picture($user->id, $COURSE->id, $user->picture, true);
    echo '</td>';
    echo '<td class="generalboxcontent">';
    echo '<h1><b>'.fullname($user).'</b></h1>';
    if ($course) {
        echo '<hr />';
        $complete = get_attendance($user->id, $course);
        if($complete) {
            print_attendance_table($user,  $course);
        } else {
            echo get_string('nooutstandingitems','attforblock');
        }
    } else {
        $stqry = "
            SELECT ats.id,ats.courseid
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id}
            GROUP BY ats.courseid
            ORDER BY ats.courseid asc";
        $recs = get_records_sql_menu($stqry);
        foreach ($recs as $id => $courseid) {
            echo '<hr />';
            echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="list1">';
            $nextcourse = get_record('course', 'id', $courseid);
            echo '<tr><td valign="top"><strong>'.$nextcourse->fullname.'</strong></td>';
            echo '<td align="right">';
            $complete = get_attendance($user->id, $nextcourse);
            if($complete) {
                print_attendance_table($user,  $nextcourse);
            } else {
                echo get_string('nooutstandingitems','attforblock');
            }
            echo '</td></tr>';
            echo '</table>';
        }
    }

    if ($course) {
        $stqry = "
                SELECT ats.sessdate,ats.sessiontitle,ats.subject,ats.teacher,ats.description,al.statusid,al.remarks,al.makeupnotes,al.sicknote
                FROM {$CFG->prefix}attendance_log al
                JOIN {$CFG->prefix}attendance_sessions ats
                ON al.sessionid = ats.id
                WHERE al.studentid = {$user->id} AND (al.makeupnotes = 'outstanding' OR al.sicknote = 'outstanding')
                ORDER BY ats.sessdate asc";
        if ($sessions = get_records_sql($stqry)) {
            $statuses = get_statuses($course->id);
            ?>
    <div id="mod-assignment-submissions">
    <table
        align="left"
        cellpadding="3"
        cellspacing="0"
        class="submissions"
    >
        <tr>
            <th>#</th>
            <th align="center"><?php print_string('date')?></th>
            <th align="center"><?php print_string('time')?></th>
            <th align="center"><?php print_string('status','attforblock')?></th>
            <th align="center"><?php print_string('sessiontitle','attforblock')?></th>
            <th align="center"><?php print_string('subject','attforblock')?></th>
            <th align="center"><?php print_string('teacher','attforblock')?></th>
            <th align="center"><?php print_string('description','attforblock')?></th>
            <th align="center"><?php print_string('makeupnotes','attforblock')?></th>
            <th align="center"><?php print_string('sicknote','attforblock')?></th>
            <th align="center"><?php print_string('remarks','attforblock')?></th>
        </tr>
        <?php
        $i = 1;
        foreach($sessions as $key=>$session)
        {
            ?>
        <tr>
            <td align="center"><?php echo $i++;?></td>
            <td><?php echo userdate($session->sessdate, get_string('strftimedmyw', 'attforblock')); //userdate($students->sessdate,'%d.%m.%y&nbsp;(%a)', 99, false);?></td>
            <td><?php echo userdate($session->sessdate, get_string('strftimehm', 'attforblock')); ?></td>
            <td><?php echo $statuses($session->statusid)->description ?></td>
            <td><?php echo empty($session->sessiontitle) ? get_string('notitle', 'attforblock') : $session->sessiontitle;  ?></td>
            <td><?php echo empty($session->subject) ? get_string('nosubject', 'attforblock') : $session->subject;  ?></td>
            <td><?php echo empty($session->teacher) ? get_string('noteacher', 'attforblock') : $session->teacher;  ?></td>
            <td><?php echo empty($session->description) ? get_string('nodescription', 'attforblock') : $session->description;  ?></td>
            <td><?php echo get_string(($session->makeupnotes), 'attforblock');?></td>
            <td><?php echo get_string(($session->sicknote), 'attforblock');?></td>
            <td><?php echo $session->remarks;?></td>
        </tr>
        <?php
        }
        echo '</table>';
        } else {
            print_heading(get_string('nooutstandingitems','attforblock'));
        }
    }
    echo '</td></tr><tr><td>&nbsp;</td></tr></table></div>';
}

function print_submitted_items($user, $cm,  $course = 0, $printing = null) {
    global $CFG, $COURSE, $mode;

    echo '<table class="userinfobox">';
    if (!$printing) {
        echo '<tr>';
        echo '<td colspan="2">'.
        helpbutton('studentview', get_string('attendancereport','attforblock'),
                   'attforblock', true, false, '', true).
                   "<a href=\"view.php?id={$cm->id}&amp;student={$user->id}&amp;mode=$mode&amp;printing=yes\" >[".
        get_string('versionforprinting','attforblock').']</a></td>';
        echo '</tr>';
    }
    echo '<tr>';
    echo '<td class="left side">';
    print_user_picture($user->id, $COURSE->id, $user->picture, true);
    echo '</td>';
    echo '<td class="generalboxcontent">';
    echo '<h1><b>'.fullname($user).'</b></h1>';
    if ($course) {
        echo '<hr />';
        $complete = get_attendance($user->id, $course);
        if($complete) {
            print_attendance_table($user,  $course);
        } else {
            echo get_string('nosubmitteditems','attforblock');
        }
    } else {
        $stqry = "
            SELECT ats.id,ats.courseid
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id}
            GROUP BY ats.courseid
            ORDER BY ats.courseid asc";
        $recs = get_records_sql_menu($stqry);
        foreach ($recs as $id => $courseid) {
            echo '<hr />';
            echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="list1">';
            $nextcourse = get_record('course', 'id', $courseid);
            echo '<tr><td valign="top"><strong>'.$nextcourse->fullname.'</strong></td>';
            echo '<td align="right">';
            $complete = get_attendance($user->id, $nextcourse);
            if($complete) {
                print_attendance_table($user,  $nextcourse);
            } else {
                echo get_string('nosubmitteditems','attforblock');
            }
            echo '</td></tr>';
            echo '</table>';
        }
    }

    if ($course) {
        $stqry = "
            SELECT ats.sessdate,ats.sessiontitle,ats.subject,ats.teacher,ats.description,al.statusid,al.remarks,al.makeupnotes,al.sicknote
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id} AND (al.makeupnotes = 'submitted' OR al.sicknote = 'submitted')
            ORDER BY ats.sessdate asc";
        if ($sessions = get_records_sql($stqry)) {
            $statuses = get_statuses($course->id);
            ?>
        <div id="mod-assignment-submissions">
        <table
            align="left"
            cellpadding="3"
            cellspacing="0"
            class="submissions"
        >
            <tr>
                <th>#</th>
                <th align="center"><?php print_string('date')?></th>
                <th align="center"><?php print_string('time')?></th>
                <th align="center"><?php print_string('sessiontitle','attforblock')?></th>
                <th align="center"><?php print_string('subject','attforblock')?></th>
                <th align="center"><?php print_string('teacher','attforblock')?></th>
                <th align="center"><?php print_string('description','attforblock')?></th>
                <th align="center"><?php print_string('makeupnotes','attforblock')?></th>
                <th align="center"><?php print_string('sicknote','attforblock')?></th>
                <th align="center"><?php print_string('status','attforblock')?></th>
                <th align="center"><?php print_string('remarks','attforblock')?></th>
            </tr>
            <?php
            $i = 1;
            foreach($sessions as $key=>$session)
            {
                ?>
            <tr>
                <td align="center"><?php echo $i++;?></td>
                <td><?php echo userdate($session->sessdate, get_string('strftimedmyw', 'attforblock')); //userdate($students->sessdate,'%d.%m.%y&nbsp;(%a)', 99, false);?></td>
                <td><?php echo userdate($session->sessdate, get_string('strftimehm', 'attforblock')); ?></td>
                <td><?php echo empty($session->sessiontitle) ? get_string('notitle', 'attforblock') : $session->sessiontitle;  ?></td>
                <td><?php echo empty($session->subject) ? get_string('nosubject', 'attforblock') : $session->subject;  ?></td>
                <td><?php echo empty($session->teacher) ? get_string('noteacher', 'attforblock') : $session->teacher;  ?></td>
                <td><?php echo empty($session->description) ? get_string('nodescription', 'attforblock') : $session->description;  ?></td>
                <td><?php echo get_string(($session->makeupnotes), 'attforblock');?></td>
                <td><?php echo get_string(($session->sicknote), 'attforblock');?></td>
                <td><?php echo $statuses($session->statusid)->description ?></td>
                <td><?php echo $session->remarks;?></td>
            </tr>
            <?php
            }
            echo '</table>';
        } else {
            print_heading(get_string('nosubmitteditems','attforblock'));
        }
    }
    echo '</td></tr><tr><td>&nbsp;</td></tr></table></div>';
}
function print_cleared_items($user, $cm,  $course = 0, $printing = null) {
    global $CFG, $COURSE, $mode;
    echo '<table class="userinfobox">';
    if (!$printing) {
        echo '<tr>';
        echo '<td colspan="2">'.
        helpbutton('studentview', get_string('attendancereport','attforblock'),
                   'attforblock', true, false, '', true).
                   "<a href=\"view.php?id={$cm->id}&amp;student={$user->id}&amp;mode=$mode&amp;printing=yes\" >[".
        get_string('versionforprinting','attforblock').']</a></td>';
        echo '</tr>';
    }
    echo '<tr>';
    echo '<td class="left side">';
    print_user_picture($user->id, $COURSE->id, $user->picture, true);
    echo '</td>';
    echo '<td class="generalboxcontent">';
    echo '<h1><b>'.fullname($user).'</b></h1>';
    if ($course) {
        echo '<hr />';
        $complete = get_attendance($user->id, $course);
        if($complete) {
            print_attendance_table($user,  $course);
        } else {
            echo get_string('nocleareditems','attforblock');
        }
    } else {
        $stqry = "
            SELECT ats.id,ats.courseid
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id}
            GROUP BY ats.courseid
            ORDER BY ats.courseid asc";
        $recs = get_records_sql_menu($stqry);
        foreach ($recs as $id => $courseid) {
            echo '<hr />';
            echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="list1">';
            $nextcourse = get_record('course', 'id', $courseid);
            echo '<tr><td valign="top"><strong>'.$nextcourse->fullname.'</strong></td>';
            echo '<td align="right">';
            $complete = get_attendance($user->id, $nextcourse);
            if($complete) {
                print_attendance_table($user,  $nextcourse);
            } else {
                echo get_string('nocleareditems','attforblock');
            }
            echo '</td></tr>';
            echo '</table>';
        }
    }
    if ($course) {
        $stqry = "
            SELECT ats.sessdate,ats.sessiontitle,ats.subject,ats.teacher,ats.description,al.statusid,al.remarks,al.makeupnotes,al.sicknote
            FROM {$CFG->prefix}attendance_log al
            JOIN {$CFG->prefix}attendance_sessions ats
            ON al.sessionid = ats.id
            WHERE al.studentid = {$user->id} AND al.makeupnotes = 'cleared' AND (al.sicknote = 'cleared' OR al.sicknote = 'notrequired')
            ORDER BY ats.sessdate asc";
        if ($sessions = get_records_sql($stqry)) {
            $statuses = get_statuses($course->id);
            ?>
            <div id="mod-assignment-submissions">
            <table
                align="left"
                cellpadding="3"
                cellspacing="0"
                class="submissions"
            >
                <tr>
                    <th>#</th>
                    <th align="center"><?php print_string('date')?></th>
                    <th align="center"><?php print_string('time')?></th>
                    <th align="center"><?php print_string('status','attforblock')?></th>
                    <th align="center"><?php print_string('sessiontitle','attforblock')?></th>
                    <th align="center"><?php print_string('subject','attforblock')?></th>
                    <th align="center"><?php print_string('teacher','attforblock')?></th>
                    <th align="center"><?php print_string('description','attforblock')?></th>
                    <th align="center"><?php print_string('makeupnotes','attforblock')?></th>
                    <th align="center"><?php print_string('sicknote','attforblock')?></th>
                    <th align="center"><?php print_string('remarks','attforblock')?></th>
                </tr>
                <?php
                $i = 1;
                foreach($sessions as $key=>$session)
                {
                    ?>
                <tr>
                    <td align="center"><?php echo $i++;?></td>
                    <td><?php echo userdate($session->sessdate, get_string('strftimedmyw', 'attforblock')); //userdate($students->sessdate,'%d.%m.%y&nbsp;(%a)', 99, false);?></td>
                    <td><?php echo userdate($session->sessdate, get_string('strftimehm', 'attforblock')); ?></td>
                    <td><?php echo $statuses($session->statusid)->description ?></td>
                    <td><?php echo empty($session->sessiontitle) ? get_string('notitle', 'attforblock') : $session->sessiontitle;  ?></td>
                    <td><?php echo empty($session->subject) ? get_string('nosubject', 'attforblock') : $session->subject;  ?></td>
                    <td><?php echo empty($session->teacher) ? get_string('noteacher', 'attforblock') : $session->teacher;  ?></td>
                    <td><?php echo empty($session->description) ? get_string('nodescription', 'attforblock') : $session->description;  ?></td>
                    <td><?php echo get_string(($session->makeupnotes), 'attforblock');?></td>
                    <td><?php echo get_string(($session->sicknote), 'attforblock');?></td>
                    <td><?php echo $session->remarks;?></td>
                </tr>
                <?php
                }
                echo '</table>';
        } else {
            print_heading(get_string('nocleareditems','attforblock'));
        }
    }
    echo '</td></tr><tr><td>&nbsp;</td></tr></table></div>';
}

?>