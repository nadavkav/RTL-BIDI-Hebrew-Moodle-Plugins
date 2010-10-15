<?php 
// $Id: lib.php,v 1.8 2007/12/12 00:09:46 stronk7 Exp $
/**
 * Library of functions and constants for module console
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the console specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
**/

// for example
//$econsole_CONSTANT = 7;

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted console record
**/
function econsole_add_instance($econsole) {
    
    // temp added for debugging
	//echo "ADD INSTANCE CALLED";
   	// print_object($econsole);
    
    $econsole->timecreated = time();

    # May have to add extra stuff in here #
    
    return insert_record("econsole", $econsole);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
**/
function econsole_update_instance($econsole) {

    $econsole->timemodified = time();
    $econsole->id = $econsole->instance;

    # May have to add extra stuff in here #

    return update_record("econsole", $econsole);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
**/
function econsole_delete_instance($id) {

    if (! $econsole = get_record("econsole", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("econsole", "id", "$econsole->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
**/
function econsole_user_outline($course, $user, $mod, $econsole) {
    if ($logs = get_records_select("log", "userid='$user->id' AND module='econsole'
                                           AND action='view' AND info='$econsole->id'", "time ASC")) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new object();
        $result->info = get_string("numviews", "", $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
**/
function econsole_user_complete($course, $user, $mod, $econsole) {
    global $CFG;

    if ($logs = get_records_select("log", "userid='$user->id' AND module='econsole'
                                           AND action='view' AND info='$econsole->id'", "time ASC")) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string("mostrecently");
        $strnumviews = get_string("numviews", "", $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string("neverseen", "econsole");
    }
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in console activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
**/
function econsole_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
**/
function econsole_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $econsoleid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
**/
function econsole_grades($econsoleid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of console. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $econsoleid ID of an instance of this module
 * @return mixed boolean/array of students
**/
function econsole_get_participants($econsoleid) {
    return false;
}

/**
 * This function returns if a scale is being used by one console
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $econsoleid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
**/
function econsole_scale_used ($econsoleid,$scaleid) {
    $return = false;

    //$rec = get_record("console","id","$econsoleid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Checks if scale is being used by any instance of console.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any console
**/
function econsole_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('console', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
**/
function econsole_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
**/
function econsole_uninstall() {
    return true;
}

/**
 * Execute query for get all module instances in the curse whole
 *
 * @return the module ids in csv format
 * 
 * By Omar Salib - TRE-RS
**/
function econsole_get_all_instances_in_course($modulename, $coursemodinfo){
	$modules = get_all_instances_in_course($modulename, $coursemodinfo);
	$moduleids = "";
	foreach($modules as $module){
		$moduleids .= $module->coursemodule.",";
	}
	return substr($moduleids,0,-1);
}

/**
 * Execute query for get all module instances in the topic only
 *
 * @return the module ids in csv format
 * 
 * By Omar Salib - TRE-RS
**/
function econsole_get_all_instances_in_topic($modulename, $section, $coursemodinfo){
	$modules = get_all_instances_in_course($modulename, $coursemodinfo);
	$moduleids = "";
	foreach($modules as $module){
		if($module->section == $section){
			$moduleids .= $module->coursemodule.",";
		}
	}
	return substr($moduleids,0,-1);
}

/**
 * Execute query for get all module instances only in the econsole
 *
 * @return the module ids in csv format
 * 
 * By Omar Salib - TRE-RS
**/
function econsole_get_all_instances_in_econsole($modulename, $section, $coursemodinfo, $sequence){
	$modules = get_all_instances_in_course($modulename, $coursemodinfo);
	$moduleids = "";
	foreach($modules as $module){
		if($module->section == $section){
			if(in_array($module->coursemodule, $sequence)){
				$moduleids .= $module->coursemodule.",";
			}
		}
	}
	return substr($moduleids,0,-1);
}

/**
 * Create buttons for bottom econsole screen
 *
 * @return the html code
 * 
 * By Omar Salib - TRE-RS
**/
function econsole_get_buttons($modulename, $ids=''){
	$btn = "";
	if(!empty($ids)){
		$modules = split(",",$ids);
		foreach($modules as $module){
			$instance = get_record("course_modules", "id", $module, "", "", "", "", "instance");
			$name = get_record($modulename, "id", $instance->instance, "", "", "", "", "name");			
			//OnMouse: replaceImage();
		 	$btn .= "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/".$modulename.".gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/".$modulename."over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/".$modulename.".gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='../".$modulename."/view.php?id=".$module."';\" onMouseMove=\"showTitleRight(event,'".$name->name."','');\"></a>&nbsp;";
			//OnMouse: changeDimensions();
		 	//$btn .= "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/".$modulename.".gif\" alt=\"\" title=\"\" class=\"btn\" style=\"width: 28; height: 28\"; border=\"0\" onMouseOver=\"Javascript: changeDimensions(this, '+4', '+4');\" onMouseOut=\"Javascript: changeDimensions(this, '-4', '-4'); hideTitle();\" onClick=\"Javascript: window.parent.opener.location.href='../".$modulename."/view.php?id=".$module."';\" onMouseMove=\"showTitle(event,'".$name->name."','');\"></a>&nbsp;";
		}
	}else{
 		$btn = "<img src=\"theme/".$_REQUEST["thm"]."/img/btn/".$modulename.".gif\" alt=\"\" title=\"\" border=\"0\" class=\"transparent\">&nbsp;";
	}
	return $btn;
}

function econsole_get_buttons_urls($id){
	$instance = get_record("course_modules", "id", $id, "", "", "", "", "instance");
	$urls = get_record("econsole", "id", $instance->instance, "", "", "", "", "url1name, url1, url2name, url2, url3name, url3, url4name, url4, url5name, url5, url6name, url6");
	$btn = !empty($urls->url1) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url1.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url1over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url1.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url1."';\" onMouseMove=\"showTitleRight(event,'".$urls->url1name."','');\"></a>&nbsp;" : "";
	$btn .= !empty($urls->url2) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url2.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url2over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url2.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url2."';\" onMouseMove=\"showTitleRight(event,'".$urls->url2name."','');\"></a>&nbsp;" : "";	
	$btn .= !empty($urls->url3) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url3.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url3over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url3.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url3."';\" onMouseMove=\"showTitleRight(event,'".$urls->url3name."','');\"></a>&nbsp;" : "";
	$btn .= !empty($urls->url4) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url4.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url4over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url4.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url4."';\" onMouseMove=\"showTitleRight(event,'".$urls->url4name."','');\"></a>&nbsp;" : "";	
	$btn .= !empty($urls->url5) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url5.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url5over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url5.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url5."';\" onMouseMove=\"showTitleRight(event,'".$urls->url5name."','');\"></a>&nbsp;" : "";		
	$btn .= !empty($urls->url6) ? "<a href=\"#\"><img src=\"theme/".$_REQUEST["thm"]."/img/btn/url6.gif\" alt=\"\" title=\"\" class=\"btn\" border=\"0\" onMouseOver=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url6over.gif');\" onMouseOut=\"Javascript: replaceImage(this, 'theme/".$_REQUEST["thm"]."/img/btn/url6.gif'); hideTitle();\" onClick=\"Javascript: window.parent.document.getElementById('mainFrame').src='".$urls->url6."';\" onMouseMove=\"showTitleRight(event,'".$urls->url6name."','');\"></a>&nbsp;" : "";		
	return $btn;
}
?>
