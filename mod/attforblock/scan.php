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
require_capability('mod/attforblock:takescans', $context);
/// Print headers
$navlinks[] = array('name' => $attforblock->name, 'link' => "view.php?id=$id", 'type' => 'activity');
$navlinks[] = array('name' => get_string('scans', 'attforblock'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: ".$attforblock->name.' - '.get_string('scans','attforblock'), $course->fullname,
$navigation, "", '<link type="text/css" href="attforblock.css" rel="stylesheet" />', true, "&nbsp;", navmenu($course));
if (!empty($action)) {
    switch ($action) {
        default: //Adding new scan
            $newscan = optional_param('newscan', '', PARAM_MULTILANG);
            if (!empty($newscan)) {
                unset($rec);
                $rec->courseid = $course->id;
                $timenow = time();
                $rec->timescanned = $timenow;
                $rec->scan = $newscan;
                $username = ereg_replace("[^A-Za-z]", "", $newscan);
                $password = ereg_replace("[^0-9]", "", $newscan);
                $currentscan = get_records_sql("SELECT un.id, un.username, df.data
                                        FROM {$CFG->prefix}user un 
                                        JOIN {$CFG->prefix}user_info_data df 
                                        ON un.id = df.userid 
                                        WHERE un.username = '$username' 
                                        AND df.data = $password");
                // check if the scan found a valid username and password
                if($currentscan) {
                    echo ' Success!';
                    echo ' Hello ';
                    echo $username.", - Your password is ".$password;
                    $late = 'notset';
                    $leftearly = 'notset';
                    $timepresent = NULL;
                    $finalstatus = NULL;
                    $rec->success = 1;
                    //	let's find out who's record was scanned and save the userid to be logged in the scan_logs table
                    foreach ($currentscan as $scanrecord) {
                        $groups = get_records_sql("
                            SELECT groupid
                            FROM {$CFG->prefix}groups_members  
                            WHERE userid = $scanrecord->id");
                        $rec->studentid = $scanrecord->id;
                        echo 'Your student id no is '.$scanrecord->id;
                        $studentid = $scanrecord->id;
                        // determine whether the student is scanning in or out
                        $lastscanned = get_records_sql("
                            SELECT id, studentid, timescanned, scannedin, courseid, processed 
                            FROM {$CFG->prefix}attendance_scan_logs 
                            WHERE studentid = $studentid 
                            AND courseid = $course->id 
                            AND processed = 0");
                        // check if there has been any previous records scanned by this student
                        if ($lastscanned) {
                            // check the status of the last scan and save the current scan as the opposite (if scanned in then scan out etc)
                            foreach ($lastscanned as $lastscan) {
                                if ($lastscan->scannedin ==1) {
                                    $rec->scannedin = 0;
                                } else {
                                    $rec->scannedin = 1;
                                }
                                // set the previous scan's 'processed' field to 1 (yes)
                                set_field('attendance_scan_logs', 'processed', 1, 'id', $lastscan->id);
                            }
                        }
                    }
                } else {
                    echo 'Failed to log in';
                }
                insert_record('attendance_scan_logs', $rec);
                add_to_log($course->id, 'attforblock', 'scan added', 'scan.php?course='.$course->id, $user->lastname.' '.$user->firstname);
            } else {
                print_error('cantaddscan', 'attforblock', "scan.php?id=$id");
            }
            break;
    }
}
show_tabs($cm, $context, 'scan');
$i = 1;
$table->width = '400px';
$table->head = array('#', get_string('scan','attforblock'));
$table->align = array('center', 'center', 'center');
$scans = get_scans($course->id);
if(count_records_select('attendance_scan_logs')) {	// check if session titles exist
    foreach($scans as $st){
        $table->data[$i][] = $i;
        $table->data[$i][] = userdate($st->timescanned, get_string('strftimehm', 'attforblock'));
        $table->data[$i][] = ereg_replace("[^A-Za-z]", "",$st->scan);
        if($st->scannedin == 0) {
            $table->data[$i][] = 'Out';
        } else {
            $table->data[$i][] = 'In';
        }
        $i++;
    }
}
$new_row = array('*',
'<input type="password" name="newscan" size="30" maxlength="30" value="" />',
'<input type="submit" name="action" value="'.get_string('scan', 'attforblock').'"/>');
$table->data[$i] = $new_row;
echo '<div><div class="generalbox boxwidthwide">';
echo '<form method="post" action="scan.php" onsubmit="return validateSession()">';
echo '<h1 class="main help">'.get_string('scans','attforblock').helpbutton ('scans', get_string('scans','attforblock'), 'attforblock', true, false, '', true).'</h1>';
print_table($table);
echo '<div><input type="hidden" name="id" value="'.$id.'"/></div>';
echo '</form></div></div>';
print_footer($course);
?>