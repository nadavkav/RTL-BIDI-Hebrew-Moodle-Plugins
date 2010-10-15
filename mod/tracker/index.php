<?PHP // $Id: index.php,v 1.1.10.6 2010/02/13 16:35:17 diml Exp $

/**
* @package mod-tracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* This page lists all the instances of tracker in a particular course
* Replace tracker with the name of your module
*/


    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/tracker/lib.php');

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record('course', 'id', $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, 'tracker', 'view all', "index.php?id=$course->id", '');


/// Get all required strings

    $strtrackers = get_string('modulenameplural', 'tracker');
    $strtracker  = get_string('modulename', 'tracker');


/// Print the header

    $navigation = build_navigation($strtrackers);
    print_header_simple($strtrackers, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

    if (! $trackers = get_all_instances_in_course('tracker', $course)) {
        notice('There are no trackers', "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic  = get_string('topic');

    if ($course->format == 'weeks') {
        $table->head  = array ($strweek, $strname);
        $table->align = array ('center', 'left');
    } else if ($course->format == 'topics') {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ('center', 'left', 'left', 'left');
    } else {
        $table->head  = array ($strname);
        $table->align = array ('left', 'left', 'left');
    }

    foreach ($trackers as $tracker) {
        $trackername = format_string($tracker->name);
        if (!$tracker->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id={$tracker->coursemodule}\">{$trackername}</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id={$tracker->coursemodule}\">{$trackername}</a>";
        }

        if ($course->format == 'weeks' or $course->format == 'topics') {
            $table->data[] = array ($tracker->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo '<br />';

    print_table($table);

/// Finish the page

    print_footer($course);

?>
