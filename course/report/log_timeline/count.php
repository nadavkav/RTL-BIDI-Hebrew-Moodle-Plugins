<?php 

    require_once('../../../config.php');
    require_once('../../lib.php');
    require_once('lib.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/weblib.php');

    $id          = optional_param('id', 0, PARAM_INT);// Course ID
    
    $host_course = optional_param('host_course', '', PARAM_PATH);// Course ID
    
    if (empty($host_course)) {
        $hostid = $CFG->mnet_localhost_id;
        if (empty($id)) {
            $site = get_site();
            $id = $site->id;
        }
    } else {
        list($hostid, $id) = explode('/', $host_course);
    }
    
    $user     = optional_param('user', 0, PARAM_INT); // User to display
    $date     = optional_param('date', 0, PARAM_FILE); // Date to display - number or some string
    $modid    = optional_param('modid', 0, PARAM_FILE); // number or 'site_errors'
    $modaction= optional_param('modaction', '', PARAM_PATH); // an action as recorded in the logs
    $group    = optional_param('group', -1, PARAM_INT); // Group to display


    if ($hostid == $CFG->mnet_localhost_id) {
        if (!$course = get_record('course', 'id', $id) ) {
            error('That\'s an invalid course id'.$id);
        }
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/site:viewreports', $context);
    } else {
        $course_stub       = array_pop(get_records_select('mnet_log', " hostid='$hostid' AND course='$id' ", '', '*', '', '1'));
        $course->id        = $id;
        $course->shortname = $course_stub->coursename;
        $course->fullname  = $course_stub->coursename;
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/site:viewreports', $context);
    }

    $logs = build_logs_array($course, $user, $date, '', 0, '', '', $modid, $modaction, $group);
    if (!$logs) { notify("No logs found!"); }
    else 
    {
        $totalcount = $logs['totalcount'];
        echo '<html><head><title>Count events</title></head><body>';
        echo "<div style=\"text-align: center;\" class=\"info\">\n";
        print_string("displayingrecords", "", $totalcount);
        echo '</div></body>';
        echo '</html>';
    }

?>