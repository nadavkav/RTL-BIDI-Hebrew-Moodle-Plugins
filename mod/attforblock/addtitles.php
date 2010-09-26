<?PHP

require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');

$id           	= required_param('id', PARAM_INT);
$submitsettings	= optional_param('submitsettings');
$action			= optional_param('action', '', PARAM_MULTILANG);
$stid			= optional_param('st', 0, PARAM_INT);

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
$attforblockrecord = get_record('attforblock','course',$course->id);


require_login($course->id);

if (! $user = get_record('user', 'id', $USER->id) ) {
    error("No such user in this course");
}

if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
    print_error('badcontext');
}

require_capability('mod/attforblock:manageattendances', $context);

/// Print headers
$navlinks[] = array('name' => $attforblock->name, 'link' => "view.php?id=$id", 'type' => 'activity');
$navlinks[] = array('name' => get_string('sessiontitles', 'attforblock'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: ".$attforblock->name.' - '.get_string('sessiontitles','attforblock'), $course->fullname,
$navigation, "", '<link type="text/css" href="attforblock.css" rel="stylesheet" />', true, "&nbsp;", navmenu($course));

if (!empty($action)) {
    switch ($action) {
        case 'delete':
            set_field('attendance_sessiontitles', 'deleted', 1, 'id', $stid);
            break;

        default: //Adding new sessiontitle

            $newsessiontitle		= optional_param('newsessiontitle', '', PARAM_MULTILANG);

            if (!empty($newsessiontitle)) {
                unset($rec);
                $rec->courseid = $course->id;
                $rec->sessiontitle = $newsessiontitle;
                insert_record('attendance_sessiontitles', $rec);
                add_to_log($course->id, 'attforblock', 'sessiontitle added', 'addtitles.php?course='.$course->id, $user->lastname.' '.$user->firstname);
            } else {
                print_error('cantaddsessiontitle', 'attforblock', "addtitles.php?id=$id");
            }
            break;
    }
}

show_tabs($cm, $context, 'sessiontitles');

if ($submitsettings) {
    config_save(); //////////////////////////////
    notice(get_string('sessiontitlesupdated','attforblock'), 'addtitles.php?id='.$id);
}

$i = 1;
$table->width = '400px';
$table->head = array('#', get_string('sessiontitle','attforblock'), get_string('action'));
$table->align = array('center', 'center', 'center', 'center');

$sessiontitles = get_sessiontitles($course->id, true);
$deltitle = get_string('delete');

if(count_records_select('attendance_sessiontitles', 'deleted = 0')) {	// check if session titles exist
    foreach($sessiontitles as $st){
        $table->data[$i][] = $i;
        $table->data[$i][] = '<input type="text" name="sessiontitle['.$st->id.']" size="30" maxlength="30" value="'.$st->sessiontitle.'" />';

        $deleteact = "<a title=\"$deltitle\" href=\"addtitles.php?id=$cm->id&amp;st={$st->id}&amp;action=delete\">".
							 "<img src=\"{$CFG->pixpath}/t/delete.gif\" alt=\"$deltitle\" /></a>&nbsp;";

        $table->data[$i][] = $deleteact;
        $i++;
    }}
    $new_row = array('*',
			  '<input type="text" name="newsessiontitle" size="30" maxlength="30" value="" />',
			  '<input type="submit" name="action" value="'.get_string('add', 'attforblock').'" />');

    $table->data[$i] = $new_row;

    echo '<div><div class="generalbox boxwidthwide">';
    echo '<form method="post" action="addtitles.php" onsubmit="return validateSession()">';
    echo '<h1 class="main help">'.get_string('sessiontitles','attforblock').helpbutton ('sessiontitles', get_string('sessiontitles','attforblock'), 'attforblock', true, false, '', true).'</h1>';
    print_table($table);
    echo '<div><input type="hidden" name="id" value="'.$id.'"/></div>';
    echo '<div><div class="submitbutton"><input type="submit" name="submitsettings" value="'.get_string("update",'attforblock').'" /></div></div>';
    echo '</form></div></div>';
    print_footer($course);

    function config_save()
    {
        global $course, $user, $attforblockrecord;
        $sessiontitle	= required_param('sessiontitle');
        foreach ($sessiontitle as $id => $v) {
            $rec = get_record('attendance_sessiontitles', 'id', $id);
            $rec->sessiontitle = $sessiontitle[$id];

            update_record('attendance_sessiontitles', $rec);
            add_to_log($course->id, 'attforblock', 'sessiontitles updated', 'addtitles.php?course='.$course->id, $user->lastname.' '.$user->firstname);
        }
        attforblock_update_grades($attforblockrecord);
    }

    ?>