<?PHP  // $Id: manage.php,v 1.2.2.4 2009/02/28 19:20:14 dlnsk Exp $

/// This page prints a particular instance of attforblock
/// (Replace attforblock with the name of your module)

require_once('../../config.php');
require_once('locallib.php');


$id   = required_param('id', PARAM_INT);   // Course Module ID, or
$from = optional_param('from', PARAM_ACTION);

if (! $cm = get_record('course_modules', 'id', $id)) {
    error('Course Module ID was incorrect');
}

if (! $course = get_record('course', 'id', $cm->course)) {
    error("Course is misconfigured");
}

if (! $attforblock = get_record('attforblock', 'id', $cm->instance)) {
    error("Course module is incorrect");
}

require_login($course->id);

if (! $user = get_record('user', 'id', $USER->id) ) {
    error("No such user in this course");
}

if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
    print_error('badcontext');
}

if (!has_capability('mod/attforblock:manageattendances', $context) AND
!has_capability('mod/attforblock:takeattendances', $context) AND
!has_capability('mod/attforblock:changeattendances', $context)) {
    redirect("view.php?id=$cm->id");
}

//if teacher is coming from block, then check for a session exists for today
if($from === 'block') {
    $today = time(); // because we compare with database, we don't need to use usertime()
    $sql = "SELECT id, lasttaken
                  FROM {$CFG->prefix}attendance_sessions
                 WHERE $today BETWEEN sessdate AND (sessdate + duration)
                   AND courseid = $course->id";
    if($att = get_record_sql($sql)) {
        if ((!$att->lasttaken and has_capability('mod/attforblock:takeattendances', $context)) or
        ($att->lasttaken and has_capability('mod/attforblock:changeattendances', $context))) {
            redirect('attendances.php?id='.$id.'&amp;sessionid='.$att->id);
        }
    }
}

/// Print headers
$navlinks[] = array('name' => $attforblock->name, 'link' => null, 'type' => 'activity');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: ".$attforblock->name, $course->fullname,
$navigation, "", '<link type="text/css" href="attforblock.css" rel="stylesheet" />', true, update_module_button($cm->id, $course->id, get_string('modulename', 'attforblock')),
navmenu($course));

// print_heading(get_string('attendanceforthecourse','attforblock').' :: ' .$course->fullname);  // this messes up the layout

if(!count_records_select('attendance_sessions', "courseid = $course->id AND sessdate >= $course->startdate")) {	// no session exists for this course
    show_tabs($cm, $context);
    print_heading(get_string('nosessionexists','attforblock'));
    $hiddensess = count_records_select('attendance_sessions', "courseid = $course->id AND sessdate < $course->startdate");
    echo '<div>'.helpbutton('hiddensessions', '', 'attforblock', true, true, '', true);
    echo get_string('hiddensessions', 'attforblock').': '.$hiddensess.'</div>';
} else {	//sessions generated , display them
    add_to_log($course->id, 'attforblock', 'manage attendances', 'manage.php?course='.$course->id, $user->lastname.' '.$user->firstname);
    show_tabs($cm, $context);
    print_sessions_list($course);
}
//	require_once('lib.php');
//	$t = attforblock_get_user_grades($attforblock); ////////////////////////////////////////////
print_footer($course);

function print_sessions_list($course) {
    global $CFG, $context, $cm;

    $strhours = get_string('hours');
    $strmins = get_string('min');
    $qry = get_records_select('attendance_sessions', "courseid = $course->id AND sessdate >= $course->startdate", 'sessdate asc');
    $i = 0;
    $table->width = '100%';
    $table->head = array('#', get_string('date'), get_string('time'), get_string('dur', 'attforblock'), get_string('group'), get_string('sessiontitle','attforblock'), get_string('subject','attforblock'), get_string('teacher','attforblock'), get_string('description','attforblock'), get_string('actions'), get_string('select'));
    $table->align = array('', '', '', '', '',  '', '', '','', '', '');
    $table->size = array('1px', '1px', '1px', '1px', '1px', '1px','1px', '1px', '1px', '1px', '1px');
    $allowtake = has_capability('mod/attforblock:takeattendances', $context);
    $allowchange = has_capability('mod/attforblock:changeattendances', $context);
    $allowmanage = has_capability('mod/attforblock:manageattendances', $context);
    foreach($qry as $key=>$sessdata){
        $i++;
        $actions = '';

        if($sessdata->lasttaken > 0)	//attendance has been taken
        {
            if ($allowchange) {

                $sesstitle = empty($sessdata->sessiontitle) ? get_string('nosessiontitle', 'attforblock') : $sessdata->sessiontitle;
                $sessgroup = empty($sessdata->groupid) ? get_string('nogroup', 'attforblock') : groups_get_group_name($sessdata->groupid);
                $sessubject = empty($sessdata->subject) ? get_string('nosubject', 'attforblock') : $sessdata->subject;
                $sesteacher = empty($sessdata->teacher) ? get_string('noteacher', 'attforblock') : $sessdata->teacher;
                $desc = "<a href=\"attendances.php?id=$cm->id&amp;sessionid={$sessdata->id}\">".
                ($sessdata->description ? $sessdata->description : get_string('nodescription', 'attforblock')).'</a>';
            } else {

                $sesstitle = '<i>'.(empty($sessdata->sessiontitle) ? get_string('nosessiontitle', 'attforblock') : $sessdata>sessiontitle).'</i>';
                $sessgroup = '<i>'.(empty($sessdata->groupid) ? get_string('nogroup', 'attforblock') : groups_get_group_name($sessdata->groupid)).'</i>';
                $sessubject = '<i>'.(empty($sessdata->subject) ? get_string('nosubject', 'attforblock') : $sessdata>subject).'</i>';
                $sesteacher = '<i>'.(empty($sessdata->teacher) ? get_string('noteacher', 'attforblock') : $sessdata>teacher).'</i>';
                $desc = '<i>'.(empty($sessdata->description) ? get_string('nodescription', 'attforblock') : $sessdata->description).'</i>';
            }
        } else {

            $sesstitle = empty($sessdata->sessiontitle) ? get_string('nosessiontitle', 'attforblock') : $sessdata->sessiontitle;
            $sessgroup = empty($sessdata->groupid) ? get_string('nogroup', 'attforblock') : groups_get_group_name($sessdata->groupid);
            $sessubject = empty($sessdata->subject) ? get_string('nosubject', 'attforblock') : $sessdata->subject;
            $sesteacher = empty($sessdata->teacher) ? get_string('noteacher', 'attforblock') : $sessdata->teacher;
            $desc = empty($sessdata->description) ? get_string('nodescription', 'attforblock') : $sessdata->description;


            if ($allowtake) {
                $title = get_string('takeattendance','attforblock');
                $actions = "<a title=\"$title\" href=\"attendances.php?id=$cm->id&amp;sessionid={$sessdata->id}\">".
                                       "<img src=\"{$CFG->pixpath}/t/go.gif\" alt=\"$title\" /></a>&nbsp;";
            }
        }

        if($allowmanage) {
            $title = get_string('editsession','attforblock');
            $actions .= "<a title=\"$title\" href=\"sessions.php?id=$cm->id&amp;sessionid={$sessdata->id}&amp;action=update\">".
								"<img src=\"{$CFG->pixpath}/t/edit.gif\" alt=\"$title\" /></a>&nbsp;";
            $title = get_string('deletesession','attforblock');
            $actions .= "<a title=\"$title\" href=\"sessions.php?id=$cm->id&amp;sessionid={$sessdata->id}&amp;action=delete\">".
								"<img src=\"{$CFG->pixpath}/t/delete.gif\" alt=\"$title\" /></a>&nbsp;";
        }

        $table->data[$sessdata->id][] = $i;
        $table->data[$sessdata->id][] = userdate($sessdata->sessdate, get_string('strftimedmyw', 'attforblock'));
        $table->data[$sessdata->id][] = userdate($sessdata->sessdate, get_string('strftimehm', 'attforblock'));
        $hours = floor($sessdata->duration / HOURSECS);
        $mins = floor(($sessdata->duration - $hours * HOURSECS) / MINSECS);
        $mins = $mins < 10 ? "0$mins" : "$mins";
        //$table->data[$sessdata->id][] = $hours ? "{$hours}&nbsp;{$strhours}&nbsp;{$mins}&nbsp;{$strmins}" : "{$mins}&nbsp;{$strmins}";
        $table->data[$sessdata->id][] = $hours ? "{$hours}".':'."{$mins}":'0:'."{$mins}";
        $table->data[$sessdata->id][] = $sessgroup;
        $table->data[$sessdata->id][] = $sesstitle;
        $table->data[$sessdata->id][] = $sessubject;
        $table->data[$sessdata->id][] = $sesteacher;
        $table->data[$sessdata->id][] = $desc;
        $table->data[$sessdata->id][] = $actions;
        $table->data[$sessdata->id][] = '<input type="checkbox" name="sessid['.$sessdata->id.']" />';
        unset($desc, $actions);
    }
    echo '<div><div class="generalbox boxwidthwide">';
    echo "<form method=\"post\" action=\"sessions.php?id={$cm->id}\">"; //&amp;sessionid={$sessdata->id}
    echo '<div>'.helpbutton ('sessions', get_string('help'), 'attforblock', true, true, '', true).'</div>';
    print_table($table);
    $hiddensess = count_records_select('attendance_sessions', "courseid = $course->id AND sessdate < $course->startdate");
    echo '<table width="100%"><tr><td valign="top">';
    echo '<div>'.helpbutton('hiddensessions', '', 'attforblock', true, true, '', true);
    echo get_string('hiddensessions', 'attforblock').': '.$hiddensess.'</div></td>';
    echo '<td><div><div class="withselectedarea"><a href="javascript:checkall();">'.get_string('selectall').'</a> /'.
             ' <a href="javascript:checknone();">'.get_string('deselectall').'</a><br /><br />';
    echo '<strong>'.get_string('withselected', 'quiz').':</strong>&nbsp;';
    if ($allowmanage) {
        $actionlist = array('changedescription' => get_string('changedescription', 'attforblock'),
            			'changeduration' => get_string('changeduration', 'attforblock'),
            			'changegroup' => get_string('changegroup', 'attforblock'),
            			'changesubject' => get_string('changesubject', 'attforblock'),
            			'changeteacher' => get_string('changeteacher', 'attforblock'),
            			'changesessiontitle' => get_string('changesessiontitle', 'attforblock'),
            			'deleteselected' => get_string('delete'));
        choose_from_menu($actionlist, 'action');
        echo '<input type="submit" name="ok" value="'.get_string('ok')."\" />\n";
    } else {
        echo get_string('youcantdo', 'attforblock'); //You can't do anything
    }
    echo '</div></div></td></tr></table>';
    echo '</form></div></div>';

}
?>