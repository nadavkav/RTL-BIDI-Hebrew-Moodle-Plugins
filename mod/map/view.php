<?php  // $Id
/**
 * view.php
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.1
 * Displays a particular instance of a map.
 * There 2 kinds of locations on a map.
 * User locations: represents where a user lives.  These locations only show city level for privacy reasons.  The users picture and description are show from their profile.
 * Extra locations: other locations to show on a map.
 * A map can have either or both of these locations.
 * Also:
 * Form to update user location(where they live), if the options is enabled on the map.  This form doesn't have an address for privacy reasons.
 * Button to update all user locations
 * Table of current user's extra locatoins
 *
 *
 */
require_once("../../config.php");
require_once("lib.php");
require_once("map.class.php");

require_once("map_security_check.php");


/// Submit any new data if there is any
// Only the user location will be submitted on this page.
if ($form = data_submitted() && has_capability('mod/map:setownlocation', $context) && isset($USER->id)) {
	
	$timenow = time();
	$action = required_param('action', PARAM_ALPHA);
	if($action=="resetlocation"){
		$form = new mod_map_reset_location_form();
	}else{
		$form = new mod_map_user_location_form();
	}
	if($form->is_cancelled()){
		redirect("view.php?id=$cm->id", get_string("actioncanceled","map"));
		exit;
	}
	if(!$form->is_validated()){
		redirect("view.php?id=$cm->id", get_string("submiterror","map"));
		exit;
	}
	$locationSuccess = map_save_location($form->get_data());
	if($locationSuccess===true){
		redirect("view.php?id=$cm->id",get_string("actionsuccessfull","map"));
		exit;
	}else{
		redirect("view.php?id=$cm->id",get_string("actionfailed","map") ." - " . $locationSuccess);
		exit;
	}
}





/// Display the map and locations


print_header_simple(format_string($map->name), "",
"<a href=\"index.php?id=$course->id\">$strmaps</a> -> ".format_string($map->name), "", "", true,
update_module_button($cm->id, $course->id, $strmap), navmenu($course, $cm));

add_to_log($course->id, "map", "view", "view.php?id=$cm->id", $map->id, $cm->id);

require_once("handle_groups.php");

echo '<div class="clearer"></div>';
if ($map->text) {
	print_box(format_text($map->text, $map->format), 'generalbox', 'intro');
}
//get the locations to show
$map_locations = map_get_locations($map->id,$currentGroup);
$map_js_locations = "";
if($map_locations){
	//print div to hold map
	print_box("<div style='width: 800px;height: 400px; margin-left:auto;margin-right:auto;' id='map'></div>","mapouter");
	//find current users location
	foreach($map_locations as $map_location){
		if($map_location->userid == $USER->id && $map_location->title == ""){
			$user_map_location = $map_location;
		}
	}
	//handle convert locations to JavaScript Array and including Javascript Files
	$map_js_locations =  map_create_locations_js($map_locations,$course->id);
	if($map_js_locations != ""){
		echo $map_js_locations;
		map_load_js_scripts($map);
	}
}else{
	//no locations for map. Just print message
	// Should an empty map be printed.  Where would the default location be?
	print_box(format_text(get_string("emptymap","map"),FORMAT_PLAIN), 'generalbox');
}
//if user has the right and the map doesn't require the students consent to appear on the map
//then show button to update locations for users - locations will be try to be set from users' profile location
if(has_capability("mod/map:setotherslocation",$context,$USER->id) && $map->requireok == 0 && $map->studentlocations == 1){
	print_box(print_single_button("update_locations.php?id=" . $id,null,get_string("updateuserlocations","map"),"post","_self",true),"button");
}
//only allow members to add student locations or extra locations
if($memberOfGroup){
	//should this map show student locations
	if($map->studentlocations == 1 && has_capability('mod/map:setownlocation', $context)){
		$locForm = new mod_map_user_location_form();
		if(!isset($user_map_location)){
			//no user location has been set for current user
			//fill form with profile location
			require_once($CFG->dirroot.'/user/profile/lib.php');
			profile_load_data($USER);
			print_box(format_text(get_string("usernolocation","map"),FORMAT_PLAIN), 'generalbox', 'intro');
			$curLocation = new object();
			$curLocation->city = $USER->city;
			$curLocation->state = map_get_user_state($USER);
			$curLocation->country = $USER->country;
			$curLocation->id = $cm->id;
			$curLocation->action = "insertlocation";
			$curLocation->userid = $USER->id;
			$locForm->set_data($curLocation);
		}else{
			if($user_map_location->showcode == 0){
				//user has explicitly said they don't want to show up on the map
				$locForm = new mod_map_reset_location_form();
			}
			//user has set location
			$curLocation = clone $user_map_location;
			$curLocation->id = $cm->id;
			$curLocation->locationid = $user_map_location->id;
			$locForm->set_data($curLocation);
		}
		$locForm->display();
	}

	//if user has the right show button to add an "extra location
	if(($map->extralocations == 1 && has_capability('mod/map:setextralocation', $context))  || has_capability('mod/map:alwayssetextralocation', $context)){
		print_box_start("button");
		print_single_button("extraLocationForm.php?id=$id&action=add",null,get_string("addextralocation","map"),"post");
		print_box_end();
	}
	// display  user's extra locations
	map_print_extra_locations($map_locations,$id);
}

print_footer($course);


?>
