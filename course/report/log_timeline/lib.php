<?php

function print_mnet_log_selector_form($hostid, $course, $selecteduser=0, $selecteddate='today',
                                 $modname="", $modid=0, $modaction='', $selectedgroup=-1, $showcourses=0, $showusers=0) {

    global $USER, $CFG, $SITE;
    require_once $CFG->dirroot.'/mnet/peer.php';
    
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
    
    $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
    
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

    // If looking at a different host, we're interested in all our site users
    if ($hostid == $CFG->mnet_localhost_id && $course->id != SITEID) {
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

    $hostarray = array();
    $dropdown  = array();

    if ($hosts = get_records_sql($sql)) {
        foreach($hosts as $host) {
            $hostarray[$host->id] = $host->name;
        }
    }

    $hostarray[$CFG->mnet_localhost_id] = $SITE->fullname;
    asort($hostarray);

    // $hostid already exists
    foreach($hostarray as $hostid_ => $name) {
        $courses = array();
        $sites = array();
        if (has_capability('moodle/site:viewreports', $context) && $showcourses) {
            if ($CFG->mnet_localhost_id == $hostid_) {
                if ($ccc = get_records("course", "", "", "fullname","id,fullname,category")) {
                    foreach ($ccc as $cc) {
                        if ($cc->id == SITEID) {
                            $sites["$hostid_/$cc->id"]   = format_string($cc->fullname).' ('.get_string('site').')';
                        } else {
                            $courses["$hostid_/$cc->id"] = format_string($cc->fullname);
                        }
                    }
                }
            } else {
                $sql = "select distinct course, coursename from mdl_mnet_log where hostid = '$hostid_'";
                if ($ccc = get_records_sql($sql)) {
                    foreach ($ccc as $cc) {
                        if (1 == $cc->course) { // TODO: MDL-8187 : this might be wrong - site course may have another id
                            $sites["$hostid_/$cc->course"]   = $cc->coursename.' ('.get_string('site').')';
                        } else {
                            $courses["$hostid_/$cc->course"] = $cc->coursename;
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

    if (has_capability('moodle/site:viewreports', $sitecontext) && !$course->category) {
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

    echo "<form class=\"logselectform\" action=\"$CFG->wwwroot/course/report/log_timeline/index.php\" method=\"get\">\n";
    echo "<div>\n";//invisible fieldset here breaks wrapping
    echo "<input type=\"hidden\" name=\"chooselog\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"showusers\" value=\"$showusers\" />\n";
    echo "<input type=\"hidden\" name=\"showcourses\" value=\"$showcourses\" />\n";
/*    if (has_capability('moodle/site:viewreports', $sitecontext) && $showcourses) {
        $cid = empty($course->id)? '1' : $course->id; 
        choose_from_menu_nested($dropdown, "host_course", $hostid.'/'.$cid, "");
    } else {
        $courses = array();
        $courses[$course->id] = $course->fullname . ((empty($course->category)) ? ' ('.get_string('site').') ' : '');
        choose_from_menu($courses,"id",$course->id,false);
        if (has_capability('moodle/site:viewreports', $sitecontext)) {
            $a = new object();
            $a->url = "$CFG->wwwroot/course/report/log/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
                ."&id=$course->id&date=$selecteddate&modid=$selectedactivity&showcourses=1&showusers=$showusers";
            print_string('logtoomanycourses','moodle',$a);
        }
    }
*/
    if ($showgroups) {
        if ($cgroups = get_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        else {
            $groups = array();
        }
        choose_from_menu ($groups, "group", 0, get_string("allgroups") ); // 0=$selectedgroup
    }

    if ($showusers) {
        choose_from_menu ($users, "user", $selecteduser, get_string("allparticipants") );
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
        choose_from_menu($users, 'user', $selecteduser, false);
        $a->url = "$CFG->wwwroot/course/report/log/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
            ."&id=$course->id&date=$selecteddate&modid=$selectedactivity&showusers=1&showcourses=$showcourses";
        print_string('logtoomanyusers','moodle',$a);
    }
    choose_from_menu_date ($dates, "date", $selecteddate, get_string("alldays"));
    choose_from_menu ($activities, "modid", $selectedactivity, get_string("allactivities"), "", "");
    choose_from_menu ($actions, 'modaction', $modaction, get_string("allactions"));
    
    echo '<input type="button" id="button_highlight" value="HighlightOff" onClick="return func_high();"/>';
    echo '<input type="button" id="button_count" value="Count" onClick="count_events();"/>';
    echo '</div>';
    echo '</form>';
    echo '<div id="menu_highlight">';
    echo '</div>';
}

function choose_from_menu_date ($options, $name, $selected='', $nothing='choose', $script='',
                           $nothingvalue='0', $return=false, $disabled=false, $tabindex=0, $id='') {

    if ($nothing == 'choose') {
        $nothing = get_string('choose') .'...';
    }

    $attributes = ($script) ? 'onchange="'. $script .'"' : '';
    if ($disabled) {
        $attributes .= ' disabled="disabled"';
    }

    if ($tabindex) {
        $attributes .= ' tabindex="'.$tabindex.'"';
    }

    if ($id ==='') {
        $id = 'menu'.$name;
         // name may contaion [], which would make an invalid id. e.g. numeric question type editing form, assignment quickgrading
        $id = str_replace('[', '', $id);
        $id = str_replace(']', '', $id);
    }

    $output = '<select id="'.$id.'" name="'. $name .'" '. $attributes .'>' . "\n";
    if ($nothing) {
        $output .= '   <option id="0" value="'. s($nothingvalue) .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";
    }
    if (!empty($options)) {
        foreach ($options as $value => $label) {
            $output .= '   <option id="'. date("M_d_Y", $value) .'" value="'. s($value) .'"';
            if ((string)$value == (string)$selected) {
                $output .= ' selected="selected"';
            }
            if ($label === '') {
                $output .= '>'. $value .'</option>' . "\n";
            } else {
                $output .= '>'. $label .'</option>' . "\n";
            }
        }
    }
    $output .= '</select>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

function print_heading_modified($text, $id=null, $align='', $size=2, $class='main', $return=false) {
    if ($align) {
        $align = ' style="text-align:'.$align.';"';
    }
    if ($class) {
        $class = ' class="'.$class.'"';
    }

    if (isset($id)) { $output = "<h$size id=\"$id\" $align $class>".stripslashes_safe($text)."</h$size>"; }
    else { $output = "<h$size $align $class>".stripslashes_safe($text)."</h$size>";}

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

?>