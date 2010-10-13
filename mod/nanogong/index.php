<?php // $Id: index.php,v 1.10 2008/07/24 00:00:00 gibson Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course
    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "nanogong", "view all", "index.php?id=$course->id", "");

    $strnanogongs = get_string("modulenameplural", "nanogong");
    $strnanogong = get_string("modulename", "nanogong");

/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    print_header("$course->shortname: $strnanogongs", "$course->fullname", "$navigation $strnanogongs", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $nanogongs = get_all_instances_in_course("nanogong", $course)) {
        notice("There are no NanoGong modules", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances

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

    foreach ($nanogongs as $nanogong) {
        if (!$nanogong->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$nanogong->coursemodule\">$nanogong->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$nanogong->coursemodule\">$nanogong->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($nanogong->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
