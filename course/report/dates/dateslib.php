<?php  // $Id: lib.php,v 1.0 Exp $
   // Library of useful functions

define('COURSE_MAX_RECENT_PERIOD1', 172800);     // Two days, in seconds
define('EXCELROWS1', 65535);
define('FIRSTUSEDEXCELROW1', 3);


function make_log_url1($module, $url) {
    switch ($module) {
        case 'course':
        case 'file':
        case 'login':
        case 'lib':
        case 'admin':
        case 'calendar':
        case 'mnet course':
            return "/course/$url";
            break;
        case 'user':
        case 'blog':
            return "/$module/$url";
            break;
        case 'upload':
            return $url;
            break;
        case 'library':
        case '':
            return '/';
            break;
        case 'message':
            return "/message/$url";
            break;
        default:
            return "/mod/$module/$url";
            break;
    }
}


function build_mnet_logs_array1($hostid, $course, $user=0, $date=0,$date1=0, $order="l.time ASC", $limitfrom='', $limitnum='',
                   $modname="", $modid=0, $modaction="", $groupid=0) {

    global $CFG;

    // It is assumed that $date is the GMT time of midnight for that day,
    // and so the next 86400 seconds worth of logs are printed.

    /// Setup for group handling.

    // TODO: I don't understand group/context/etc. enough to be able to do
    // something interesting with it here
    // What is the context of a remote course?

    /// If the group mode is separate, and this user does not have editing privileges,
    /// then only the user's group can be viewed.
    //if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
    //    $groupid = get_current_group($course->id);
    //}
    /// If this course doesn't have groups, no groupid can be specified.
    //else if (!$course->groupmode) {
    //    $groupid = 0;
    //}
    $groupid = 0;

    $joins = array();

    $qry = "
            SELECT
                l.*,
                u.firstname,
                u.lastname,
                u.picture
            FROM
                {$CFG->prefix}mnet_log l
            LEFT JOIN
                {$CFG->prefix}user u
            ON
                l.userid = u.id
            WHERE
                ";

    $where .= "l.hostid = '$hostid'";

    // TODO: Is 1 really a magic number referring to the sitename?
    if ($course != 1 || $modid != 0) {
        $where .= " AND\n                l.course='$course'";
    }

    if ($modname) {
        $where .= " AND\n                l.module = '$modname'";
    }

    if ('site_errors' === $modid) {
        $where .= " AND\n                ( l.action='error' OR l.action='infected' )";
    } else if ($modid) {
        //TODO: This assumes that modids are the same across sites... probably
        //not true
        $where .= " AND\n                l.cmid = '$modid'";
    }

    if ($modaction) {
        $firstletter = substr($modaction, 0, 1);
        if (preg_match('/[[:alpha:]]/', $firstletter)) {
            $where .= " AND\n                lower(l.action) LIKE '%" . strtolower($modaction) . "%'";
        } else if ($firstletter == '-') {
            $where .= " AND\n                lower(l.action) NOT LIKE '%" . strtolower(substr($modaction, 1)) . "%'";
        }
    }

    if ($user) {
        $where .= " AND\n                l.userid = '$user'";
    }



    if ($date) {
   	  if ($date1)    
		$enddate = $date1 + 86400;
	   else
		$enddate = $date + 86400;
       
        $where .= " AND\n                l.time between '$date' AND '$enddate'";
    }

    $result = array();
    $result['totalcount'] = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}mnet_log l WHERE $where");
    if(!empty($result['totalcount'])) {
        $where .= "\n            ORDER BY\n                $order";
        $result['logs'] = get_records_sql($qry.$where, $limitfrom, $limitnum);
    } else {
        $result['logs'] = array();
    }
    return $result;
}

function build_logs_array1($course, $user=0, $date=0, $date1=0, $order="l.time ASC", $limitfrom='', $limitnum='',
                   $modname="", $modid=0, $modaction="", $groupid=0) {

    // It is assumed that $date is the GMT time of midnight for that day,
    // and so the next 86400 seconds worth of logs are printed.

    /// Setup for group handling.

    /// If the group mode is separate, and this user does not have editing privileges,
    /// then only the user's group can be viewed.
    if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
        $groupid = get_current_group($course->id);
    }
    /// If this course doesn't have groups, no groupid can be specified.
    else if (!$course->groupmode) {
        $groupid = 0;
    }

    $joins = array();

    if ($course->id != SITEID || $modid != 0) {
        $joins[] = "l.course='$course->id'";
    }

    if ($modname) {
        $joins[] = "l.module = '$modname'";
    }

    if ('site_errors' === $modid) {
        $joins[] = "( l.action='error' OR l.action='infected' )";
    } else if ($modid) {
        $joins[] = "l.cmid = '$modid'";
    }

    if ($modaction) {
        $firstletter = substr($modaction, 0, 1);
        if (preg_match('/[[:alpha:]]/', $firstletter)) {
            $joins[] = "lower(l.action) LIKE '%" . strtolower($modaction) . "%'";
        } else if ($firstletter == '-') {
            $joins[] = "lower(l.action) NOT LIKE '%" . strtolower(substr($modaction, 1)) . "%'";
        }
    }


    /// Getting all members of a group.
    if ($groupid and !$user) {
        if ($gusers = groups_get_members($groupid)) {
            $gusers = array_keys($gusers);
            $joins[] = 'l.userid IN (' . implode(',', $gusers) . ')';
        } else {
            $joins[] = 'l.userid = 0'; // No users in groups, so we want something that will always be false.
        }
    }
    else if ($user) {
        $joins[] = "l.userid = '$user'";
    }

    if ($date) {
	    if ($date1)    
		$enddate = $date1 + 86400;
	   else
		$enddate = $date + 86400;

        $joins[] = "l.time between '$date' AND '$enddate'";
    }

    $selector = implode(' AND ', $joins);

    $totalcount = 0;  // Initialise
    $result = array();
    $result['logs'] = get_logs1($selector, $order, $limitfrom, $limitnum, $totalcount);
    $result['totalcount'] = $totalcount;
    return $result;
}
 function get_logs1($select, $order='l.time DESC', $limitfrom='', $limitnum='', &$totalcount) {
      global $CFG;
  
      if ($order) {
          $order = 'ORDER BY u.id, l.action , '. $order;
      }
  
      $selectsql = $CFG->prefix .'log l LEFT JOIN '. $CFG->prefix .'user u ON l.userid = u.id '. ((strlen($select) > 0) ? 'WHERE '. $select : '');
      $countsql = $CFG->prefix.'log l '.((strlen($select) > 0) ? ' WHERE '. $select : '');
      $grpby='group by u.id,l.action';
  
      $totalcount = count_records_sql("SELECT COUNT(*) FROM $selectsql ");
  
      return get_records_sql('SELECT l.*,count(l.action) as actions, u.firstname, u.lastname, u.picture
                                  FROM '. $selectsql .' '. $grpby .' '. $order, $limitfrom, $limitnum) ;
  }


function print_log1($course, $user=0, $date=0, $date1=0, $order="l.time ASC", $page=0, $perpage=100,
                   $url="", $modname="", $modid=0, $modaction="", $groupid=0) {

    global $CFG;

    if (!$logs = build_logs_array1($course, $user, $date, $date1, $order, $page*$perpage, $perpage,
                       $modname, $modid, $modaction, $groupid)) {
        notify("No logs found!");
        print_footer($course);
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
        $courses[$course->id] = $course->shortname;
    }
//TODO: Count and Paging Bar's
 //   $totalcount = $logs['totalcount'];
    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    echo "<div class=\"info\">\n";
  // print_string("displayingrecords", "", $totalcount);
    echo "</div>\n";
    echo "<br \><br \>";
 //  print_paging_bar($totalcount, $page, $perpage, "$url&amp;perpage=$perpage&amp;");

    echo '<table class="logtable genearlbox boxaligncenter" summary="">'."\n";

    echo "<tr>";
    if ($course->id == SITEID) {
        echo "<th class=\"c0 header\" scope=\"col\">".get_string('course')."</th>\n";
    }
    echo "<th class=\"c1 header\" scope=\"col\"></th>\n";
    echo "<th class=\"c3 header\" scope=\"col\">".get_string('action')."</th>\n";
    echo "<th class=\"c4 header\" scope=\"col\">Count</th>\n";
    echo "<th class=\"c5 header\" scope=\"col\">".get_string('info')."</th>\n";
    echo "</tr>\n";

    // Make sure that the logs array is an array, even it is empty, to avoid warnings from the foreach.
    if (empty($logs['logs'])) {
        $logs['logs'] = array();
    }

    $row = 1;
    $tfullname='';
    $groupcount=0;
$date=$date+84600;
    foreach ($logs['logs'] as $log) {

        $row = ($row + 1) % 2;

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

        // If $log->url has been trimmed short by the db size restriction
        // code in add_to_log, keep a note so we don't add a link to a broken url
        $tl=textlib_get_instance();
        $brokenurl=($tl->strlen($log->url)==100 && $tl->substr($log->url,97)=='...');

        $log->url  = strip_tags(urldecode($log->url));   // Some XSS protection
        $log->info = strip_tags(urldecode($log->info));  // Some XSS protection
        $log->url  = s($log->url); /// XSS protection and XHTML compatibility - should be in link_to_popup_window() instead!!
	$groupcount=$groupcount+1;

	//* User Name *//
	$fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
	
	
	if ($fullname!=$tfullname)
	{

	echo '<tr bgcolor="3399FF" class="r">';    
        echo "<td class=\"cell c3\" colspan=\"4\">\n";
        echo " <a href=\"$CFG->wwwroot/user/view.php?id={$log->userid}&amp;course={$log->course}\">$fullname</a>&nbsp;&nbsp;&nbsp;&nbsp;".  userdate($date,$strftimedatetime)."&nbsp;&nbsp;to&nbsp;&nbsp;".userdate($date1,$strftimedatetime)."\n";
	echo "</td>\n";
	$tfullname=$fullname;
	$row=0;
        echo "</tr>\n";
	
	}
	//End User Name
	if($row==1) 
		$colr="99CCFF";
	else
		$colr="#FFFFFF";
        echo '<tr bgcolor='.$colr.'>'; //class="r'.$row.'">';
        if ($course->id == SITEID) {
            echo "<td class=\"cell c0\">\n";
            if (empty($log->course)) {
                echo get_string('site') . "\n";
            } else {
                echo "    <a href=\"{$CFG->wwwroot}/course/view.php?id={$log->course}\">". format_string($courses[$log->course])."</a>\n";
            }
            echo "</td>\n";
        }
      
        echo "<td class=\"cell c1\" align=\"right\">&nbsp;</td>\n";
    
        echo "<td class=\"cell c4\">\n";
        $displayaction="$log->module $log->action ";
        if($brokenurl) {
            echo $displayaction;
        } else {
            link_to_popup_window( make_log_url1($log->module,$log->url), 'fromloglive',$displayaction, 440, 700);
        }
	echo "</td>\n";;
        echo "<td class=\"cell c3\">\n";
        echo "  $log->actions\n";
        echo "</td>\n";

        echo "<td class=\"cell c5\">{$log->info}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";

   // $totalcount=$groupcount;
   //  print_paging_bar($totalcount, $page, $perpage, "$url&amp;perpage=$perpage&amp;");
}


function print_mnet_log1($hostid, $course, $user=0, $date=0,$date1=0, $order="l.time ASC", $page=0, $perpage=100,
                   $url="", $modname="", $modid=0, $modaction="", $groupid=0) {

    global $CFG;

    if (!$logs = build_mnet_logs_array1($hostid, $course, $user, $date,$date1, $order, $page*$perpage, $perpage,
                       $modname, $modid, $modaction, $groupid)) {
        notify("No logs found!");
        print_footer($course);
        exit;
    }

    if ($course->id == SITEID) {
        $courses[0] = '';
        if ($ccc = get_courses('all', 'c.id ASC', 'c.id,c.shortname,c.visible')) {
            foreach ($ccc as $cc) {
                $courses[$cc->id] = $cc->shortname;
            }
        }
    }

    $totalcount = $logs['totalcount'];
    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    echo "<div class=\"info\">\n";
    print_string("displayingrecords", "", $totalcount);
    echo "</div>\n";

    print_paging_bar($totalcount, $page, $perpage, "$url&amp;perpage=$perpage&amp;");

    echo "<table class=\"logtable\" cellpadding=\"3\" cellspacing=\"0\">\n";
    echo "<tr>";
    if ($course->id == SITEID) {
        echo "<th class=\"c0 header\">".get_string('course')."</th>\n";
    }
    echo "<th class=\"c1 header\"><!--get_string('time')--></th>\n";
    echo "<th class=\"c2 header\">".get_string('ip_address')."</th>\n";
    echo "<th class=\"c3 header\">".get_string('fullname')."</th>\n";
    echo "<th class=\"c4 header\">".get_string('action')."</th>\n";
    echo "<th class=\"c5 header\">".get_string('info')."</th>\n";
    echo "</tr>\n";

    if (empty($logs['logs'])) {
        echo "</table>\n";
        return;
    }

    $row = 1;
    foreach ($logs['logs'] as $log) {

        $log->info = $log->coursename;
        $row = ($row + 1) % 2;

        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = get_record('log_display', 'module', $log->module, 'action', $log->action);
            $ldcache[$log->module][$log->action] = $ld;
        }
        if (0 && $ld && !empty($log->info)) {
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

        echo '<tr class="r'.$row.'">';
        if ($course->id == SITEID) {
            echo "<td class=\"r$row c0\" >\n";
            echo "    <a href=\"{$CFG->wwwroot}/course/view.php?id={$log->course}\">".$courses[$log->course]."</a>\n";
            echo "</td>\n";
        }
        echo "<td class=\"r$row c1\" align=\"right\">".userdate($log->time, '%a').
             ' '.userdate($log->time, $strftimedatetime)."</td>\n";
        echo "<td class=\"r$row c2\" >\n";
        link_to_popup_window("/iplookup/index.php?ip=$log->ip&amp;user=$log->userid", 'iplookup',$log->ip, 400, 700);
        echo "</td>\n";
        $fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
        echo "<td class=\"r$row c3\" >\n";
        echo "    <a href=\"$CFG->wwwroot/user/view.php?id={$log->userid}\">$fullname</a>\n";
        echo "</td>\n";
        echo "<td class=\"r$row c4\">\n";
        echo $log->action .': '.$log->module;
        echo "</td>\n";;
        echo "<td class=\"r$row c5\">{$log->info}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";

    print_paging_bar($totalcount, $page, $perpage, "$url&amp;perpage=$perpage&amp;");
}


function print_log_csv1($course, $user, $date,$date1=0, $order='l.time DESC', $modname,
                        $modid, $modaction, $groupid) {

    $text = get_string('course')."\t".get_string('time')."\t".get_string('ip_address')."\t".
            get_string('fullname')."\t".get_string('action')."\t".get_string('info');

    if (!$logs = build_logs_array1($course, $user, $date,$date1, $order, '', '',
                       $modname, $modid, $modaction, $groupid)) {
        return false;
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
        $courses[$course->id] = $course->shortname;
    }

    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    $filename = 'logs_'.userdate(time(),get_string('backupnameformat'),99,false);
    $filename .= '.txt';
    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    echo get_string('savedat').userdate(time(), $strftimedatetime)."\n";
    echo $text;

    if (empty($logs['logs'])) {
        return true;
    }

    foreach ($logs['logs'] as $log) {
        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = get_record('log_display', 'module', $log->module, 'action', $log->action);
            $ldcache[$log->module][$log->action] = $ld;
        }
        if ($ld && !empty($log->info)) {
            // ugly hack to make sure fullname is shown correctly
            if (($ld->mtable == 'user') and ($ld->field ==  sql_concat('firstname', "' '" , 'lastname'))) {
                $log->info = fullname(get_record($ld->mtable, 'id', $log->info), true);
            } else {
                $log->info = get_field($ld->mtable, $ld->field, 'id', $log->info);
            }
        }

        //Filter log->info
        $log->info = format_string($log->info);

        $log->url  = strip_tags(urldecode($log->url));     // Some XSS protection
        $log->info = strip_tags(urldecode($log->info));    // Some XSS protection
        $log->url  = str_replace('&', '&amp;', $log->url); // XHTML compatibility

        $firstField = $courses[$log->course];
        $fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
        $row = array($firstField, userdate($log->time, $strftimedatetime), $fullname, $log->module.' '.$log->action,$log->actions, $log->info);
        $text = implode("\t", $row);
        echo $text." \n";
    }
    return true;
}


function print_log_xls1($course, $user, $date,$date1=0, $order='l.time DESC', $modname,
                        $modid, $modaction, $groupid) {

    global $CFG;

    require_once("$CFG->libdir/excellib.class.php");

    if (!$logs = build_logs_array1($course, $user, $date,$date1, $order, '', '',
                       $modname, $modid, $modaction, $groupid)) {
        return false;
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
        $courses[$course->id] = $course->shortname;
    }

    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    $nroPages = ceil(count($logs)/(EXCELROWS1-FIRSTUSEDEXCELROW1+1));
    $filename = 'logs_'.userdate(time(),get_string('backupnameformat'),99,false);
    $filename .= '.xls';

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();
    $headers = array(get_string('course'), get_string('fullname'),    get_string('action'),'Count', get_string('info'));

    // Creating worksheets
    for ($wsnumber = 1; $wsnumber <= $nroPages; $wsnumber++) {
        $sheettitle = get_string('logs').' '.$wsnumber.'-'.$nroPages;
        $worksheet[$wsnumber] =& $workbook->add_worksheet($sheettitle);
        $worksheet[$wsnumber]->set_column(1, 1, 30);
        $worksheet[$wsnumber]->write_string(0, 0, get_string('savedat').
                                    userdate(time(), $strftimedatetime));
        $col = 0;
        foreach ($headers as $item) {
            $worksheet[$wsnumber]->write(FIRSTUSEDEXCELROW1-1,$col,$item,'');
            $col++;
        }
    }

    if (empty($logs['logs'])) {
        $workbook->close();
        return true;
    }

    $formatDate =& $workbook->add_format();
    $formatDate->set_num_format(get_string('log_excel_date_format'));

    $row = FIRSTUSEDEXCELROW1;
    $wsnumber = 1;
    $myxls =& $worksheet[$wsnumber];
    foreach ($logs['logs'] as $log) {
        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = get_record('log_display', 'module', $log->module, 'action', $log->action);
            $ldcache[$log->module][$log->action] = $ld;
        }
        if ($ld && !empty($log->info)) {
            // ugly hack to make sure fullname is shown correctly
            if (($ld->mtable == 'user') and ($ld->field == sql_concat('firstname', "' '" , 'lastname'))) {
                $log->info = fullname(get_record($ld->mtable, 'id', $log->info), true);
            } else {
                $log->info = get_field($ld->mtable, $ld->field, 'id', $log->info);
            }
        }

        // Filter log->info
        $log->info = format_string($log->info);
        $log->info = strip_tags(urldecode($log->info));  // Some XSS protection

        if ($nroPages>1) {
            if ($row > EXCELROWS1) {
                $wsnumber++;
                $myxls =& $worksheet[$wsnumber];
                $row = FIRSTUSEDEXCELROW1;
            }
        }

        $myxls->write($row, 0, $courses[$log->course], '');
        // Excel counts from 1/1/1900
        $excelTime=25569+$log->time/(3600*24);
       // $myxls->write($row, 1, $excelTime, $formatDate);
//        $myxls->write($row, 2, $log->ip, '');
	$fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
	if ($strname==$fullname)
	{
		$myxls->write($row, 1, ' ', '');
	}
	else
	{
		$myxls->write($row, 1, $fullname, '');
		$strname=$fullname;
	}
	$myxls->write($row, 2, $log->module.' '.$log->action, '');
        $myxls->write($row, 3, $log->actions, '');
        $myxls->write($row, 4, $log->info, '');

        $row++;
    }

    $workbook->close();
    return true;
}

function print_log_ods1($course, $user, $date,$date1=0, $order='l.time DESC', $modname,
                        $modid, $modaction, $groupid) {

    global $CFG;

    require_once("$CFG->libdir/odslib.class.php");

    if (!$logs = build_logs_array1($course, $user, $date,$date1, $order, '', '',
                       $modname, $modid, $modaction, $groupid)) {
        return false;
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
        $courses[$course->id] = $course->shortname;
    }

    $count=0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime (0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    $nroPages = ceil(count($logs)/(EXCELROWS1-FIRSTUSEDEXCELROW1+1));
    $filename = 'logs_'.userdate(time(),get_string('backupnameformat'),99,false);
    $filename .= '.ods';

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();
    $headers = array(get_string('course'), get_string('time'), get_string('ip_address'),
                        get_string('fullname'),    get_string('action'), get_string('info'));

    // Creating worksheets
    for ($wsnumber = 1; $wsnumber <= $nroPages; $wsnumber++) {
        $sheettitle = get_string('logs').' '.$wsnumber.'-'.$nroPages;
        $worksheet[$wsnumber] =& $workbook->add_worksheet($sheettitle);
        $worksheet[$wsnumber]->set_column(1, 1, 30);
        $worksheet[$wsnumber]->write_string(0, 0, get_string('savedat').
                                    userdate(time(), $strftimedatetime));
        $col = 0;
        foreach ($headers as $item) {
            $worksheet[$wsnumber]->write(FIRSTUSEDEXCELROW1-1,$col,$item,'');
            $col++;
        }
    }

    if (empty($logs['logs'])) {
        $workbook->close();
        return true;
    }

    $formatDate =& $workbook->add_format();
    $formatDate->set_num_format(get_string('log_excel_date_format'));

    $row = FIRSTUSEDEXCELROW1;
    $wsnumber = 1;
    $myxls =& $worksheet[$wsnumber];
    foreach ($logs['logs'] as $log) {
        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = get_record('log_display', 'module', $log->module, 'action', $log->action);
            $ldcache[$log->module][$log->action] = $ld;
        }
        if ($ld && !empty($log->info)) {
            // ugly hack to make sure fullname is shown correctly
            if (($ld->mtable == 'user') and ($ld->field == sql_concat('firstname', "' '" , 'lastname'))) {
                $log->info = fullname(get_record($ld->mtable, 'id', $log->info), true);
            } else {
                $log->info = get_field($ld->mtable, $ld->field, 'id', $log->info);
            }
        }

        // Filter log->info
        $log->info = format_string($log->info);
        $log->info = strip_tags(urldecode($log->info));  // Some XSS protection

        if ($nroPages>1) {
            if ($row > EXCELROWS1) {
                $wsnumber++;
                $myxls =& $worksheet[$wsnumber];
                $row = FIRSTUSEDEXCELROW1;
            }
        }

        $myxls->write_string($row, 0, $courses[$log->course]);
        $myxls->write_date($row, 1, $log->time);
        $myxls->write_string($row, 2, $log->ip);
        $fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
        $myxls->write_string($row, 3, $fullname);
        $myxls->write_string($row, 4, $log->module.' '.$log->action);
        $myxls->write_string($row, 5, $log->info);

        $row++;
    }

    $workbook->close();
    return true;
}


function print_log_graph1($course, $userid=0, $type="course.png", $date=0) {
    global $CFG, $USER;
    if (empty($CFG->gdversion)) {
        echo "(".get_string("gdneed").")";
    } else {
        // MDL-10818, do not display broken graph when user has no permission to view graph
        if (has_capability('moodle/site:viewreports', get_context_instance(CONTEXT_COURSE, $course->id)) ||
            ($course->showreports and $USER->id == $userid)) {
            echo '<img src="'.$CFG->wwwroot.'/course/report/log/graph.php?id='.$course->id.
                 '&amp;user='.$userid.'&amp;type='.$type.'&amp;date='.$date.'" alt="" />';
        }
    }
}


function print_overview1($courses) {

    global $CFG, $USER;

    $htmlarray = array();
    if ($modules = get_records('modules')) {
        foreach ($modules as $mod) {
            if (file_exists(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php')) {
                include_once(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php');
                $fname = $mod->name.'_print_overview1';
                if (function_exists($fname)) {
                    $fname($courses,$htmlarray);
                }
            }
        }
    }
    foreach ($courses as $course) {
        print_simple_box_start('center', '100%', '', 5, "coursebox");
        $linkcss = '';
        if (empty($course->visible)) {
            $linkcss = 'class="dimmed"';
        }
        print_heading('<a title="'. format_string($course->fullname).'" '.$linkcss.' href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'. format_string($course->fullname).'</a>');
        if (array_key_exists($course->id,$htmlarray)) {
            foreach ($htmlarray[$course->id] as $modname => $html) {
                echo $html;
            }
        }
        print_simple_box_end();
    }
}


function print_recent_activity1($course) {
    // $course is an object
    // This function trawls through the logs looking for
    // anything new since the user's last login

    global $CFG, $USER, $SESSION;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    $viewfullnames = has_capability('moodle/site:viewfullnames', $context);

    $timestart = round(time() - COURSE_MAX_RECENT_PERIOD1, -2); // better db caching for guests - 100 seconds

    if (!has_capability('moodle/legacy:guest', $context, NULL, false)) {
        if (!empty($USER->lastcourseaccess[$course->id])) {
            if ($USER->lastcourseaccess[$course->id] > $timestart) {
                $timestart = $USER->lastcourseaccess[$course->id];
            }
        }
    }

    echo '<div class="activitydate">';
    echo get_string('activitysince', '', userdate($timestart));
    echo '</div>';
    echo '<div class="activityhead">';

    echo '<a href="'.$CFG->wwwroot.'/course/recent.php?id='.$course->id.'">'.get_string('recentactivityreport').'</a>';

    echo "</div>\n";

    $content = false;

/// Firstly, have there been any new enrolments?

    $users = get_recent_enrolments($course->id, $timestart);

    //Accessibility: new users now appear in an <OL> list.
    if ($users) {
        echo '<div class="newusers">';
        print_headline(get_string("newusers").':', 3);
        $content = true;
        echo "<ol class=\"list\">\n";
        foreach ($users as $user) {
            $fullname = fullname($user, $viewfullnames);
            echo '<li class="name"><a href="'."$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">$fullname</a></li>\n";
        }
        echo "</ol>\n</div>\n";
    }

/// Next, have there been any modifications to the course structure?

    $modinfo =& get_fast_modinfo($course);

    $changelist = array();

    $logs = get_records_select('log', "time > $timestart AND course = $course->id AND
                                       module = 'course' AND
                                       (action = 'add mod' OR action = 'update mod' OR action = 'delete mod')",
                               "id ASC");

    if ($logs) {
        $actions  = array('add mod', 'update mod', 'delete mod');
        $newgones = array(); // added and later deleted items
        foreach ($logs as $key => $log) {
            if (!in_array($log->action, $actions)) {
                continue;
            }
            $info = split(' ', $log->info);

            if ($info[0] == 'label') {     // Labels are ignored in recent activity
                continue;
            }

            if (count($info) != 2) {
                debugging("Incorrect log entry info: id = ".$log->id, DEBUG_DEVELOPER);
                continue;
            }

            $modname    = $info[0];
            $instanceid = $info[1];

            if ($log->action == 'delete mod') {
                // unfortunately we do not know if the mod was visible
                if (!array_key_exists($log->info, $newgones)) {
                    $strdeleted = get_string('deletedactivity', 'moodle', get_string('modulename', $modname));
                    $changelist[$log->info] = array ('operation' => 'delete', 'text' => $strdeleted);
                }
            } else {
                if (!isset($modinfo->instances[$modname][$instanceid])) {
                    if ($log->action == 'add mod') {
                        // do not display added and later deleted activities
                        $newgones[$log->info] = true;
                    }
                    continue;
                }
                $cm = $modinfo->instances[$modname][$instanceid];
                if (!$cm->uservisible) {
                    continue;
                }

                if ($log->action == 'add mod') {
                    $stradded = get_string('added', 'moodle', get_string('modulename', $modname));
                    $changelist[$log->info] = array('operation' => 'add', 'text' => "$stradded:<br /><a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($cm->name, true)."</a>");

                } else if ($log->action == 'update mod' and empty($changelist[$log->info])) {
                    $strupdated = get_string('updated', 'moodle', get_string('modulename', $modname));
                    $changelist[$log->info] = array('operation' => 'update', 'text' => "$strupdated:<br /><a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($cm->name, true)."</a>");
                }
            }
        }
    }

    if (!empty($changelist)) {
        print_headline(get_string('courseupdates').':', 3);
        $content = true;
        foreach ($changelist as $changeinfo => $change) {
            echo '<p class="activity">'.$change['text'].'</p>';
        }
    }

/// Now display new things from each module

    $usedmodules = array();
    foreach($modinfo->cms as $cm) {
        if (isset($usedmodules[$cm->modname])) {
            continue;
        }
        if (!$cm->uservisible) {
            continue;
        }
        $usedmodules[$cm->modname] = $cm->modname;
    }

    foreach ($usedmodules as $modname) {      // Each module gets it's own logs and prints them
        if (file_exists($CFG->dirroot.'/mod/'.$modname.'/lib.php')) {
            include_once($CFG->dirroot.'/mod/'.$modname.'/lib.php');
            $print_recent_activity = $modname.'_print_recent_activity';
            if (function_exists($print_recent_activity)) {
                // NOTE: original $isteacher (second parameter below) was replaced with $viewfullnames!
                $content = $print_recent_activity($course, $viewfullnames, $timestart) || $content;
            }
        } else {
            debugging("Missing lib.php in lib/{$modname} - please reinstall files or uninstall the module");
        }
    }

    if (! $content) {
        echo '<p class="message">'.get_string('nothingnew').'</p>';
    }
}


function get_array_of_activities1($courseid) {
// For a given course, returns an array of course activity objects
// Each item in the array contains he following properties:
//  cm - course module id
//  mod - name of the module (eg forum)
//  section - the number of the section (eg week or topic)
//  name - the name of the instance
//  visible - is the instance visible or not
//  groupingid - grouping id
//  groupmembersonly - is this instance visible to group members only
//  extra - contains extra string to include in any link

    global $CFG;

    $mod = array();

    if (!$rawmods = get_course_mods($courseid)) {
        return $mod; // always return array
    }

    if ($sections = get_records("course_sections", "course", $courseid, "section ASC")) {
       foreach ($sections as $section) {
           if (!empty($section->sequence)) {
               $sequence = explode(",", $section->sequence);
               foreach ($sequence as $seq) {
                   if (empty($rawmods[$seq])) {
                       continue;
                   }
                   $mod[$seq]->id               = $rawmods[$seq]->instance;
                   $mod[$seq]->cm               = $rawmods[$seq]->id;
                   $mod[$seq]->mod              = $rawmods[$seq]->modname;
                   $mod[$seq]->section          = $section->section;
                   $mod[$seq]->visible          = $rawmods[$seq]->visible;
                   $mod[$seq]->groupmode        = $rawmods[$seq]->groupmode;
                   $mod[$seq]->groupingid       = $rawmods[$seq]->groupingid;
                   $mod[$seq]->groupmembersonly = $rawmods[$seq]->groupmembersonly;
                   $mod[$seq]->extra            = "";

                   $modname = $mod[$seq]->mod;
                   $functionname = $modname."_get_coursemodule_info";

                   if (!file_exists("$CFG->dirroot/mod/$modname/lib.php")) {
                       continue;
                   }

                   include_once("$CFG->dirroot/mod/$modname/lib.php");

                   if (function_exists($functionname)) {
                       if ($info = $functionname($rawmods[$seq])) {
                           if (!empty($info->extra)) {
                               $mod[$seq]->extra = $info->extra;
                           }
                           if (!empty($info->icon)) {
                               $mod[$seq]->icon = $info->icon;
                           }
                           if (!empty($info->name)) {
                               $mod[$seq]->name = urlencode($info->name);
                           }
                       }
                   }
                   if (!isset($mod[$seq]->name)) {
                       $mod[$seq]->name = urlencode(get_field($rawmods[$seq]->modname, "name", "id", $rawmods[$seq]->instance));
                   }
               }
            }
        }
    }
    return $mod;
}

?>
