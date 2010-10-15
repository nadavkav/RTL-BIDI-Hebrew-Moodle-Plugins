<?php  // $Id: view.php,v 1.0 2009/01/28 matbury Exp $

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

/**
 * This page prints a particular instance of swf
 *
 * @author Matt Bury - matbury@gmail.com
 * @version $Id: view.php,v 1.0 2009/01/28 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package swf
 **/

/// (Replace swf with the name of your module)

	require_once("../../config.php");
    require_once("lib.php");
	
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // swf ID
	
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $swf = get_record("swf", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $swf = get_record("swf", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $swf->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("swf", $swf->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "swf", "view", "view.php?id=$cm->id", "$swf->id");

/// Print the page header
    $strswfs = get_string("modulenameplural", "swf");
    $strswf  = get_string("modulename", "swf");
	$swf->instance = $id; // Add course module ID
	
	// Print Javascript head code that embeds SWF file using SWFObject. If SWFObject fails
	// for some reason, the standard <embed> and <object> HTML code should work.
	// The "swf_print_header_js()" function is in mod/swf/lib.php
    print_header_simple(format_string($swf->name), "", get_string('swf', 'swf').': '.$swf->name, "", swf_print_header_js($swf), true, update_module_button($cm->id, $course->id, $strswf), navmenu($course, $cm));
	
	// Everything between the myAlternativeContent <div> tags is 
	// overwritten by SWFObject.
	echo swf_print_body($swf); // mod/swf/lib.php
	
/// Finish the page
    print_footer($course);
	
	//$swf_obj = swf_get_interactions($swf->moduleid);
	//print_object($swf_obj);
// End of mod/swf/view.php
?>
