<?php // $Id: index.php,v 1.1 2007/09/07 06:31:51 jamiesensei Exp $
/**
 * This page lists all the instances of qcreate in a particular course
 *
 * @author
 * @version $Id: index.php,v 1.1 2007/09/07 06:31:51 jamiesensei Exp $
 * @package qcreate
 **/

/// Replace qcreate with the name of your module

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "qcreate", "view all", "index.php?id=$course->id", "");


/// Get all required stringsqcreate

    $strqcreates = get_string("modulenameplural", "qcreate");
    $strqcreate  = get_string("modulename", "qcreate");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strqcreates, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple("$strqcreates", "", $navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $qcreates = get_all_instances_in_course("qcreate", $course)) {
        notice("There are no qcreates", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($qcreates as $qcreate) {
        if (!$qcreate->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$qcreate->coursemodule\">$qcreate->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$qcreate->coursemodule\">$qcreate->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($qcreate->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
