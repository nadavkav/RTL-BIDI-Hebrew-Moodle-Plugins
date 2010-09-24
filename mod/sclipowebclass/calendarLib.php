<?php

function startCalendar($course)
{
$view = optional_param('view', 'upcoming', PARAM_ALPHA);
    $day  = optional_param('cal_d', 0, PARAM_INT);
    $mon  = optional_param('cal_m', 0, PARAM_INT);
    $yr   = optional_param('cal_y', 0, PARAM_INT);

 
 // Initialize the session variables
    calendar_session_vars();

    //add_to_log($course->id, "course", "view", "view.php?id=$course->id", "$course->id");
    $now = usergetdate(time());
    $pagetitle = '';
    
    $nav = calendar_get_link_tag(get_string('calendar', 'calendar'), CALENDAR_URL.'view.php?view=upcoming&amp;course='.$course->id.'&amp;', $now['mday'], $now['mon'], $now['year']);

    
    if(!checkdate($mon, $day, $yr)) {
        $day = intval($now['mday']);
        $mon = intval($now['mon']);
        $yr = intval($now['year']);
    }
    $time = make_timestamp($yr, $mon, $day);
	switch($view) {
        case 'day':
            $nav .= ' -> '.userdate($time, get_string('strftimedate'));
            $pagetitle = get_string('dayview', 'calendar');
        break;
        case 'month':
            $nav .= ' -> '.userdate($time, get_string('strftimemonthyear'));
            $pagetitle = get_string('detailedmonthview', 'calendar');
        break;
        case 'upcoming':
            $pagetitle = get_string('upcomingevents', 'calendar');
        break;
    }

    // If a course has been supplied in the URL, change the filters to show that one
    if (!empty($course->id)) {
        if ($course = get_record('course', 'id', $course->id)) {
            if ($course->id == SITEID) {
                // If coming from the home page, show all courses
                $SESSION->cal_courses_shown = calendar_get_default_courses(true);
                calendar_set_referring_course(0);

            } else {
                // Otherwise show just this one
                $SESSION->cal_courses_shown = $course->id;
                calendar_set_referring_course($SESSION->cal_courses_shown);
            }
        }
    } else {
        $course = null;
    }

    if (empty($USER->id) or isguest()) {
        $defaultcourses = calendar_get_default_courses();
        calendar_set_filters($courses, $groups, $users, $defaultcourses, $defaultcourses);

    } else {
        calendar_set_filters($courses, $groups, $users);
    }

    // Let's see if we are supposed to provide a referring course link
    // but NOT for the "main page" course
    if ($SESSION->cal_course_referer != SITEID &&
       ($shortname = get_field('course', 'shortname', 'id', $SESSION->cal_course_referer)) !== false) {
        // If we know about the referring course, show a return link and ALSO require login!
        require_login();
        $nav = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$SESSION->cal_course_referer.'">'.$shortname.'</a> -> '.$nav;
        if (empty($course)) {
            $course = get_record('course', 'id', $SESSION->cal_course_referer); // Useful to have around
        }
    }

    $strcalendar = get_string('calendar', 'calendar');
    $prefsbutton = calendar_preferences_button();

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }


    echo calendar_overlib_html();
     // Layout the whole page as three big columns.
    echo '<table id="calendar" style="height:100%;">';
    echo '<tr>';

    // START: Main column

    /// Print the main part of the pageecho $user;
 echo '<td class="maincalendar">';
    echo '<div class="heightcontainer">';
}

?>