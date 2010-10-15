<?php
require_once("../../config.php");
require_once("lib.php");

//Course
$id = required_param('id', PARAM_INT);

if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

//Require login
require_login($course->id);

//Get course shortname
$courseshortname = get_record("course", "id", $_REQUEST['id'], "", "", "", "", "shortname");

// Get all required strings econsole
$strconsolestrers = get_string("modulenameplural", "econsole");
$streconsole  = get_string("modulename", "econsole");

//Log
add_to_log($course->id, "econsole", "view all", "index.php?id=".$course->id, "");

// Print the header
$navlinks = array();
$navlinks[] = array('name' => $strconsolestrers, 'link' => '', 'type' => 'activity');
if (function_exists('build_navigation')){
	//Moodle 1.9 ou superior
   	$navigation = build_navigation($navlinks);
}else{
	$navigation=$strconsolestrers;
}
print_header_simple("$strconsolestrers", "", $navigation, "", "", true, "", navmenu($course));

//Get all the appropriate data
if (! $consolestrers = get_all_instances_in_course("econsole", $course)) {
    notice("There are no E-Consoles", "../../course/view.php?id=$course->id");
    die;
}

//Print the list of instances (your module will probably extend this)
$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $streconsole);
    $table->align = array ("center", "left");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $streconsole);
    $table->align = array ("center", "left", "left", "left");
} else {
    $table->head  = array ($streconsole);
    $table->align = array ("left", "left", "left");
}

foreach ($consolestrers as $econsole) {

	//$econsole->section => id mdl_console
	//$econsole->coursemodule => id mdl_course_modules

    if (!$econsole->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$econsole->coursemodule&index=1\">$econsole->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$econsole->coursemodule&index=1\">$econsole->name</a>";
    }		
		
    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array ($econsole->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";

print_table($table);

//Finish the page
print_footer($course);
?>
