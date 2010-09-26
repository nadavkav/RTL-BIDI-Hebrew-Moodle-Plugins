<?php  // $Id: lib.php,v 1.0  Exp $

// are we in rtl or ltr mode? for table alignment
right_to_left() ? $strrtltablealignment = 'right' : $strrtltablealignment = 'left';

function print_mnet_log_selector_form1($hostid, $course, $selecteduser=0, $selecteddate='today',$selecteddate1='today',
                                 $modname="", $modid=0, $modaction='', $selectedgroup=-1, $showcourses=0, $showusers=0, $logformat='showashtml') {

    global $USER, $CFG, $SITE;
    require_once $CFG->dirroot.'/mnet/peer.php';

// are we in rtl or ltr mode? for table alignment
right_to_left() ? $strrtltablealignment = 'right' : $strrtltablealignment = 'left';
    
    $mnet_peer = new mnet_peer();
    $mnet_peer->set_id($hostid);

    $sql = "select distinct course, hostid, coursename from {$CFG->prefix}mnet_log";
    $courses = get_records_sql($sql);
    $remotecoursecount = count($courses);

    // first check to see if we can override showcourses and showusers
    $numcourses = $remotecoursecount + count_records_select("course", "", "COUNT(id)");
    if ($numcourses < COURSE_MAX_COURSES_PER_DROPDOWN && !$showcourses) {
        $showcourses = 1;
    }
    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    
    // Context for remote data is always SITE
    // Groups for remote data are always OFF
    if ($hostid == $CFG->mnet_localhost_id) {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        /// Setup for group handling.
        if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            $selectedgroup = get_current_group($course->id);
            $showgroups = false;
        }
        else if ($course->groupmode) {
            $selectedgroup = ($selectedgroup == -1) ? get_current_group($course->id) : $selectedgroup;
            $showgroups = true;
        }
        else {
            $selectedgroup = 0;
            $showgroups = false;
        }

    } else {
        $context = $sitecontext;
    }

    // Get all the possible users
    $users = array();

    // Define limitfrom and limitnum for queries below
    // If $showusers is enabled... don't apply limitfrom and limitnum
    $limitfrom = empty($showusers) ? 0 : '';
    $limitnum  = empty($showusers) ? COURSE_MAX_USERS_PER_DROPDOWN + 1 : '';

    // If looking at a different host, we're interested in all our site users
    if ($hostid == $CFG->mnet_localhost_id && $course->id != SITEID) {
        if ($selectedgroup) {   // If using a group, only get users in that group.
            $courseusers = get_group_users($selectedgroup, 'u.lastname ASC', '', 'u.id, u.firstname, u.lastname, u.idnumber', $limitfrom, $limitnum);
        } else {
            $courseusers = get_course_users($course->id, '', '', 'u.id, u.firstname, u.lastname, u.idnumber', $limitfrom, $limitnum);
        }
    } else {
        $courseusers = get_site_users("u.lastaccess DESC", "u.id, u.firstname, u.lastname, u.idnumber", '', $limitfrom, $limitnum);
    }

    if (count($courseusers) < COURSE_MAX_USERS_PER_DROPDOWN && !$showusers) {
        $showusers = 1;
    }

    if ($showusers) {
        if ($courseusers) {
            foreach ($courseusers as $courseuser) {
                $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
            }
        }
        if ($guest = get_guest()) {
            $users[$guest->id] = fullname($guest);
        }
    }

    // Get all the hosts that have log records
    $sql = "select distinct
                h.id,
                h.name
            from
                {$CFG->prefix}mnet_host h,
                {$CFG->prefix}mnet_log l
            where
                h.id = l.hostid
            order by
                h.name";

    if ($hosts = get_records_sql($sql)) {
        foreach($hosts as $host) {
            $hostarray[$host->id] = $host->name;
        }
    }

    $hostarray[$CFG->mnet_localhost_id] = $SITE->fullname;
    asort($hostarray);

    foreach($hostarray as $hostid => $name) {
        $courses = array();
        $sites = array();
        if ($CFG->mnet_localhost_id == $hostid) {
            if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
                if ($ccc = get_records("course", "", "", "fullname","id,fullname,category")) {
                    foreach ($ccc as $cc) {
                        if ($cc->id == SITEID) {
                            $sites["$hostid/$cc->id"]   = format_string($cc->fullname).' ('.get_string('site').')';
                        } else {
                            $courses["$hostid/$cc->id"] = format_string($cc->fullname);
                        }
                    }
                }
            }
        } else {
            if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
                $sql = "select distinct course, coursename from {$CFG->prefix}mnet_log where hostid = '$hostid'";
                if ($ccc = get_records_sql($sql)) {
                    foreach ($ccc as $cc) {
                        if (1 == $cc->course) { // TODO: this might be wrong - site course may have another id
                            $sites["$hostid/$cc->course"]   = $cc->coursename.' ('.get_string('site').')';
                        } else {
                            $courses["$hostid/$cc->course"] = $cc->coursename;
                        }
                    }
                }
            }
        }

        asort($courses);
        $dropdown[$name] = $sites + $courses;
    }


    $activities = array();
    $selectedactivity = "";

/// Casting $course->modinfo to string prevents one notice when the field is null
    if ($modinfo = unserialize((string)$course->modinfo)) {
        $section = 0;
        if ($course->format == 'weeks') {  // Bodgy
            $strsection = get_string("week");
        } else {
            $strsection = get_string("topic");
        }
        foreach ($modinfo as $mod) {
            if ($mod->mod == "label") {
                continue;
            }
            if ($mod->section > 0 and $section <> $mod->section) {
                $activities["section/$mod->section"] = "-------------- $strsection $mod->section --------------";
            }
            $section = $mod->section;
            $mod->name = strip_tags(format_string(urldecode($mod->name),true));
            if (strlen($mod->name) > 55) {
                $mod->name = substr($mod->name, 0, 50)."...";
            }
            if (!$mod->visible) {
                $mod->name = "(".$mod->name.")";
            }
            $activities["$mod->cm"] = $mod->name;

            if ($mod->cm == $modid) {
                $selectedactivity = "$mod->cm";
            }
        }
    }

    if (has_capability('coursereport/log:view', $sitecontext) && !$course->category) {
        $activities["site_errors"] = get_string("siteerrors");
        if ($modid === "site_errors") {
            $selectedactivity = "site_errors";
        }
    }

    $strftimedate = get_string("strftimedate");
    $strftimedaydate = get_string("strftimedaydate");

    asort($users);

    // Prepare the list of action options.
    $actions = array(
        'view' => get_string('view'),
        'add' => get_string('add'),
        'update' => get_string('update'),
        'delete' => get_string('delete'),
        '-view' => get_string('allchanges')
    );

    // Get all the possible dates
    // Note that we are keeping track of real (GMT) time and user time
    // User time is only used in displays - all calcs and passing is GMT

    $timenow = time(); // GMT

    // What day is it now for the user, and when is midnight that day (in GMT).
    $timemidnight = $today = usergetmidnight($timenow);

    // Put today up the top of the list
    $dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate) );

    if (!$course->startdate or ($course->startdate > $timenow)) {
        $course->startdate = $course->timecreated;
    }

    $numdates = 1;
    while ($timemidnight > $course->startdate and $numdates < 365) {
        $timemidnight = $timemidnight - 86400;
        $timenow = $timenow - 86400;
        $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
        $numdates++;
    }

    if ($selecteddate == "today") {
        $selecteddate = $today;
    }
    if ($selecteddate1 == "today") {
        $selecteddate1 = $today;
    }
    echo "<form class=\"logselectform\" action=\"$CFG->wwwroot/course/report/dates/index.php\" method=\"get\">\n";
    echo '<table align="center"><tr>';
    echo "<div>\n";//invisible fieldset here breaks wrapping
    echo "<input type=\"hidden\" name=\"chooselog\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"showusers\" value=\"$showusers\" />\n";
    echo "<input type=\"hidden\" name=\"showcourses\" value=\"$showcourses\" />\n";
    if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
	    $cid = empty($course->id)? '1' : $course->id; 
        echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('course').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu_nested($dropdown, "host_course", $hostid.'/'.$cid, "");
	echo '</tD>';   
    } else {
        $courses = array();
	$courses[$course->id] = $course->fullname . ((empty($course->category)) ? ' ('.get_string('site').') ' : '');
        echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('course').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu($courses,"id",$course->id,false);
	echo '</td>';
        if (has_capability('coursereport/log:view', $sitecontext)) {
            $a = new object();
            $a->url = "$CFG->wwwroot/course/report/dates/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
                ."&id=$course->id&date=$selecteddate&date1=$selecteddate1&modid=$selectedactivity&showcourses=1&showusers=$showusers";
            print_string('logtoomanycourses','moodle',$a);
        }
    }

    if ($showgroups) {
        if ($cgroups = groups_get_all_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        else {
            $groups = array();
	}
	echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('group').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu ($groups, "group", $selectedgroup, get_string("allgroups") );
	echo '</td>';
    }

    if ($showusers) {
    	echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('participants').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu ($users, "user", $selecteduser, get_string("allparticipants") );
	echo "</td></tr><tr>";
    }
    else {
        $users = array();
        if (!empty($selecteduser)) {
            $user = get_record('user','id',$selecteduser);
            $users[$selecteduser] = fullname($user);
        }
        else {
            $users[0] = get_string('allparticipants');
	}
        echo '<td align="'.$strrtltablealignment.'" class="logininfo">';
	choose_from_menu($users, 'user', $selecteduser, false);
	echo '</td>';
        $a->url = "$CFG->wwwroot/course/report/log/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
            ."&id=$course->id&date=$selecteddate&modid=$selectedactivity&showusers=1&showcourses=$showcourses";
        print_string('logtoomanyusers','moodle',$a);
    }
    echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('from').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($dates, "date", $selecteddate, "");
    echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('to').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($dates, "date1", $selecteddate1, "");
    echo '</td><td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('activity').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($activities, "modid", $selectedactivity, get_string("allactivities"), "", "");
    echo '</td></tr><tr><td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('action').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($actions, 'modaction', $modaction, get_string("allactions"));
    echo '</tD>';    
    $logformats = array('showashtml' => get_string('displayonpage'),
                        'downloadascsv' => get_string('downloadtext'),
                        'downloadasods' => get_string('downloadods'),
                        'downloadasexcel' => get_string('downloadexcel'));
    echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('format').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($logformats, 'logformat', $logformat, false);
    echo '</tD><td colspan=2 align="'.$strrtltablealignment.'" class="logininfo">';
    echo '<input type="submit" value="'.get_string('displayreport','report_dates').'" />';
    echo '</div>';
    echo '</form></td></tr></table>';
}

function print_log_selector_form1($course, $selecteduser=0, $selecteddate='today', $selecteddate1='today',
                                 $modname="", $modid=0, $modaction='', $selectedgroup=-1, $showcourses=0, $showusers=0, $logformat='showashtml') {

    global $USER, $CFG;

// are we in rtl or ltr mode? for table alignment
right_to_left() ? $strrtltablealignment = 'right' : $strrtltablealignment = 'left';

    // first check to see if we can override showcourses and showusers
    $numcourses =  count_records_select("course", "", "COUNT(id)");
    if ($numcourses < COURSE_MAX_COURSES_PER_DROPDOWN && !$showcourses) {
        $showcourses = 1;
    }
    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
   
    /// Setup for group handling.
    if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
        $selectedgroup = get_current_group($course->id);
        $showgroups = false;
    }
    else if ($course->groupmode) {
        $selectedgroup = ($selectedgroup == -1) ? get_current_group($course->id) : $selectedgroup;
        $showgroups = true;
    }
    else {
        $selectedgroup = 0;
        $showgroups = false;
    }

    // Get all the possible users
    $users = array();

    if ($course->id != SITEID) {
        if ($selectedgroup) {   // If using a group, only get users in that group.
            $courseusers = get_group_users($selectedgroup, 'u.lastname ASC', '', 'u.id, u.firstname, u.lastname, u.idnumber');
        } else {
            $courseusers = get_course_users($course->id, '', '', 'u.id, u.firstname, u.lastname, u.idnumber');
        }
    } else {
        $courseusers = get_site_users("u.lastaccess DESC", "u.id, u.firstname, u.lastname, u.idnumber");
    }
    
    if (count($courseusers) < COURSE_MAX_USERS_PER_DROPDOWN && !$showusers) { 
	        $showusers = 1;
    }

    if ($showusers) {
        if ($courseusers) {
            foreach ($courseusers as $courseuser) {
                $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
            }
        }
        if ($guest = get_guest()) {
            $users[$guest->id] = fullname($guest);
        }
    }

    if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
        if ($ccc = get_records("course", "", "", "fullname","id,fullname,category")) {
            foreach ($ccc as $cc) {
                if ($cc->category) {
                    $courses["$cc->id"] = format_string($cc->fullname);
                } else {
                    $courses["$cc->id"] = format_string($cc->fullname) . ' (Site)';
                }
            }
        }
        asort($courses);
    }

    $activities = array();
    $selectedactivity = "";

/// Casting $course->modinfo to string prevents one notice when the field is null
    if ($modinfo = unserialize((string)$course->modinfo)) {
        $section = 0;
        if ($course->format == 'weeks') {  // Bodgy
            $strsection = get_string("week");
        } else {
            $strsection = get_string("topic");
        }
        foreach ($modinfo as $mod) {
            if ($mod->mod == "label" || $mod->mod == "Accord") {
                continue;
            }
            if ($mod->section > 0 and $section <> $mod->section) {
                $activities["section/$mod->section"] = "-------------- $strsection $mod->section --------------";
            }
            $section = $mod->section;
            $mod->name = strip_tags(format_string(urldecode($mod->name),true));
            if (strlen($mod->name) > 55) {
                $mod->name = substr($mod->name, 0, 50)."...";
            }
            if (!$mod->visible) {
                $mod->name = "(".$mod->name.")";
            }
            $activities["$mod->cm"] = $mod->name;

            if ($mod->cm == $modid) {
                $selectedactivity = "$mod->cm";
            }
        }
    }

    if (has_capability('coursereport/log:view', $sitecontext) && ($course->id == SITEID)) {
        $activities["site_errors"] = get_string("siteerrors");
        if ($modid === "site_errors") {
            $selectedactivity = "site_errors";
        }
    }

    $strftimedate = get_string("strftimedate");
    $strftimedaydate = get_string("strftimedaydate");

    asort($users);

    // Prepare the list of action options.
    $actions = array(
        'view' => get_string('view'),
        'add' => get_string('add'),
        'update' => get_string('update'),
        'delete' => get_string('delete'),
        '-view' => get_string('allchanges')
    );

    // Get all the possible dates
    // Note that we are keeping track of real (GMT) time and user time
    // User time is only used in displays - all calcs and passing is GMT

    $timenow = time(); // GMT

    // What day is it now for the user, and when is midnight that day (in GMT).
    $timemidnight = $today = usergetmidnight($timenow);

    // Put today up the top of the list
    $dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate) );

    if (!$course->startdate or ($course->startdate > $timenow)) {
        $course->startdate = $course->timecreated;
    }

    $numdates = 1;
    while ($timemidnight > $course->startdate and $numdates < 365) {
        $timemidnight = $timemidnight - 86400;
        $timenow = $timenow - 86400;
        $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
        $numdates++;
    }

    if ($selecteddate == "today") {
        $selecteddate = $today;
    }
   if ($selecteddate1 == "today") {
        $selecteddate1 = $today;
   }

    echo "<form class=\"logselectform\" action=\"$CFG->wwwroot/course/report/dates/index.php\" method=\"get\">\n";
    echo '<table align="center"><tr>';
    echo "<div>\n";
    echo "<input type=\"hidden\" name=\"chooselog\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"showusers\" value=\"$showusers\" />\n";
    echo "<input type=\"hidden\" name=\"showcourses\" value=\"$showcourses\" />\n";
    if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
	    echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('course').'<td align="'.$strrtltablealignment.'">';
	    choose_from_menu ($courses, "id", $course->id, "");
	    echo "</td>";
    } else {
        //        echo '<input type="hidden" name="id" value="'.$course->id.'" />';
        $courses = array();
	$courses[$course->id] = $course->fullname . (($course->id == SITEID) ? ' ('.get_string('site').') ' : '');
        echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('course').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu($courses,"id",$course->id,false);
	echo "</td>";
        if (has_capability('coursereport/log:view', $sitecontext)) {
            $a = new object();
            $a->url = "$CFG->wwwroot/course/report/dates/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
                ."&id=$course->id&date=$selecteddate&date1=$selecteddate1&modid=$selectedactivity&showcourses=1&showusers=$showusers";
            print_string('logtoomanycourses','moodle',$a);
        }
    }

    if ($showgroups) {
        if ($cgroups = groups_get_all_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        else {
            $groups = array();
	}
        echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('group').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu ($groups, "group", $selectedgroup, get_string("allgroups") );
	echo "</td>";
    }

    if ($showusers) {
 	echo '<td class="logininfo" align="'.$strrtltablealignment.'">'.get_string('participants').'<td align="'.$strrtltablealignment.'">';
	choose_from_menu ($users, "user", $selecteduser, get_string("allparticipants") );
	echo "</td></tr><tr>";
    }
    else {
        $users = array();
        if (!empty($selecteduser)) {
            $user = get_record('user','id',$selecteduser);
            $users[$selecteduser] = fullname($user);
        }
        else {
            $users[0] = get_string('allparticipants');
	}
	echo '<td align="'.$strrtltablealignment.'" class="logininfo">';
	choose_from_menu($users, 'user', $selecteduser, false);
	echo "</td>";
        $a = new object();
        $a->url = "$CFG->wwwroot/course/report/log/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
            ."&id=$course->id&date=$selecteddate&date1=$selecteddate1&modid=$selectedactivity&showusers=1&showcourses=$showcourses";
        print_string('logtoomanyusers','moodle',$a);
    }
    echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('from').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($dates, "date", $selecteddate,"");
    echo '</td><td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('to').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($dates, "date1", $selecteddate1, "");
    echo '</td><td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('activity').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($activities, "modid", $selectedactivity, get_string("allactivities"), "", "");
    echo '</td></tr><tr><td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('action').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($actions, 'modaction', $modaction, get_string("allactions"));
    echo "</tD>";
    $logformats = array('showashtml' => get_string('displayonpage'),
                        'downloadascsv' => get_string('downloadtext'),
                        'downloadasods' => get_string('downloadods'),
                        'downloadasexcel' => get_string('downloadexcel'));
    echo '<td align="'.$strrtltablealignment.'" class="logininfo">'.get_string('format').'<td align="'.$strrtltablealignment.'" class="logininfo">';
    choose_from_menu ($logformats, 'logformat', $logformat, false);
    echo '</tD><td colspan=2 align="'.$strrtltablealignment.'" class="logininfo">';
    echo '<input type="submit" value="'.get_string('displayreport','report_dates').'" />';
    echo '</div>';
    echo '</form></td></tr></table>';
}

?>
