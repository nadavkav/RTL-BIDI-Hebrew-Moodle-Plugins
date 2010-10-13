<?php 
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once dirname(__FILE__).'/inc.php';

$courseid = optional_param("courseid", 0, PARAM_INT);

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);
require_capability('block/exabis_eportfolio:importfrommoodle', $context);

if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

block_exabis_eportfolio_print_header("exportimportmoodleimport");

$assignments = get_records_sql("SELECT s.id, s.assignment, s.timemodified, a.name, a.course, c.fullname AS coursename
								FROM {$CFG->prefix}assignment_submissions s
								JOIN {$CFG->prefix}assignment a ON s.assignment=a.id
								LEFT JOIN {$CFG->prefix}course c on a.course = c.id
								WHERE s.userid='{$USER->id}'");

if (right_to_left()) { // rtl table alignment support (nadavkav patch)
$alignment = "left";
} else {
$alignment = "right";
}
$table = new stdClass();   
$table->head  = array (get_string("modulename","assignment"), get_string("time"), get_string("file"), get_string("course","block_exabis_eportfolio"), get_string("action"));
$table->align = array($alignment,$alignment, $alignment, $alignment, $alignment);
$table->size = array("20%", "20%", "25%", "20%", "15%");
$table->width = "85%";

if($assignments) {
	foreach ($assignments as $assignment) {
		$basedir = block_exabis_eportfolio_moodleimport_file_area_name($USER->id, $assignment->assignment, $assignment->course);
		if ($files = get_directory_list($CFG->dataroot . '/' . $basedir)) {

			unset($table->data);
			unset($icons);
			$icons = '';
			require_once($CFG->libdir.'/filelib.php');

			foreach ($files as $key => $file) {
				$icon = mimeinfo('icon', $file);
				
				if ($CFG->slasharguments) {
					$ffurl = $CFG->wwwroot . '/file.php/' . $basedir . '/' . $file;
				} else {
					$ffurl = $CFG->wwwroot . '/file.php?file=/' . $basedir . '/' . $file;
				}
				
				$icons .= '<a href="' . $CFG->wwwroot . '/blocks/exabis_eportfolio/import_moodle_add_file.php?courseid=' . $courseid . '&amp;assignmentid=' . $assignment->id . '&amp;filename=' . $file . '&amp;sesskey=' . sesskey() . '">' .
						  get_string("add_this_file", "block_exabis_eportfolio") . '</a>';

				$table->data[] = array($assignment->name, userdate($assignment->timemodified), '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
						'<a href="'.$ffurl.'" >'.$file.'</a><br />', $assignment->coursename, $icons);
			}
			print_table($table);
		}
	}
}
else {
	echo "<p>" .get_string("nomoodleimportyet", "block_exabis_eportfolio"). "</p>";
}

print_footer($course);
