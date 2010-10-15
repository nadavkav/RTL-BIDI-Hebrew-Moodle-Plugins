<?php // $Id: index.php,v 1.0 2009/01/28 matbury Exp $
/**
 * This page lists all the instances of swf in a particular course
 *
 * @author Matt Bury - matbury@gmail.com - http://matbury.com/
 * @version $Id: index.php,v 1.0 2009/01/28 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package swf
 **/
 
/*
*    Copyright (C) 2009  Matt Bury - matbury@gmail.com - http://matbury.com/
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/// Replace swf with the name of your module

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "swf", "view all", "index.php?id=$course->id", "");


/// Get all required stringsswf

    $strswfs = get_string("modulenameplural", "swf");
    $strswf  = get_string("modulename", "swf");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strswfs, 'link' => '', 'type' => 'activity');
    //$navigation = build_navigation($navlinks);
	
	//print_header_simple("$strswfs", "", $navigation, "", "", true, "", navmenu($course));
    print_header_simple("$strswfs", "", 'swf', "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $swfs = get_all_instances_in_course("swf", $course)) {
        notice("There are no swfs", "../../course/view.php?id=$course->id");
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

    foreach ($swfs as $swf) {
        if (!$swf->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$swf->coursemodule\">$swf->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$swf->coursemodule\">$swf->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($swf->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
