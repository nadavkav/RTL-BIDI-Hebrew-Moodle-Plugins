<?php   // $Id: index.php,v 1.5 2007/09/09 09:00:18 stronk7 Exp $

/// This page lists all the instances of WebQuest in a particular course

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "WebQuest", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strwebquests = get_string("modulenameplural", "webquest");
    $strwebquest  = get_string("modulename", "webquest");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strwebquests", "$course->fullname", "$navigation $strwebquests", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $webquests = get_all_instances_in_course("webquest", $course)) {
        notice("There are no WebQuests", "../../course/view.php?id=$course->id");
        die;
    }


/// Print the list of instances

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
    $strtasks = get_string("tasks","webquest");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname, $strtasks);
        $table->align = array ("CENTER", "LEFT", "LEFT");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strtasks);
        $table->align = array ("CENTER", "LEFT", "LEFT", "LEFT", "LEFT");
    } else {
        $table->head  = array ($strname, $strtasks);
        $table->align = array ("LEFT", "LEFT", "LEFT", "LEFT");
    }

    foreach ($webquests as $webquest) {
        if (!$webquest->visible) {
            $link = "<a class=\"dimmed\" href=\"view.php?id=$webquest->coursemodule\">$webquest->name</a>";
        } else {
            $link = "<a href=\"view.php?id=$webquest->coursemodule\">$webquest->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($webquest->section, $link, $webquest->ntasks);
        } else {
            $table->data[] = array ($link, $webquest->ntasks);
        }
    }

    echo "<BR>";

    print_table($table);

/// Finish the page

    print_footer($course);

?>