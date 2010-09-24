<?php // $Id: mysql.php,v 1.34 2005/04/27 06:14:00 moodler Exp $
require_once($CFG->dirroot.'/lib/datalib.php');

//Delete function (needed when you delete a course (it deletes every door instance)
function door_delete_instance($id){	
	global $CFG;

    if (! $door = get_record('resource', 'id', $id)) {
        return false;
    }

    require_once("$CFG->dirroot/mod/resource/type/door/resource.class.php");
    $doorinstance = new resource_door();
    return $doorinstance->delete_instance($door);
}


//Functions for the configuration section
function add_repository($name,$address,$authentication) {
	$rep = null;
	$rep->name = $name;
	$rep->address = $address;
	$rep->authentication = $authentication;
	if(insert_record("door_repository", $rep, false, "id"))
		return true;
	else
		return false;
}

function update_repository($id,$name,$address,$authentication){
	if(record_exists("door_repository", "id", $id)){
		$rep = null;
		$rep->id = $id;
		$rep->name = $name;
		$rep->address = $address;
		$rep->authentication = $authentication;
		if(!update_record("door_repository", $rep))
			return false;
		else
			return true;
	}else{
		return false;
	}	
}

function delete_repository($id){
	if(delete_records("door_repository", "id", $id)){
		return true;
	}else{
		return false;
	}
}

function get_all_repositories(){
	return get_records("door_repository","","","authentication,name");
}

function get_all_authentications() {
    return get_records("door_repository_authentications");    
}
?>