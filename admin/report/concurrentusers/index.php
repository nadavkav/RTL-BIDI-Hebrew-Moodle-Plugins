<?php  // $Id: index.php,v 1.12.4.1 2008/02/23 12:09:43 skodak Exp $

    require_once('../../../config.php');
    require_once($CFG->dirroot.'/lib/statslib.php');
    require_once($CFG->dirroot.'/course/report/stats/lib.php');

    require_once($CFG->libdir.'/adminlib.php');

    admin_externalpage_setup('reportstats');

    admin_externalpage_print_header();


    $fromdate = optional_param('fromdate', 0, PARAM_TEXT);
	$todate = optional_param('todate', 0, PARAM_TEXT);
    $fromtime = optional_param('fromtime', 0, PARAM_TEXT);
	$totime = optional_param('totime', 0, PARAM_TEXT);
    $userid   = optional_param('userid', 0, PARAM_INT);
    $courseid = optional_param('course', SITEID, PARAM_INT);

    require_login();

    if (empty($CFG->enablestats)) {
        redirect("$CFG->wwwroot/$CFG->admin/settings.php?section=stats", get_string('mustenablestats', 'admin'), 3);
    }

    require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM));

	$todaydate = date("Y-m-d");
	if (empty($fromdate)) {
		$fromdate = $todaydate;
		$todate = $todaydate;
		$fromtime = '00:00';
		$totime = '23:59';
	}

	echo "<form method=\"post\" action=\"index.php\">";
	echo get_string('fromdate','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"fromdate\" value=\"$fromdate\">";
	echo get_string('todate','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"todate\" value=\"$todate\">";
	echo "<br/>";
	echo get_string('fromtime','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"fromtime\" value=\"$fromtime\">";
	echo get_string('totime','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"totime\" value=\"$totime\">";
	echo "<input type=\"submit\" value=\"".get_string('update')."\"></form><br/>";

	$sql = 'SELECT 	DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y.%m.%d-%k:%i\' ) AS grptime,
				COUNT( time ) AS permin
				FROM mdl_log m
				WHERE 	DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y-%m-%d %H:%i\' ) >= \''.$fromdate.' '.$fromtime.'\' AND
						DATE_FORMAT( FROM_UNIXTIME( time ) , \'%Y-%m-%d %H:%i\' ) <= \''.$todate.' '.$totime.'\'
				GROUP BY grptime
				ORDER BY grptime DESC';
//echo $sql;
	echo '<div id="progress" style="text-align:center;"><img src="progress.gif" width="400" height="200"></div><br/><br/>';
	echo "<img src=\"usersgraph.php?fromdate=$fromdate&todate=$todate&fromtime=$fromtime&totime=$totime\" onload=\"document.getElementById('progress').style.display = 'none';\">";

    add_to_log(1, "site", "report stats", "report/concurrent-users/index.php?fromdate=$fromdate&todate=$todate&fromtime=$fromtime&totime=$totime", $todaydate);

    admin_externalpage_print_footer();

?>
