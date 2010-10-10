<?php 
/**
 * This page lists all the instances of webquestscorm in a particular course
 *
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez 
 * @version $Id: index.php, v 2.0 2009/25/04
 * @package webquestscorm
 **/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "webquestscorm", "view all", "index.php?id=$course->id", "");

    $strwebquestscorms = get_string("modulenameplural", "webquestscorm");
    $strwebquestscorm  = get_string("modulename", "webquestscorm");

    if ($CFG->version > 2007101500){ 
    	$navlinks = array();
    	$navlinks[] = array('name' => $strwebquestscorms, 'link' => '', 'type' => 'activity');
    	$navigation = build_navigation($navlinks);

    	print_header_simple("$strwebquestscorms", "", $navigation, "", "", true, "", navmenu($course));
    
    }else{
	print_header_simple($strwebquestscorms, "", $strwebquestscorms, "", "", true, "", navmenu($course));
    }

    

// Get all the appropriate data

    if (! $webquestscorms = get_all_instances_in_course("webquestscorm", $course)) {
        notice("There are no webquestscorms", "../../course/view.php?id=$course->id");
        die;
    }

// Print the list of instances 

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

    foreach ($webquestscorms as $webquestscorm) {
        if (!$webquestscorm->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$webquestscorm->coursemodule\">$webquestscorm->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$webquestscorm->coursemodule\">$webquestscorm->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($webquestscorm->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);
    print_footer($course);

?>
