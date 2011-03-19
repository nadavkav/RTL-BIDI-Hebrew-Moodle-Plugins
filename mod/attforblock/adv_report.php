<?PHP // $Id: adv_report.php,v 1.1.2.4 2009/02/28 16:49:17 dlnsk Exp $

require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once('locallib.php');
require_once('reportlib.php');
require_once('advanced_report_form.php');

$action = optional_param('action', PARAM_ACTION);
$id = required_param('id', PARAM_INT);
//$group = optional_param('group', -1, PARAM_INT);              // Group to show
$view = optional_param('view', 'weeks', PARAM_ALPHA);        // which page to show
$current = optional_param('current', 0, PARAM_INT);
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$studentselected = optional_param('studentselected', '0', PARAM_ALPHA);
$makeupnoteselected = optional_param('makeupnoteselected', 'all', PARAM_ALPHA);
$sicknoteselected = optional_param('sicknoteselected', 'all', PARAM_ALPHA);
$teacherselected = optional_param('teacherselected', '0', PARAM_ALPHA);
$subjectselected = optional_param('subjectselected', '0', PARAM_ALPHA);
$statusselected	= optional_param('statusselected', '0', PARAM_ALPHA);
$datefrom = optional_param('datefrom', '0', PARAM_INT);
$dateto = optional_param('dateto', '0', PARAM_INT);
$courseselected = optional_param('courseselected', -1, PARAM_INT);              // Course to show
$groupselected = optional_param('groupselected', -1, PARAM_INT);              // Course to show
$reportselected = optional_param('reportselected', 'all', PARAM_ALPHA); // Report type (Summary/Detail/All)

if ($id) {
    if (! $cm = get_record('course_modules', 'id', $id)) {
        error('Course Module ID was incorrect');
    }
    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }
    if (! $attforblock = get_record('attforblock', 'id', $cm->instance)) {
        error("Course module is incorrect");
    }
}

require_login($course->id);

    
if (! $user = get_record('user', 'id', $USER->id) ) {
    error("No such user in this course");
}

if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
    print_error('badcontext');
}

require_capability('mod/attforblock:viewreports', $context);

//add info to log
add_to_log($course->id, 'attendance', 'report displayed', 'mod/attforblock/adv_report.php?id='.$id, $user->lastname.' '.$user->firstname);

/// Print headers
$navlinks[] = array('name' => $attforblock->name, 'link' => "view.php?id=$id", 'type' => 'activity');
$navlinks[] = array('name' => get_string('report', 'attforblock'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

require_js('updatestatus.js');
require_js('showclass.js');

print_header("$course->shortname: ".$attforblock->name.' - ' .get_string('report','attforblock'), $course->fullname,
$navigation, "", '<link type="text/css" href="attforblock.css" rel="stylesheet" />', true, "&nbsp;", navmenu($course));

show_tabs($cm, $context, 'advancedreport');
echo '<div>'.helpbutton ('report', get_string('help'), 'attforblock', true, true, '', true).'</div>';
$sort = $sort == 'firstname' ? 'firstname' : 'lastname';

if(!count_records('attendance_sessions', 'courseid', $course->id)) {	// no session exists for this course
    redirect("sessions.php?id=$cm->id&amp;action=add");
} else {// display attendance report
  //  require_once('scanlib.php'); // to be used somewhere when this works
  //  Print the filter form in a table
    echo '<table class="generaltable"><thead><tr>';
    echo '<th class ="alignleft" id="tshowfilter" name="tshowfilter">';
    $mform_report = new mod_attforblock_report_form('adv_report.php', array('course'=>$course, 'cm'=>$cm, 'modcontext'=>$context));
    $mform_report->display();
?>
</th></tr></thead><tbody><tr><td></td></tr></tbody></table>
<?php echo get_string('showcolumns','attforblock'); ?>
<form name="tcol" onsubmit="return false">
    <input type="checkbox" name="showfilter" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('reportfilter', 'attforblock'); ?><br></br>
    <input type="checkbox" name="showgroupby" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('groupedbyheadings', 'attforblock'); ?><br></br>
    <input type="checkbox" name="showstudent" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('student', 'attforblock'); ?>
    <input type="checkbox" name="showcourse" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('course'); ?>
    <input type="checkbox" name="showstatus" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('status', 'attforblock'); ?>
    <input type="checkbox" name="showtitle" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('sessiontitles', 'attforblock'); ?>
    <input type="checkbox" name="showsubject" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('subject', 'attforblock'); ?>
    <input type="checkbox" name="showteacher" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('teacher', 'attforblock'); ?>
    <input type="checkbox" name="showdescription" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('description', 'attforblock'); ?>
    <input type="checkbox" name="showmakeupnotes" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('makeupnotes', 'attforblock'); ?>
    <input type="checkbox" name="showsicknotes" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('sicknotes', 'attforblock'); ?>
    <input type="checkbox" name="showremarks" onclick="toggleVis(this.name)" checked="checked"><?PHP echo get_string('remarks', 'attforblock'); ?>
</form>
<hr>
</br>
    <div id="txtHint"></div>
<?PHP

    if ($fromform = $mform_report->get_data()) {
        $datefrom = $fromform->fdatefrom;
        $dateto = $fromform->fdateto;
        $sort = $fromform->sortmenu;
        $studentselected = $fromform->studentmenu;
        $makeupnoteselected = $fromform->makeupnotemenu;
        $sicknoteselected = $fromform->sicknotemenu;
        $teacherselected = $fromform->teachermenu;
        $subjectselected = $fromform->subjectmenu;
        $statusselected = $fromform->statusmenu;
        $courseselected = $fromform->coursemenu;
        $groupselected = $fromform->groupmenu;
        $reportselected	= $fromform->reporttype;
        set_field('attendance_report_query','datefrom', $datefrom, 'id',0);
        set_field('attendance_report_query','dateto', $dateto, 'id',0);
        set_field('attendance_report_query','sortby', $sort, 'id',0);
        set_field('attendance_report_query','student', $studentselected, 'id',0);
        set_field('attendance_report_query','makeupnote', $makeupnoteselected, 'id',0);
        set_field('attendance_report_query','sicknote', $sicknoteselected, 'id',0);
        set_field('attendance_report_query','teacher', $teacherselected, 'id',0);
        set_field('attendance_report_query','subject', $subjectselected, 'id',0);
        set_field('attendance_report_query','status', $statusselected, 'id',0);
        set_field('attendance_report_query','course', $courseselected, 'id',0);
        set_field('attendance_report_query','groupid', $groupselected, 'id',0);
        set_field('attendance_report_query','reporttype', $reportselected, 'id',0);

    } else {
        $dateto = date(time());
        $datefrom = $dateto -86400;
    }
    $datecondition = " AND ats.sessdate >= $datefrom AND ats.sessdate <= $dateto";

    //	add the current group to the WHERE clause of the report
    if ($groupselected === '-1') {
        $groupcondition = '';
    } else {
        $groupcondition = " AND ats.groupid = ".$groupselected;
        $currentgroup = $groupselected;
    }

    if ($currentgroup) {
        $students = get_users_by_capability($context, 'moodle/legacy:student', '', "u.$sort ASC", '', '', $currentgroup, '', false);
    } else {
        $students = get_users_by_capability($context, 'moodle/legacy:student', '', "u.$sort ASC", '', '', '', '', false);
    }

    $statuses = get_statuses($course->id);
    $allstatuses = get_statuses($course->id, false);

    //	add the current student to the WHERE clause of the report
    if ($studentselected === '0') {
        $studentcondition = '';
    } else {
        $studentcondition = " AND al.studentid = ".$studentselected;
    }

    //	add the current student to the WHERE clause of the report
    if ($courseselected === '-1') {
        $coursecondition = " ats.courseid > -1";
    } else {
        $coursecondition = " ats.courseid = ".$courseselected;
    }

    //	add the current makeupnote to the WHERE clause of the report
    if ($makeupnoteselected === 'all') {
        $makeupnotecondition = '';
    } else {
        $makeupnotecondition = " AND al.makeupnotes = '".$makeupnoteselected."'";
    }

    //	add the current sickeupnote to the WHERE clause of the report
    if ($sicknoteselected === 'all') {
        $sicknotecondition = '';
    } else {
        $sicknotecondition = " AND al.sicknote = '".$sicknoteselected."'";
    }

    //	add the current teacher to the WHERE clause of the report
    if ($teacherselected === '-1') {
        $teachercondition = '';
    } else {
        $teachercondition = " AND ats.teacher = '".$teacherselected."'";
    }

    //	add the current subject to the WHERE clause of the report
    if ($subjectselected === '-1') {
        $subjectcondition = '';
    } else {
        $subjectcondition = " AND ats.subject = '".$subjectselected."'";
    }

    //	add the current status to the WHERE clause of the report
    if ($statusselected === '-1') {
        $statuscondition = '';
    } else {
        $statuscondition = " AND al.statusid = ".$statusselected;
    }

    $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
    $where = "courseid={$course->id} AND sessdate >= $course->startdate AND sessdate <= ".time();

    if ($action === 'update') {
        $allowtake = has_capability('mod/attforblock:takeattendances', $context);
        $allowchange = has_capability('mod/attforblock:changeattendances', $context);

        if ($reportselected === 'detailed'){
	?>
	<table class="generaltable">
	    <tr>
	        <th id="tshowstudent" name="tshowstudent"><?php print_string('student','attforblock')?></th>
	        <th id="tshowcourse" name="tshowcourse"><?php print_string('course')?></th>
	        <th><?php print_string('date')?></th>
	        <th id="tshowstatus" name="tshowstatus"><?php print_string('status','attforblock')?></th>
	        <th id="tshowtitle" name="tshowtitle"><?php print_string('sessiontitle','attforblock')?></th>
	        <th id="tshowsubject" name="tshowsubject"><?php print_string('subject','attforblock')?></th>
	        <th id="tshowteacher" name="tshowteacher"><?php print_string('teacher','attforblock')?></th>
	        <th id="tshowdescription" name="tshowdescription"><?php print_string('description','attforblock')?></th>
	        <th id="tshowmakeupnotes" name="tshowmakeupnotes"><?php print_string('makeupnotes','attforblock')?></th>
	        <th id="tshowsicknotes" name="tshowsicknotes"><?php print_string('sicknote','attforblock')?></th>
	        <th id="tshowremarks" name="tshowremarks"><?php print_string('remarks','attforblock')?></th>
	    </tr>
	<?php
	}

        // if all students are selected
        if ($studentselected === '0')  {
            foreach($students as $student) {
                $studentselected = $student->id;
                $studentcondition = " AND al.studentid = ".$studentselected;
                $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
                if ($reportselected === 'all' OR $reportselected === 'summary'){
                    // print the student picture and name
                    echo '<table><tr><th id="tshowgroupby" name="tshowgroupby">'.
                     print_user_picture($studentselected, $course->id, $student->picture, 20, true, true).
		     "<a href=\"view.php?id=$id&amp;student={$student->id}\">".' '.fullname($student).
		     '</a></th></tr></table>';
		 }

                // show all courses for all students
                if ($courseselected === '-1') {
                    $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
                    foreach($courses as $course) {
                        $coursecondition = " ats.courseid =".$course->id;
                        $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
                        if ($reportselected === 'all' OR $reportselected === 'summary'){
                        echo '<table><tr><th id="tshowgroupby" name="tshowgroupby">'."<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></th></tr></table>';
                        }
                        if ($reportselected === 'all' OR $reportselected === 'summary'){
                            echo '<table class="generaltable"><thead><tr>';
                            echo '<th id="tshowstudent" name="tshowstudent">'.get_string('student','attforblock').'</th>';
                            echo '<th id="tshowcourse" name="tshowcourse">'.get_string('course').'</th>';
                            $statuses = get_statuses($course->id);
                            foreach($statuses as $st) {
                                echo '<th>'.$st->description.'</th>';
                            }
                            echo '<th>'.get_string('grade').'</th>';
                            echo '<th>%</th></tr><tr><td></td>';
                            echo '</tr></thead>';
                            echo '<tbody><tr>';
                            echo '<td id="tshowstudent" name="tshowstudent">';
                            echo "<a href=\"view.php?id=$id&amp;student={$student->id}\">".fullname($student);
                            echo '</a></td><td id="tshowcourse" name="tshowcourse">';
       	                    echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></td>';
                            foreach($statuses as $st) {
                                echo '<td>'.get_attendance($student->id, $course, $st->id).'</td>';
                            }
                            echo '<td>'.get_grade($studentselected, $course).'&nbsp;/&nbsp;'.get_maxgrade($student->id, $course).'</td>';
                            echo '<td>'.get_percent($studentselected, $course).'%'.'</td>';
                            echo '</tr></tbody></table>';
                        }
                        if ($reportselected === 'all' OR $reportselected === 'detailed'){
                            print_detailed_report($studentselected, $cm, $course->id);
                        }
                    }
                    // show only the selected course for all students
                } else {
                    $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
                    foreach($courses as $course) {
                        $coursecondition = " ats.courseid =".$course->id;
                        $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
                        if($course->id === $courseselected) {
                            if ($reportselected === 'all' OR $reportselected === 'summary'){
                            echo '<table><tr><th id="tshowgroupby" name="tshowgroupby">'."<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></th></tr></table>';
                            }
                            if ($reportselected === 'all' OR $reportselected === 'summary'){
                                echo '<table class="generaltable"><thead><tr>';
                                echo '<th id="tshowstudent" name="tshowstudent">'.get_string('student','attforblock').'</th>';
                                echo '<th id="tshowcourse" name="tshowcourse">'.get_string('course').'</th>';
                                $statuses = get_statuses($course->id);
                                foreach($statuses as $st) {
                                    echo '<th>'.$st->description.'</th>';
                                }
                                echo '<th>'.get_string('grade').'</th>';
                                echo '<th>%</th></tr><tr><td></td>';
                                echo '</tr></thead>';
                                echo '<tbody><tr>';
                                echo '<td id="tshowstudent" name="tshowstudent">';
                                echo "<a href=\"view.php?id=$id&amp;student={$student->id}\">".fullname($student);
                                echo '</a></td><td id="tshowcourse" name="tshowcourse">';
       	                        echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></td>';
                                foreach($statuses as $st) {
                                    echo '<td>'.get_attendance($student->id, $course, $st->id).'</td>';
                                }
                                echo '<td>'.get_grade($studentselected, $course).'&nbsp;/&nbsp;'.get_maxgrade($student->id, $course).'</td>';
                                echo '<td>'.get_percent($studentselected, $course).'%'.'</td>';
                                echo '</tr></tbody></table>';
                            }
                            if ($reportselected === 'all' OR $reportselected === 'detailed'){
                                print_detailed_report($studentselected, $cm, $course->id);
                            }
                        }
                    }
                }
            }
        } else {
                // if only one student is selected

                $student = get_record_select('user', "id = $studentselected");
                // print the student picture and name
                if ($reportselected === 'all' OR $reportselected === 'summary'){
                echo '<table><tr><th id="tshowgroupby" name="tshowgroupby">'.
                     print_user_picture($studentselected, $course->id, $student->picture, 10, true, true).
		     "<a href=\"view.php?id=$id&amp;student={$student->id}\">".' '.fullname($student).
		     '</a></th></tr></table>';
		}

                if ($reportselected === 'all'){
		?>
		<table class="generaltable">
		    <tr>
		        <th id="tshowstudent" name="tshowstudent"><?php print_string('student','attforblock')?></th>
		        <th id="tshowcourse" name="tshowcourse"><?php print_string('course')?></th>
		        <th><?php print_string('date')?></th>
		        <th id="tshowstatus" name="tshowstatus"><?php print_string('status','attforblock')?></th>
		        <th id="tshowtitle" name="tshowtitle"><?php print_string('sessiontitle','attforblock')?></th>
		        <th id="tshowsubject" name="tshowsubject"><?php print_string('subject','attforblock')?></th>
		        <th id="tshowteacher" name="tshowteacher"><?php print_string('teacher','attforblock')?></th>
		        <th id="tshowdescription" name="tshowdescription"><?php print_string('description','attforblock')?></th>
		        <th id="tshowmakeupnotes" name="tshowmakeupnotes"><?php print_string('makeupnotes','attforblock')?></th>
		        <th id="tshowsicknotes" name="tshowsicknotes"><?php print_string('sicknote','attforblock')?></th>
		        <th id="tshowremarks" name="tshowremarks"><?php print_string('remarks','attforblock')?></th>
		    </tr>
		<?php
		}

                // show all courses for all students
                if ($courseselected === '-1') {
                    $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
                    foreach($courses as $course) {
                        $coursecondition = " ats.courseid =".$course->id;
                        $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
                        if ($reportselected === 'all' OR $reportselected === 'summary'){
                            echo '<table class="generaltable"><thead><tr>';
                            echo '<th id="tshowstudent" name="tshowstudent">'.get_string('student','attforblock').'</th>';
                            echo '<th id="tshowcourse" name="tshowcourse">'.get_string('course').'</th>';
                            $statuses = get_statuses($course->id);
                            foreach($statuses as $st) {
                                echo '<th>'.$st->description.'</th>';
                            }
                            echo '<th>'.get_string('grade').'</th>';
                            echo '<th>%</th></tr><tr><td></td>';
                            echo '</tr></thead>';
                            echo '<tbody><tr>';
                            echo '<td id="tshowstudent" name="tshowstudent">';
                            echo "<a href=\"view.php?id=$id&amp;student={$student->id}\">".fullname($student);
                            echo '</a></td><td id="tshowcourse" name="tshowcourse">';
       	                    echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></td>';
                            foreach($statuses as $st) {
                                echo '<td>'.get_attendance($student->id, $course, $st->id).'</td>';
                            }
                            echo '<td>'.get_grade($studentselected, $course).'&nbsp;/&nbsp;'.get_maxgrade($student->id, $course).'</td>';
                            echo '<td>'.get_percent($studentselected, $course).'%'.'</td>';
                            echo '</tr></tbody></table>';
                        }
                        if ($reportselected === 'all' OR $reportselected === 'detailed'){
                            print_detailed_report($studentselected, $cm, $course->id);
                        }
                    }
                    // show only the selected course for all students
                } else {
                    $courses = get_my_courses($USER->id, 'fullname ASC, sortorder ASC,visible DESC', '*', false, 21);
                    foreach($courses as $course) {
                        $coursecondition = " ats.courseid =".$course->id;
                        $whereclause = $coursecondition.$groupcondition.$datecondition.$makeupnotecondition.$sicknotecondition.$studentcondition.$teachercondition.$subjectcondition.$statuscondition;
                        if($course->id === $courseselected) {
                        if ($reportselected === 'all' OR $reportselected === 'summary'){
                            echo '<table class="generaltable"><thead><tr>';
                            echo '<th id="tshowstudent" name="tshowstudent">'.get_string('student','attforblock').'</th>';
                            echo '<th id="tshowcourse" name="tshowcourse">'.get_string('course').'</th>';
                            $statuses = get_statuses($course->id);
                            foreach($statuses as $st) {
                                echo '<th>'.$st->description.'</th>';
                            }
                            echo '<th>'.get_string('grade').'</th>';
                            echo '<th>%</th></tr><tr><td></td>';
                            echo '</tr></thead>';
                            echo '<tbody><tr>';
                            echo '<td id="tshowstudent" name="tshowstudent">';
                            echo "<a href=\"view.php?id=$id&amp;student={$student->id}\">".fullname($student);
                            echo '</a></td><td id="tshowcourse" name="tshowcourse">';
       	                    echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></td>';
                            foreach($statuses as $st) {
                                echo '<td>'.get_attendance($student->id, $course, $st->id).'</td>';
                            }
                            echo '<td>'.get_grade($studentselected, $course).'&nbsp;/&nbsp;'.get_maxgrade($student->id, $course).'</td>';
                            echo '<td>'.get_percent($studentselected, $course).'%'.'</td>';
                            echo '</tr></tbody></table>';
                        }
                            if ($reportselected === 'all' OR $reportselected === 'detailed'){
                                echo '<h3>'."<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">".$course->fullname.'</a></h3>';
                                print_detailed_report($studentselected, $cm, $course->id);
                            }
                        }
                    }
                }
            }
            if ($reportselected === 'detailed'){
                echo '</table>';
            }
    }

    print_footer($course);

    exit;
}
?>