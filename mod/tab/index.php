<?php // $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page lists all the instances of tab in a particular course
 *
 * @author : Patrick Thibaudeau
 * @version $Id: version.php,v 1.0 2007/07/01 16:41:20
 * @package tab
 **/


    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "tab", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strtabs = get_string("modulenameplural", "tab");
    $strtab  = get_string("modulename", "tab");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    print_header("$course->shortname: $strtabs", "$course->fullname", "$navigation $strtabs", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $tabs = get_all_instances_in_course("tab", $course)) {
        notice("There are no tabs", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    // set table alignment according to course's RTL/LTR mode
    if (right_to_left()){
      $rtlalignment = 'right';
    } else {
      $rtlalignment = 'left';
    }

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", $rtlalignment);
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", $rtlalignment, $rtlalignment, $rtlalignment);
    } else {
        $table->head  = array ($strname);
        $table->align = array ($rtlalignment, $rtlalignment, $rtlalignment);
    }

    foreach ($tabs as $tab) {
        if (!$tab->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$tab->coursemodule\">$tab->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$tab->coursemodule\">$tab->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($tab->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
