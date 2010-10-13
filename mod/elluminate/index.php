<?php // $Id: index.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';

    $id = optional_param('id', 0, PARAM_INT);                   // Course id

    if ($id) {
        if (! $course = get_record('course', 'id', $id)) {
            error("Course ID is incorrect");
        }
    } else {
        if (! $course = get_site()) {
            error("Could not find a top-level course!");
        }
    }

    require_course_login($course);


    add_to_log($course->id, "elluminate", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strelluminates = get_string("modulenameplural", "elluminate");
    $strelluminate  = get_string("modulename", "elluminate");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

/// Print header.
    $navigation = build_navigation($strelluminates);
    print_header_simple($strelluminates, "", $navigation, "", "", true, '');

    

/// Get all the appropriate data

    if (! $elluminates = get_all_instances_in_course("elluminate", $course)) {
        notice("There are no Elluminate Live! meetings ", "../../course/view.php?id=$course->id");
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

	$search =  array("@", "#", "$", "%", "^", "?", "&", "/", "\\", "'", ";", "\"", ",", ".", "<", ">","*");
	$replace = '';

    foreach ($elluminates as $elluminate) {
    	$name = $elluminate->name;
	    //$name = str_replace($search, $replace, stripslashes($elluminate->name));
	    $elluminate->name = stripslashes($elluminate->name);
		//if(($elluminate->groupmode == 0) || ($elluminate->creator == $USER->id) || groups_is_member($elluminate->groupid, $USER->id)) {
	        if (!$elluminate->visible) {
	            //Show dimmed if the mod is hidden
	            //$link = "<a class=\"dimmed\" href=\"view.php?id=$elluminate->coursemodule\">$elluminate->name</a>";
	            $link = "<a class=\"dimmed\" href=\"view.php?id=$elluminate->coursemodule\">$name</a>";
	        } else {
	            //Show normal if the mod is visible
	            //$link = "<a href=\"view.php?id=$elluminate->coursemodule\">$elluminate->name</a>";
	            $link = "<a href=\"view.php?id=$elluminate->coursemodule\">$name</a>";
	        }
	
	        if ($course->format == "weeks" or $course->format == "topics") {
	            $table->data[] = array ($elluminate->section, $link);
	        } else {
	            $table->data[] = array ($link);
	        }
		//}
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
