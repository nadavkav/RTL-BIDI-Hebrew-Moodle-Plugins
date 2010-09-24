<?php
/**
 * update_locations.php
 *
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.1
 * Attempts to update user locations for all users who don't have location set.
 * Will not update locations for students who have opted out of being show on the map
 * If Map field requireok equals "1" then this page will not update any locations
 *
 */
require_once("../../config.php");
require_once("lib.php");

$id         = required_param('id', PARAM_INT);                 // Course Module ID
$action     = optional_param('action', '', PARAM_ALPHA);

require_once("map_security_check.php");

if(!has_capability("mod/map:setotherslocation",$context,$USER->id)){

	redirect("view.php?id=$cm->id",get_string("error") . " - " . get_string("noaccess",map));
}
if($map->requireok == 1){
	redirect("view.php?id=$cm->id",get_string("error") . " - " . get_string("needuserconsent","map"));
}
print_header_simple(format_string($map->name), "",
"<a href=\"index.php?id=$course->id\">$strmaps</a> -> ".format_string($map->name), "", "", true,
update_module_button($cm->id, $course->id, $strmap), navmenu($course, $cm));
$setLocations = get_records("map_locations","mapid",$map->id,'userid');

//get all users who can be shown on the map
$users = get_users_by_capability($context, 'mod/map:autoupdatelocation', 'u.id, u.picture, u.firstname, u.lastname, u.city,u.country', 'u.firstname ASC');
//print_r($users);
//remove users who already have locations on this map

if(!empty($setLocations)){
	foreach($setLocations as $loc){
		if(empty($loc->title)){
			//this is a user locations
			unset($users[$loc->userid]);
		}
	}
}
// if all users have been removed or no users in course
if(count($users)==0){
	print_box(get_string("nolocationsupdated","map"));
}else{
	//for the user left(don't already have a location)
	foreach($users as $user){
		require_once($CFG->dirroot.'/user/profile/lib.php');
		profile_load_data($user);
		$user->userid = $user->id;
		$user->state = map_get_user_state($user);
		$user->action = "insertlocation";
		$user->mapid = $map->id;
		$user->id = $id;
		if($resultLocation = map_save_location($user)===true){
			//was able to set users location
			print_box("User: $user->firstname $user->lastname - " . get_string("locationset","map"));
		}else{
			//was not able to set user location
			print_box("User: $user->firstname $user->lastname - " . get_string("errorsetlocation","map") . " - " . $resultLocation);
		}
	}
}

print_single_button("view.php?id=" . $id,null,"View Map","post");

?>