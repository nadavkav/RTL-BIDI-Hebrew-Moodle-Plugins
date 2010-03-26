<?php  // $Id: index.php,v 1.12.4.1 2008/02/23 12:09:43 skodak Exp $

    require_once('../../../config.php');
    require_once($CFG->dirroot.'/lib/statslib.php');
    require_once($CFG->dirroot.'/course/report/stats/lib.php');

    require_once($CFG->libdir.'/adminlib.php');

    admin_externalpage_setup('reportstats');

    admin_externalpage_print_header();


/*    $fromdate = optional_param('fromdate', 0, PARAM_TEXT);
	$todate = optional_param('todate', 0, PARAM_TEXT);
    $fromtime = optional_param('fromtime', 0, PARAM_TEXT);
	$totime = optional_param('totime', 0, PARAM_TEXT);
*/
	$selectedinstitution = optional_param('institution', 0, PARAM_TEXT);

    $userid   = optional_param('userid', 0, PARAM_INT);
    $courseid = optional_param('course', SITEID, PARAM_INT);

    require_login();

    if (empty($CFG->enablestats)) {
        redirect("$CFG->wwwroot/$CFG->admin/settings.php?section=stats", get_string('mustenablestats', 'admin'), 3);
    }

    require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM));

	$sql = 'SELECT count(m.`institution`) usercount , m.`institution` name FROM mdl_user m
				GROUP BY m.`institution`
				ORDER BY usercount DESC';
	$institutions = get_records_sql($sql);

	foreach ($institutions as $institution) {
		if ($institution->usercount > 10 ) $institutionlist[$institution->name] = $institution->name;
	}

// 	$todaydate = date("Y-m-d");
// 	if (empty($fromdate)) {
// 		$fromdate = $todaydate;
// 		$todate = $todaydate;
// 		$fromtime = '00:00';
// 		$totime = '23:59';
// 	}

	echo "<form method=\"post\" action=\"index.php\">";
// 	echo get_string('fromdate','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"fromdate\" value=\"$fromdate\">";
// 	echo get_string('todate','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"todate\" value=\"$todate\">";
// 	echo "<br/>";
// 	echo get_string('fromtime','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"fromtime\" value=\"$fromtime\">";
// 	echo get_string('totime','report_concurrentusers','',$CFG->dirroot.'/admin/report/concurrentusers/lang/')." <input type=\"text\" name=\"totime\" value=\"$totime\">";

	echo get_string('chooseinstitution','report_usageperinstitution').': ';
	choose_from_menu($institutionlist,'institution');
	echo "<input type=\"submit\" value=\"".get_string('update')."\"></form><br/>";

//	echo "<img src=\"usersgraph.php?fromdate=$fromdate&todate=$todate&fromtime=$fromtime&totime=$totime\">";
	echo get_string('graph_actions_per_institution','report_usageperinstitution').$selectedinstitution.'<br/>';
	echo "<img src=\"graph_actions_per_institution.php?institution=$selectedinstitution\"><br/>";

	echo get_string('graph_actions_per_institution_nv','report_usageperinstitution').$selectedinstitution.'<br/>';
	echo "<img src=\"graph_actions_per_institution_nv.php?institution=$selectedinstitution\"><br/>";

	echo get_string('graph_usage_per_institution_over_time','report_usageperinstitution').$selectedinstitution.'<br/>';
	echo "<img src=\"graph_usage_per_institution_over_time.php?institution=$selectedinstitution\"><br/>";

	echo get_string('list_most_active_teachers','report_usageperinstitution').$selectedinstitution.'<br/>';

	$sql = 'SELECT count(mlog.`userid`) usercount , m.`institution`, m.`firstname`, m.`lastname`
			FROM mdl_user m LEFT OUTER JOIN mdl_log mlog ON mlog.userid = m.id
			WHERE m.`institution` LIKE \''.$selectedinstitution.'\'  AND m.`department` LIKE \'מורה\'
			GROUP BY mlog.userid
			ORDER BY usercount DESC';
	$teachers = get_records_sql($sql);
	foreach($teachers as $teacher) {
		echo " {$teacher->lastname } {$teacher->firstname } ... ({$teacher->usercount }) <br/>";
	}

	add_to_log(1, "site", "report stats", "report/usageperinstitution/index.php?institution=$selectedinstitution", $selectedinstitution);

    admin_externalpage_print_footer();

?>
