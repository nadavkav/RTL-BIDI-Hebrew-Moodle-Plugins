<?PHP // $Id: index.php,v 1.4.10.5 2009/10/07 17:19:00 diml Exp $

    /**
    * @package mod-scheduler
    * @category mod
    * @author Valery Fremaux (admin@ethnoinformatique.fr)
    */

/// This page lists all the instances of scheduler in a particular course

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record('course', 'id', $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, 'scheduler', 'view all', "index.php?id=$course->id", "");


/// Get all required strings

    $strschedulers = get_string('modulenameplural', 'scheduler');
    $strscheduler  = get_string('modulename', 'scheduler');

/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strscheduler, 'link' => '', 'type' => 'title');    
    $navigation = build_navigation($navlinks);
    print_header_simple($strscheduler, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

    if (! $schedulers = get_all_instances_in_course("scheduler", $course)) {
        notice(get_string('noschedulers', 'scheduler'), "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances 

    $timenow = time();
    $strname  = get_string('name');
    $strweek  = get_string('week');
    $strtopic  = get_string('topic');

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ('CENTER', 'LEFT');
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ('CENTER', 'LEFT', 'LEFT', 'LEFT');
    } else {
        $table->head  = array ($strname);
        $table->align = array ('LEFT', 'LEFT', 'LEFT');
    }

    foreach ($schedulers as $scheduler) {
        if (!$scheduler->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id={$scheduler->coursemodule}\">$scheduler->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id={$scheduler->coursemodule}\">$scheduler->name</a>";
        }
        if ($scheduler->visible or isteacher($course->id)) {
            if ($course->format == 'weeks' or $course->format == 'topics') {
                $table->data[] = array ($scheduler->section, $link);
            } else {
                $table->data[] = array ($link);
            }
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
