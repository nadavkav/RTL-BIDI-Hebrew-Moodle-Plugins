<?php // $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page lists all the instances of bookmarks in a particular course
 *
 * @author 
 * @version $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package bookmarks
 **/



    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "bookmarks", "view all", "index.php?id=$course->id", "");



/// Get all required strings

    $strbookmarkss = get_string("modulenameplural", "bookmarks");
    $strbookmarks  = get_string("modulename", "bookmarks");


/// Print header.
	$navlinks = array();
	$navlinks[] = array('name' => get_string('modulenameplural','bookmarks'), 'link' => $CFG->wwwroot.'/mod/bookmarks/index.php?id='.$course->id, 'type' => 'activity');
	    
	$navigation = build_navigation($navlinks);
	    
	print_header_simple(get_string('modulenameplural','bookmarks'), "",
		$navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $bookmarks = get_all_instances_in_course("bookmarks", $course)) {
        notice("There are no bookmarks", "../../course/view.php?id=$course->id");
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

    foreach ($bookmarks as $instance) {
        if (!$instance->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$instance->coursemodule\">$instance->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$instance->coursemodule\">$instance->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($instance->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
