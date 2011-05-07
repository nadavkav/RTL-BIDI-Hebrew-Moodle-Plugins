<?php
/**
 * map_security_check.php
 * 
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.1
 * Makes sure the user is logged and should be able see the map
 *
*/
 
$id         = required_param('id', PARAM_INT);                 // Course Module ID
$action     = optional_param('action', '', PARAM_ALPHA);


if (! $cm = get_coursemodule_from_id('map', $id)) {
	error("Course Module ID was incorrect");
}

if (! $course = get_record("course", "id", $cm->course)) {
	error("Course is misconfigured");
}

require_course_login($course, false, $cm);

if (!$map = map_get_map($cm->instance)) {
	error("Course module is incorrect");
}

$strmap = get_string('modulename', 'map');
$strmaps = get_string('modulenameplural', 'map');

if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
	print_error('badcontext');
}

//check to make sure the module is set correcty

if(!map_config_ok()){

	error(get_string("badconfig","map"));
	exit();
}

?>