<?php
/**
 * extraLocationForm.php
 * 
 * @package map
 * @author Ted Bowman <ted@tedbow.com>
 * @version 0.2
 * Shows/handles form to add/edit an "extra" location on the map
 *
*/
require_once("../../config.php");
require_once("lib.php");
require_once("map.class.php");

$id         = required_param('id', PARAM_INT);                 // Course Module ID
$action     = optional_param('action', '', PARAM_ALPHA);
$locationid     = optional_param('locationid', '', PARAM_INT);

require_once("map_security_check.php");


print_header_simple(format_string($map->name), "",
"<a href=\"index.php?id=$course->id\">$strmaps</a> -> ".format_string($map->name), "", "", true);
//make sure map allows extra locations and user has the right to add
if(($map->extralocations == 1 && has_capability('mod/map:setextralocation', $context))  || has_capability('mod/map:alwayssetextralocation', $context)){
	
	$lForm = new mod_map_extralocation_form();
	if (data_submitted()){
		$lForm = new mod_map_extralocation_form();
		//$lForm->_form->removeElement("country");
		
		if($lForm->is_validated()){
			$locationSuccess = map_save_location($lForm->get_data());
			if($locationSuccess===true){
				redirect("view.php?id=$cm->id",get_string("actionsuccessfull","map"));
				exit;
			}else{
				redirect("view.php?id=$cm->id",get_string("actionfailed","map") ." - " . $locationSuccess);
				exit;
			}	
		}
	
	}
	if($action == "delete"){
		if(delete_records("map_locations","id",$locationid)===false){
			redirect("view.php?id=$cm->id",get_string("actionfailed","map"));
		}else{
			redirect("view.php?id=$cm->id",get_string("actionsuccessfull","map"));
		}
		exit;
	}
	if($action == "edit"){
		$loc = get_record("map_locations","id",$locationid);
		$loc->action = "updatelocation";
	}
	$loc->locationid = $locationid;
	$loc->id = $id;
	if($map->showaddress4extra !=2){
		$lForm->removePoint();
	}
	if($map->showaddress4extra == 0){
		$lForm->removeAddress();
	}
	$lForm->set_data($loc);
	$lForm->display();
}else{
	redirect("view.php?id=$cm->id",get_string("error") . " - " . get_string("noaccess",map));
	exit;
}



?>