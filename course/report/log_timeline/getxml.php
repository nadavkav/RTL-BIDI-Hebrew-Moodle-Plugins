<?php
    require_once('../../../config.php');
    require_once('../../lib.php');

    require_once('timeline.lib.php');
    
    global $CFG;
    
    $id     = optional_param('id', 0, PARAM_INT);// Course ID
    $hostid = optional_param('hostid', 0, PARAM_INT); //Course ID

    $user     = optional_param('user', 0, PARAM_INT); // User to display
    $date     = optional_param('date', 0, PARAM_FILE); // Date to display - number or some string
    $modname  = optional_param('modname', '', PARAM_CLEAN); // course_module->id
    $modid    = optional_param('modid', 0, PARAM_FILE); // number or 'site_errors'
    $modaction= optional_param('modaction', '', PARAM_PATH); // an action as recorded in the logs
    $group    = optional_param('group', -1, PARAM_INT); // Group to display
    
    if ($hostid == $CFG->mnet_localhost_id) {
        if (!$course = get_record('course', 'id', $id) ) {
            echo ERROR;
            error('That\'s an invalid course id'.$id);
        }
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        
    } else {
        $course_stub       = array_pop(get_records_select('mnet_log', " hostid='$hostid' AND course='$id' ", '', '*', '', '1'));
        $course->id        = $id;
        $course->shortname = $course_stub->coursename;
        $course->fullname  = $course_stub->coursename;
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        }
     
    require_capability('moodle/site:viewreports', $context);
        
    if (!$logs = build_logs_array($course, $user, $date, 'l.time DESC', 0, '',
                       $modname, $modid, $modaction, $group)) {
        exit;
        }
    
    $courses = array();

    if ($course->id == SITEID) {
        $courses[0] = '';
        if ($ccc = get_courses('all', 'c.id ASC', 'c.id,c.shortname')) {
            foreach ($ccc as $cc) {
                $courses[$cc->id] = $cc->shortname;
            }
        }
    } else {
        $courses[$courseid] = $course->shortname;
    }

    $totalcount = $logs['totalcount'];
    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    // Make sure that the logs array is an array, even it is empty, to avoid warnings from the foreach.
    if (empty($logs['logs'])) {
        $logs['logs'] = array();
    }

    header("Content-type: text/xml");
    echo("<data>\n");

    foreach ($logs['logs'] as $log) {

        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = get_record('log_display', 'module', $log->module, 'action', $log->action);
            $ldcache[$log->module][$log->action] = $ld;
        }
        if ($ld && is_numeric($log->info)) {
            // ugly hack to make sure fullname is shown correctly
            if (($ld->mtable == 'user') and ($ld->field == sql_concat('firstname', "' '" , 'lastname'))) {
                $log->info = fullname(get_record($ld->mtable, 'id', $log->info), true);
            } else {
                $log->info = get_field($ld->mtable, $ld->field, 'id', $log->info);
            }
        }

        //Filter log->info
        $log->info = format_string($log->info);

        $log->url  = strip_tags(urldecode($log->url));   // Some XSS protection
        $log->info = strip_tags(urldecode($log->info));  // Some XSS protection
        $log->url  = str_replace('&', '&amp;', $log->url); /// XHTML compatibility
        
        $time_xml=getdate($log->time);

        $file_xml=$file_xml."<event \nstart=\"".substr($time_xml['month'],0,3).' '.$time_xml['mday'].' '.
        $time_xml['year'].' '.time_b($time_xml['hours']).':'.time_b($time_xml['minutes']).':'.
        time_b($time_xml['seconds'])." GMT+0100\" \ntitle=\"";
        
        if ($course->id == SITEID) {
            
            if (empty($log->course)) {
                $file_xml=$file_xml.format_string($log->info);
            } else {
                /*echo "<a href=\"{$CFG->wwwroot}/course/view.php?id={$log->course}\">". format_string($courses[$log->course])."</a>\n";*/
                $file_xml=$file_xml."{$courses[$log->course]} ";
            }

        }

        $fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, 
        $course->id)));
        //$image=explode(" ", print_user_picture($log->userid, $log->course, false,true,true,true));
        //$image=explode("\"", $image[3]);
        //$file_xml=$file_xml."$fullname {$log->action}\" \nimage=\"$image[1]\">\n"; // User
        $file_xml=$file_xml."$fullname {$log->action}\" \nimage=\"{$CFG->wwwroot}/user/pix.php/{$log->userid}/f2.jpg\">\n"; // User

        // Link I.p.
        /*$ip_action=explode(" ", link_to_popup_window("/iplookup/index.php?ip=$log->ip&amp;user=$log->userid", 
        'iplookup',$log->ip, 400, 700, null, null, true));
        var_dump($ip_action);
        $link_to=$ip_action[0]." ".$ip_action[2]."".substr($ip_action[8], 4, strlen($ip_action[8]));
        $file_xml=$file_xml.correct_syntax($link_to."<br/>")."\n"; 
        */
        $ip_action = link_to_popup_window("/iplookup/index.php?ip=$log->ip&amp;user=$log->userid", 'iplookup',$log->ip, 400, 700, null, null, true);
        $file_xml = $file_xml.correct_syntax($ip_action).'&lt;br/&gt;'."\n";
  
        // User
        $file_xml=$file_xml.correct_syntax("<a href=\"$CFG->wwwroot/user/view.php?id={$log->userid}&amp;course={$log->course}\">$fullname</a><br/>\n");
 
        // Log Live Action
        /*$action=link_to_popup_window( make_log_url($log->module,$log->url), 'fromloglive',"$log->module $log->action", 
        400, 600, null , null, true);
        $ip_action=explode(" ",$action);
        $link_to=$ip_action[0]." ".$ip_action[2]."".substr($action, strpos($action, ">"), strlen($action));
        $file_xml=$file_xml.correct_syntax($link_to."<br/>")."\n"; */
        $ip_action = link_to_popup_window( make_log_url($log->module,$log->url), 'fromloglive',"$log->module $log->action", 400, 600, null , null, true);
        $file_xml = $file_xml.correct_syntax($ip_action).'&lt;br/&gt;'."\n";

        $file_xml=$file_xml.correct_syntax("<i>{$log->info}</i><br/>\n"); //Info
        $file_xml=$file_xml."</event>\n\n";
        echo($file_xml);
        $file_xml="";
    }
    
    echo("\n</data>");
    
?>
