<?PHP

require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$submitsettings	= optional_param('submitsettings');
$action	= optional_param('action', '', PARAM_MULTILANG);
$stid = optional_param('st', 0, PARAM_INT);

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
$navlinks[] = array('name' => get_string('settings', 'attforblock'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: ".$attforblock->name.' - '.get_string('settings','attforblock'), $course->fullname,
$navigation, "", '<link type="text/css" href="attforblock.css" rel="stylesheet" />', true, "&nbsp;", navmenu($course));

if (!empty($action)) {
    switch ($action) {
        case 'delete':
            if (!$rec = get_record('attendance_statuses', 'courseid', $course->id, 'id', $stid)) {
                print_error('notfoundstatus', 'attforblock', "attsettings.php?id=$id");
            }
            if (count_records('attendance_log', 'statusid', $stid)) {
                print_error('cantdeletestatus', 'attforblock', "attsettings.php?id=$id");
            }

            $confirm = optional_param('confirm');
            if (isset($confirm)) {
                set_field('attendance_statuses', 'deleted', 1, 'id', $rec->id);
                redirect('attsettings.php?id='.$id, get_string('statusdeleted','attforblock'), 3);
            }
            print_heading(get_string('deletingstatus','attforblock').' :: ' .$course->fullname);

            notice_yesno(get_string('deletecheckfull', '', get_string('variable', 'attforblock')).
					             '<br /><br />'.$rec->acronym.': '.
            ($rec->description ? $rec->description : get_string('nodescription', 'attforblock')),
			                     "attsettings.php?id=$id&amp;st=$stid&amp;action=delete&amp;confirm=1", $_SERVER['HTTP_REFERER']);
            exit;
        case 'show':
            set_field('attendance_statuses', 'visible', 1, 'id', $stid);
            break;
        case 'hide':
            $students = get_users_by_capability($context, 'moodle/legacy:student', '', '', '', '', '', '', false);
            $studlist = implode(',', array_keys($students));
            if (!count_records_select('attendance_log', "studentid IN ($studlist) AND statusid = $stid")) {
                set_field('attendance_statuses', 'visible', 0, 'id', $stid);
            } else {
                print_error('canthidestatus', 'attforblock', "attsettings.php?id=$id");
            }
            break;
        default: //Adding new status
            $newacronym	= optional_param('newacronym', '', PARAM_MULTILANG);
            $newdescription = optional_param('newdescription', '', PARAM_MULTILANG);
            $newgrade = optional_param('newgrade', 0, PARAM_INT);
            $newmakeupnote = optional_param('newmakeupnote', '', PARAM_MULTILANG);
            $newsicknote = optional_param('newsicknote', '', PARAM_MULTILANG);
            $newstartlogic = optional_param('newstartlogic', 0, PARAM_MULTILANG);
            $newafterstart = optional_param('newafterstart', 0, PARAM_INT);
            $newfinishlogic = optional_param('newfinishlogic', 0, PARAM_MULTILANG);
            $newbeforefinish = optional_param('newbeforefinish', 0, PARAM_INT);
            $newlogicoperator = optional_param('newlogicoperator', 0, PARAM_MULTILANG);
            $newpercentageattended = optional_param('newpercentageattended', 0, PARAM_INT);
            
            if (!empty($newacronym) && !empty($newdescription)) {
                unset($rec);
                $rec->courseid = $course->id;
                $rec->acronym = $newacronym;
                $rec->description = $newdescription;
                $rec->grade = $newgrade;
                $rec->makeupnote = $newmakeupnote;
                $rec->sicknote = $newsicknote;
                $rec->startlogic = $newstartlogic;
                $rec->afterstart = $newafterstart;
                $rec->finishlogic = $newstartlogic;
                $rec->beforefinish = $newbeforefinish;
                $rec->logicoperator = $newlogicoperator;
                $rec->percentageattended = $newpercentageattended;
                insert_record('attendance_statuses', $rec);
                add_to_log($course->id, 'attforblock', 'setting added', 'attsettings.php?course='.$course->id, $user->lastname.' '.$user->firstname);
            } else {
                print_error('cantaddstatus', 'attforblock', "attsettings.php?id=$id");
            }
            break;
    }
}

show_tabs($cm, $context, 'settings');

if ($submitsettings) {
    config_save();
    notice(get_string('variablesupdated','attforblock'), 'attsettings.php?id='.$id);
}

$i = 1;
$table->width = '100%';
$table->head = array('#',
get_string('acronym','attforblock'),
get_string('description'),
get_string('grade'),
get_string('makeupnote', 'attforblock'),
get_string('sicknote', 'attforblock'),
get_string('latearrival','attforblock'),
get_string('leftearly', 'attforblock'),
get_string('percentageattended', 'attforblock'),
get_string('action'));
$table->align = array('center', 'center', 'center', 'center', 'center', 'center', 'center', 'center','center');

$statuses = get_statuses($course->id, false);
$deltitle = get_string('delete');
foreach($statuses as $st)
{
    $table->data[$i][] = $i;
    $table->data[$i][] = '<input type="text" name="acronym['.$st->id.']" size="2" maxlength="2" value="'.$st->acronym.'" />';
    $table->data[$i][] = '<input type="text" name="description['.$st->id.']" size="13" maxlength="13" value="'.$st->description.'" />';
    $table->data[$i][] = '<input type="text" name="grade['.$st->id.']" size="4" maxlength="4" value="'.$st->grade.'" />';

    //	Define the options of the drop down menu for make up note and sicknote
    $optionlist = array(
        			'outstanding' => get_string('outstanding', 'attforblock'),
        			'notrequired' => get_string('notrequired', 'attforblock'),
        			'submitted' => get_string('submitted', 'attforblock'),
        			'cleared' => get_string('cleared', 'attforblock'));

    //	Define the options of the 'logicoperator' field
    $operatoroptions = array('none' => get_string('notrequired', 'attforblock'),
        			'greater' => get_string('greater', 'attforblock'),
        			'less' => get_string('less', 'attforblock')
    );

    //	Print the data of the 'makeupnote', and 'sicknote' fields
    $table->data[$i][] = choose_from_menu($optionlist, 'makeupnote'.'['.$st->id.']', ''.$st->makeupnote.'' ,'', '', '',  true);
    $table->data[$i][] = choose_from_menu($optionlist, 'sicknote'.'['.$st->id.']', ''.$st->sicknote.'' ,'', '', '',  true);
    $table->data[$i][] = choose_from_menu($operatoroptions, 'startlogic'.'['.$st->id.']', ''.$st->startlogic.'' ,'', '', '',  true).'<input type="text" name="afterstart['.$st->id.']" size="3" maxlength="3" value="'.$st->afterstart.'" />'.'m';
    $table->data[$i][] = choose_from_menu($operatoroptions, 'finishlogic'.'['.$st->id.']', ''.$st->finishlogic.'' ,'', '', '',  true).'<input type="text" name="beforefinish['.$st->id.']" size="3" maxlength="3" value="'.$st->beforefinish.'" />'.'m';
    $table->data[$i][] = choose_from_menu($operatoroptions, 'logicoperator'.'['.$st->id.']', ''.$st->logicoperator.'' ,'', '', '',  true).'<input type="text" name="percentageattended['.$st->id.']" size="3" maxlength="3" value="'.$st->percentageattended.'" />'.'%';

    $action = $st->visible ? 'hide' : 'show';
    $titlevis = get_string($action);
    $deleteact = '';
    if (!count_records('attendance_log', 'statusid', $st->id)) {
        $deleteact = "<a title=\"$deltitle\" href=\"attsettings.php?id=$cm->id&amp;st={$st->id}&amp;action=delete\">".
		     "<img src=\"{$CFG->pixpath}/t/delete.gif\" alt=\"$deltitle\" /></a>&nbsp;";
    }
    $table->data[$i][] = "<a title=\"$titlevis\" href=\"attsettings.php?id=$cm->id&amp;st={$st->id}&amp;action=$action\">".
			 "<img src=\"{$CFG->pixpath}/t/{$action}.gif\" alt=\"$titlevis\" /></a>&nbsp;".
    $deleteact;
    $i++;
}
$new_row = array('*',
'<input type="text" name="newacronym" size="2" maxlength="2" value="" />',
'<input type="text" name="newdescription" size="15" maxlength="15" value="" />',
'<input type="text" name="newgrade" size="4" maxlength="4" value="" />'
);
$table->data[$i] = $new_row;
$table->data[$i][] = choose_from_menu($optionlist, 'newmakeupnote', 'notrequired', '', '', '',  true);
$table->data[$i][] = choose_from_menu($optionlist, 'newsicknote', 'notrequired','', '', '',  true);
$table->data[$i][] = choose_from_menu($operatoroptions, 'newstartlogic', '-', '', '', '',  true).'<input type="text" name="newafterstart" size="3" maxlength="3" value="" />'.'m';
$table->data[$i][] = choose_from_menu($operatoroptions, 'newfinishlogic', '-', '', '', '',  true).'<input type="text" name="newbeforefinish" size="3" maxlength="3" value="" />'.'m';
$table->data[$i][] = choose_from_menu($operatoroptions, 'newlogicoperator', '-', '', '', '',  true).'<input type="text" name="newpercentageattended" size="3" maxlength="3" value="" />'.'%';
$table->data[$i][] = '<input type="submit" name="action" value="'.get_string('add', 'attforblock').'"/>';
echo '<div><div class="myvarwidth">';
echo '<form method="post" action="attsettings.php" onsubmit="return validateSession()">';
echo '<h1 class="main help">'.get_string('myvariables','attforblock').helpbutton ('myvariables', get_string('myvariables','attforblock'), 'attforblock', true, false, '', true).'</h1>';
print_table($table);
echo '<div><input type="hidden" name="id" value="'.$id.'"/></div>';
echo '<div><div class="submitbutton"><input type="submit" name="submitsettings" value="'.get_string("update",'attforblock').'"/></div></div>';
echo '</form></div></div>';

print_footer($course);


function config_save()
{
global $course, $user, $attforblockrecord;

$acronym = required_param('acronym');
$description = required_param('description');
$grade = required_param('grade',PARAM_INT);
$makeupnote = required_param('makeupnote', PARAM_MULTILANG);
$sicknote = required_param('sicknote', PARAM_MULTILANG);
$startlogic = required_param('startlogic',PARAM_MULTILANG);
$afterstart = required_param('afterstart',PARAM_INT);
$finishlogic = required_param('finishlogic',PARAM_MULTILANG);
$beforefinish = required_param('beforefinish',PARAM_INT);
$percentageattended = required_param('percentageattended',PARAM_INT);
$logicoperator = required_param('logicoperator',PARAM_MULTILANG);

foreach ($acronym as $id => $v) {
 $rec = get_record('attendance_statuses', 'id', $id);
 $rec->acronym = $acronym[$id];
 $rec->description = $description[$id];
 $rec->grade = $grade[$id];
 $rec->makeupnote = $makeupnote[$id];
 $rec->sicknote = $sicknote[$id];
 $rec->finishlogic = $finishlogic[$id];
 $rec->beforefinish = $beforefinish[$id];
 $rec->logicoperator = $logicoperator[$id];
 $rec->startlogic = $startlogic[$id];
 $rec->afterstart = $afterstart[$id];
 $rec->percentageattended = $percentageattended[$id];

 update_record('attendance_statuses', $rec);
 add_to_log($course->id, 'attforblock', 'settings updated', 'attsettings.php?course='.$course->id, $user->lastname.' '.$user->firstname);
}
attforblock_update_grades($attforblockrecord);
}

 ?>