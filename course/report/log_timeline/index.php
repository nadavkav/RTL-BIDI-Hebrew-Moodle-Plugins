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
    
    $group       = optional_param('group', -1, PARAM_INT); // Group to display
    $user        = optional_param('user', 0, PARAM_INT); // User to display
    $modname     = optional_param('modname', '', PARAM_CLEAN); // course_module->id
    $modid       = optional_param('modid', '', PARAM_FILE); // number or 'site_errors'
    $modaction   = optional_param('modaction', 0 , PARAM_PATH); // an action as recorded in the logs
    $showcourses = optional_param('showcourses', 0, PARAM_INT); // whether to show courses if we're over our limit.
    $showusers   = optional_param('showusers', 0, PARAM_INT); // whether to show users if we're over our limit.

    $height_timeline = optional_param('height', 500, PARAM_INT); // Height of the timeline

    $date = usergetmidnight(time());

    if ($hostid == $CFG->mnet_localhost_id) {
        if (!$course = get_record('course', 'id', $id) ) {
            error('That\'s an invalid course id'.$id);
        }
    } else {
        $course_stub       = array_pop(get_records_select('mnet_log', " hostid='$hostid' AND course='$id' ", '', '*', '', '1'));
        $course->id        = $id;
        $course->shortname = $course_stub->coursename;
        $course->fullname  = $course_stub->coursename;
    }

    require_login($course->id);

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    require_capability('moodle/site:viewreports', $context);

    add_to_log($course->id, "course", "report timeline", "report/log_timeline/index.php?id=$course->id", $course->id);

    $strlogs = 'Timeline';
    $stradministration = get_string('administration');
    $strreports = get_string('reports');

    session_write_close();

    $userinfo = get_string('allparticipants');
    $dateinfo = get_string('alldays');

    if ($user) {
        if (!$u = get_record('user', 'id', $user) ) {
            error('That\'s an invalid user!');
        }
        $userinfo = fullname($u, has_capability('moodle/site:viewfullnames', $context));
    }
    if ($date) {
        $dateinfo = userdate($date, get_string('strftimedaydate'));
    }

    $navlinks = array();

    $navlinks[] = array('name' => $strreports, 'link' => "$CFG->wwwroot/course/report.php?id=$course->id", 'type' => 'misc');
    $navlinks[] = array('name' => $strlogs, 'link' => "index.php?id=$course->id", 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    $printheader = print_header($course->shortname .': '. $strlogs, $course->fullname, $navigation,'','','','','','','', true);

    // For moodle 1.8
    /*print_header($course->shortname .': '. $strlogs, $course->fullname, 
    "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->
    <a href=\"$CFG->wwwroot/course/report.php?id=$course->id\">$strreports</a> ->
    <a href=\"index.php?id=$course->id\">$strlogs</a>",'','','','','','','', true);*/
            
    $printheader = explode('</head>', $printheader);
    $printheader[0] .= "<script type=\"text/javascript\" src=\"$CFG->wwwroot/course/report/log_timeline/api_timeline/timeline-api.js\"></script>\n";
    echo implode('</head>', $printheader);

    print_heading_modified(format_string($course->fullname) . " : $userinfo : $dateinfo (".usertimezone().")", "title_timeline");

    print_mnet_log_selector_form($hostid, $course, $user, $date, $modname, $modid, $modaction, $group, $showcourses, $showusers);

    $logs = build_logs_array($course, $user, $date, '', 0, '', $modname, $modid, $modaction, $group);
    if (!$logs) { notify("No logs found!"); }
    else 
    {
        //Print the timeline
        $getaxml="getxml.php?id=".$id."&hostid=".$hostid."&user=".$user."&date=".$date.
        "&modname=".$modname."&modid=".$modid."&modaction=".$modaction."&group=".$group;

        if ($height_timeline < 0) { $height_timeline = 500;}

        echo '<div id="my-timeline" style="height: '.$height_timeline.'px; border: 1px solid #aaa"></div>';

?>

<script type="text/javascript">
<!--
var file_xml="";
file_xml="<?php echo($getaxml); ?>";

//-->
</script>
<script type="text/javascript" src="<?php echo($CFG->wwwroot);?>/course/report/log_timeline/timeline_processing.js"></script>

<?php            
    }
  
    print_footer($course);

    exit;
?>
