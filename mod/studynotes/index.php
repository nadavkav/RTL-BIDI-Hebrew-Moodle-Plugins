<?php // $Id: index.php,v 1.3 2009/06/22 07:37:09 fabiangebert Exp $
/**
 * This page lists all the instances of studynotes in a particular course
 *
 * @author
 * @version $Id: index.php,v 1.3 2009/06/22 07:37:09 fabiangebert Exp $
 * @package studynotes
 **/

/// Replace studynotes with the name of your module

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record("course", "id", $id)) {
	error("Course ID is incorrect");
}

require_login($course->id);

$studynotess = get_all_instances_in_course("studynotes", $course);

if(count($studynotess)==1) {
	header("Location: ".$CFG->wwwroot.'/mod/studynotes/view.php?id='.$studynotess[0]->coursemodule);
}

add_to_log($course->id, "studynotes", "view all", "index.php?id=$course->id", "");


/// Get all required stringsstudynotes

$strstudynotess = get_string("modulenameplural", "studynotes");
$strstudynotes  = get_string("modulename", "studynotes");


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strstudynotess, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple("$strstudynotess", "", $navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $studynotess) {
	notice("There are no studynotess", "../../course/view.php?id=$course->id");
	die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

if ($course->format == "weeks") {
	$table=(object)null;
	$table->head  = array ($strweek, $strname);
	$table->align = array ("center", "left");
} else if ($course->format == "topics") {
	$table->head  = array ($strtopic, $strname);
	$table->align = array ("center", "left", "left", "left");
} else {
	$table->head  = array ($strname);
	$table->align = array ("left", "left", "left");
}

foreach ($studynotess as $studynotes) {
	if (!$studynotes->visible) {
		//Show dimmed if the mod is hidden
		$link = "<a class=\"dimmed\" href=\"view.php?id=$studynotes->coursemodule\">$studynotes->name</a>";
	} else {
		//Show normal if the mod is visible
		$link = "<a href=\"view.php?id=$studynotes->coursemodule\">$studynotes->name</a>";
	}

	if ($course->format == "weeks" or $course->format == "topics") {
		$table->data[] = array ($studynotes->section, $link);
	} else {
		$table->data[] = array ($link);
	}
}

echo "<br />";

print_table($table);

/// Finish the page

print_footer($course);

?>
