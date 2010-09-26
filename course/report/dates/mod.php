<?php  // $Id: mod.php,v 1.0 Exp $

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/course/report/dates/lib.php');

//    if (has_capability('coursereport/log:view', $context)) {
        print_heading(get_string('choosesummeryreport','report_dates') .':');

        print_log_selector_form1($course);
  //  }


?>
