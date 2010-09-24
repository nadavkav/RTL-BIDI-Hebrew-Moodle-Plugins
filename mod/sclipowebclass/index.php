<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod-sclipo
 * @copyright 2009 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// Replace sclipo with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);

add_to_log($course->id, 'sclipowebclass', 'view all', "index.php?id=$course->id", '');

/// Print the header
/*
$PAGE->set_url('mod/sclipo/view.php', array('id' => $id));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->shortname);
*/

// todo navigation will be changed yet for Moodle 2.0
$navlinks = array();
$navlinks[] = array('name' => get_string('modulenameplural', 'sclipowebclass'),
                    'link' => '',
                    'type' => 'activity');
$navigation = build_navigation($navlinks);
$navlinks = array();

print_header_simple(get_string("editinga", "moodle", "sclipowebclass"), '', $navigation, "form.content", "", false);

print_simple_box_start('center', '', '', 5, 'generalbox', "sclipowebclass");
//echo $OUTPUT->header($navigation);

/// Get all the appropriate data

if (! $sclipos = get_all_instances_in_course('sclipowebclass', $course)) {
	echo "<strong><center>There are currently no Sclipo Live Web Classes scheduled</center></strong>";
	print_footer($course);
    die();
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($sclipos as $sclipo) {
    if (!$sclipo->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$sclipo->coursemodule\">$sclipo->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$sclipo->coursemodule\">$sclipo->name</a>";
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($sclipo->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

//echo $OUTPUT->heading(get_string('modulenameplural', 'sclipo'), 2);
print_table($table);

/// Finish the page

print_footer($course);

?>