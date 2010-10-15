<?php  // $Id: lib.php,v 1.0 2009/09/28 matbury Exp $
/**
* Library of functions and constants for module swf
* 
* @author Matt Bury - matbury@gmail.com - http://matbury.com/
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
* @package swf
*/

/*
*    Copyright (C) 2009  Matt Bury - matbury@gmail.com - http://matbury.com/
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted swf record
 **/
function swf_add_instance($swf) {
    
    $swf->timecreated = time();

    # May have to add extra stuff in here #
	
	return insert_record('swf', $swf);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function swf_update_instance($swf) {

    $swf->timemodified = time();
    $swf->id = $swf->instance;
	
	# May have to add extra stuff in here #
		
    return update_record("swf", $swf);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function swf_delete_instance($id) {

    if (! $swf = get_record("swf", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("swf", "id", "$swf->id")) {
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
function swf_user_outline($course, $user, $mod, $swf) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function swf_user_complete($course, $user, $mod, $swf) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in swf activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function swf_print_recent_activity($course, $isteacher, $timestart) {
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
function swf_cron () {
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
 * @param int $swfid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function swf_grades($swfid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of swf. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $swfid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function swf_get_participants($swfid) {
    return false;
}

/**
 * This function returns if a scale is being used by one swf
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $swfid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function swf_scale_used ($swfid,$scaleid) {
    $return = false;

    //$rec = get_record("swf","id","$swfid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Checks if scale is being used by any instance of swf.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any swf
 */
function swf_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('swf', 'grade', -$scaleid)) {
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
 */
function swf_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function swf_uninstall() {
    return true;
}

/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////

/// Specific generic functions for SWF Activity Module


// ------------------------------------------------------------ mod_form.php ---------------------------------------------------------- //

// The following function for mod_form.php all return arrays for the drop down lists in the SWF Activity Module instance creator form

/**
* Retrieve list of available interactions for current course
*
* @param $swf_courseid int
* @return array
*/
function swf_get_interactions($swf_courseid) {
	$swf_interactions = get_records('swf_interactions', 'course', $swf_courseid);
	$swf_select_interaction = array('' => 'none');
	if($swf_interactions) {
		foreach($swf_interactions as $swf_value)
		{
			$swf_select_interaction[$swf_value->id] = $swf_value->name;
		}
		unset($swf_value);
	}
	return $swf_select_interaction;
}

/*
* @return array
*/
function swf_list_align() {
	$swf_align_array = array('middle' => 'middle',
							'left' => 'left',
							'right' => 'right',
							'top' => 'top',
							'bottom' => 'bottom');
	return $swf_align_array;
}

/*
* @return array
*/
function swf_list_allownetworking() {
	$swf_list_allownetworking = array('all' => 'all',
									'internal' => 'internal',
									'none' => 'none');
	return $swf_list_allownetworking;
}

/*
* @return array
*/
function swf_list_allowscriptaccess() {
	$swf_list_allowscriptaccess = array('always' => 'always',
										'sameDomain' => 'sameDomain',
										'never' => 'never');
	return $swf_list_allowscriptaccess;
}

/*
* Create an associative array from 0 - 100
*
* @return array
*/
function swf_list_grading() {
	$swf_list_grading = array();
	for($i = 0; $i < 101; $i++) {
		$swf_list_grading["$i"] = "$i";
	}
	return $swf_list_grading;
}

/*
* @return array
*/
function swf_list_quality() {
	$swf_list_quality = array('best' => 'best',
							'high' => 'high',
							'medium' => 'medium',
							'autohigh' => 'autohigh',
							'autolow' => 'autolow',
							'low' => 'low');
	return $swf_list_quality;
}

/*
* @return array
*/
function swf_list_salign() {
	$swf_list_salign = array('tl' => 'top left',
							'tr' => 'top right',
							'bl' => 'bottom left',
							'br' => 'bottom right',
							'l' => 'left',
							't' => 'top',
							'r' => 'right',
							'b' => 'bottom');
	return $swf_list_salign;
}

/*
* @return array
*/
function swf_list_scale() {
	$swf_list_scale = array('showall' => 'showall',
							'noborder' => 'noborder',
							'exactfit' => 'exactfit',
							'noscale' => 'noscale');
	return $swf_list_scale;
}

/*
* @return array
*/
function swf_list_skins() {
	$swf_list_skins = array('default' => '',
							'skins/gradient_square_blue.swf' => 'Square blue gradient',
							'skins/shiny_round_red.swf' => 'Shiny round red');
	return $swf_list_skins;
}

/*
* @return array
*/
function swf_list_truefalse() {
	$swf_list_truefalse = array('true' => 'true',
								'false' => 'false');
	return $swf_list_truefalse;
}

/*
* @return array
*/
function swf_list_wmode() {
	$swf_list_wmode = array('window' => 'window',
							'opaque' => 'opaque',
							'transparent' => 'transparent',
							'direct' => 'direct',
							'gpu' => 'gpu');
	return $swf_list_wmode;
}

// ------------------------------------------------------------ view.php ---------------------------------------------------------- //

/**
* Construct Javascript SWFObject embed code for <head> section of view.php
* Note: '?'.time() is used to prevent browser caching for XML and SWF files.
*
* @param $swf (mdl_swf DB record for current SWF module instance)
* @return string
*/
function swf_print_header_js($swf) {
	global $CFG;
	// Build URL to AMFPHP (Flash Remoting) service
	// Moodle 1.8 and 1.9 only
	// This will be replaced by Zend_Amf in Moodle 2.0
	$swf_gateway = $CFG->wwwroot.'/lib/amfphp/gateway.php';
	// Build URL to moodledata directory
	// This is where SWF files and media should be stored
	$swf_moodledata = $CFG->wwwroot.'/file.php/'.$swf->course.'/';
	// e.g. http://yoursite.com/file.php/99/
	// Build URL back to course page. Useful for redirects from embedded swfs.
	// There's no need to redirect to user feedback pages as these can be easily handled by swfs.
	$swf_coursepage = $CFG->wwwroot.'/course/view.php?id='.$swf->course;
	// e.g. http://yoursite.com/course/view.php?id=99
	// Prevent using SWF file in browser cache by attaching time as query string
	$swf_time = time();
	$swf_swfurl = $swf_moodledata.$swf->swfurl.'?'.$swf_time;
	$swf_xmlurl = $swf_moodledata.$swf->xmlurl.'?'.$swf_time;
	// e.g. http://yourmoodlesite.com/file.php/99/swf/flash_file.swf?123513670
	// skin
	$swf_skin = $swf->skin;
	// configxml
	$swf_configxml = $swf_moodledata.$swf->configxml.'?'.$swf_time;
	// Build Javascript code for view.php print_header() function
	$swf_header_js = '<script type="text/javascript" src="swfobject/swfobject.js"></script>
		<script type="text/javascript">
			var flashvars = {};
			flashvars.gateway = "'.$swf_gateway.'";
			flashvars.course = "'.$swf->course.'";
			flashvars.swfid = "'.$swf->id.'";
			flashvars.instance = "'.$swf->instance.'";
			flashvars.interaction = "'.$swf->interaction.'";
			flashvars.moodledata = "'.$swf_moodledata.'";
			flashvars.coursepage = "'.$swf_coursepage.'";
			flashvars.xmlurl = "'.$swf_xmlurl.'";
			flashvars.apikey = "'.$swf->apikey.'";
			flashvars.flashvar1 = "'.$swf->flashvar1.'";
			flashvars.flashvar2 = "'.$swf->flashvar2.'";
			flashvars.flashvar3 = "'.$swf->flashvar3.'";
			flashvars.starttime = "'.$swf_time.'";
			flashvars.grading = "'.$swf->grading.'";
			flashvars.skin = "'.$swf_skin.'";
			flashvars.configxml = "'.$swf_configxml.'";
			var params = {};
			params.play = "'.$swf->width.'";
			params.loop = "'.$swf->loopswf.'";
			params.menu = "'.$swf->menu.'";
			params.quality = "'.$swf->quality.'";
			params.scale = "'.$swf->scale.'";
			params.salign = "'.$swf->salign.'";
			params.wmode = "'.$swf->wmode.'";
			params.bgcolor = "#'.$swf->bgcolor.'";
			params.devicefont = "'.$swf->devicefont.'";
			params.seamlesstabbing = "'.$swf->seamlesstabbing.'";
			params.allowfullscreen = "'.$swf->allowfullscreen.'";
			params.allowscriptaccess = "'.$swf->allowscriptaccess.'";
			params.allownetworking = "'.$swf->allownetworking.'";
			var attributes = {};
			attributes.id = "contentid";
			attributes.align = "middle";
			swfobject.embedSWF("'.$swf_swfurl.'?'.$swf_time.'", "myAlternativeContent", "'.$swf->width.'", "'.$swf->height.'", "'.$swf->version.'", "swfobject/expressInstall.swf", flashvars, params, attributes);
		</script>';
	
	return $swf_header_js;
}

/**
* Construct Javascript SWFObject embed code for <body> section of view.php
* Note: everything between the <div id="myAlternativeContent"></div> tags
* is overwritten by SWFObject. This embed code will only be used if SWFObject
* fails for some reason, e.g. Javascript isn't enabled. In any case, the module
* should function normally.
*
* @param $swf (mdl_swf DB record for current SWF module instance)
* @param $cm module instance data
* @return string
*/
function swf_print_body($swf) {
	global $CFG;
	// Build URL to AMFPHP (Flash Remoting) service
	// Moodle 1.8 and 1.9 only
	// This will be replaced by Zend_Amf in Moodle 2.0
	$swf_gateway = $CFG->wwwroot.'/lib/amfphp/gateway.php';
	// Build URL to moodledata directory
	// This is where SWF files and media should be stored
	$swf_moodledata = $CFG->wwwroot.'/file.php/'.$swf->course.'/';
	// e.g. http://yoursite.com/file.php/99/
	// Build URL back to course page. Useful for redirects from embedded swfs.
	// There's no need to redirect to user feedback pages as these can be easily handled by swfs.
	$swf_coursepage = $CFG->wwwroot.'/course/view.php?id='.$swf->course;
	// e.g. http://yoursite.com/course/view.php?id=99
	// Prevent using SWF file in browser cache by attaching time as query string
	$swf_time = time();
	$swf_swfurl = $swf_moodledata.$swf->swfurl.'?'.$swf_time;
	$swf_xmlurl = $swf_moodledata.$swf->xmlurl.'?'.$swf_time;
	// e.g. http://yourmoodlesite.com/file.php/99/swf/flash_file.swf?123513670
	// skin
	$swf_skin = $swf->skin;
	// configxml
	$swf_configxml = $swf_moodledata.$swf->configxml.'?'.$swf_time;
	//
	$swf_body = '<div align="center">
		<div id="myAlternativeContent">
			<div>
			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$swf->width.'" height="'.$swf->height.'" id="contentid" align="'.$swf->align.'">
				<param name="movie" value="'.$swf_swfurl.'" />
				<param name="play" value="'.$swf->play.'" />
				<param name="loop" value="'.$swf->loopswf.'" />
				<param name="menu" value="'.$swf->menu.'" />
				<param name="quality" value="'.$swf->quality.'" />
				<param name="scale" value="'.$swf->scale.'" />
				<param name="salign" value="'.$swf->salign.'" />
				<param name="wmode" value="'.$swf->wmode.'" />
				<param name="bgcolor" value="#'.$swf->bgcolor.'" />
				<param name="devicefont" value="'.$swf->devicefont.'" />
				<param name="seamlesstabbing" value="'.$swf->seamlesstabbing.'" />
				<param name="allowfullscreen" value="'.$swf->allowfullscreen.'" />
				<param name="allowscriptaccess" value="'.$swf->allowscriptaccess.'" />
				<param name="allownetworking" value="'.$swf->allownetworking.'" />
				<param name="flashvars" value="gateway='.$swf_gateway.'&amp;course='.$swf->course.'&amp;swfid='.$swf->id.'&amp;interaction='.$swf->interaction.'&amp;instance='.$swf->instance.'&amp;moodledata='.$swf_moodledata.'&amp;coursepage='.$swf_coursepage.'&amp;swfurl='.$swf_swfurl.'&amp;xmlurl='.$swf_xmlurl.'&amp;apikey='.$swf->apikey.'&amp;flashvar1='.$swf->flashvar1.'&amp;flashvar2='.$swf->flashvar2.'&amp;flashvar3='.$swf->flashvar3.'&amp;starttime='.$swf_time.'&amp;grading='.$swf->grading.'&amp;skin='.$swf_skin.'&amp;configxml='.$swf_configxml.'" />
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="'.$swf_swfurl.'" width="'.$swf->width.'" height="'.$swf->height.'" align="'.$swf->align.'">
					<param name="play" value="'.$swf->play.'" />
					<param name="loop" value="'.$swf->loopswf.'" />
					<param name="menu" value="'.$swf->menu.'" />
					<param name="quality" value="'.$swf->quality.'" />
					<param name="scale" value="'.$swf->scale.'" />
					<param name="salign" value="'.$swf->salign.'" />
					<param name="wmode" value="'.$swf->wmode.'" />
					<param name="bgcolor" value="#'.$swf->bgcolor.'" />
					<param name="devicefont" value="'.$swf->devicefont.'" />
					<param name="seamlesstabbing" value="'.$swf->seamlesstabbing.'" />
					<param name="allowfullscreen" value="'.$swf->allowfullscreen.'" />
					<param name="allowscriptaccess" value="'.$swf->allowscriptaccess.'" />
					<param name="allownetworking" value="'.$swf->allownetworking.'" />
					<param name="flashvars" value="gateway='.$swf_gateway.'&amp;course='.$swf->course.'&amp;swfid='.$swf->id.'&amp;interaction='.$swf->interaction.'&amp;instance='.$swf->instance.'&amp;moodledata='.$swf_moodledata.'&amp;coursepage='.$swf_coursepage.'&amp;swfurl='.$swf_swfurl.'&amp;xmlurl='.$swf_xmlurl.'&amp;apikey='.$swf->apikey.'&amp;flashvar1='.$swf->flashvar1.'&amp;flashvar2='.$swf->flashvar2.'&amp;flashvar3='.$swf->flashvar3.'&amp;starttime='.$swf_time.'&amp;grading='.$swf->grading.'&amp;skin='.$swf_skin.'&amp;configxml='.$swf_configxml.'" />
				<!--<![endif]-->'.get_string('embederror','swf').'
<div align="center">
  <p><strong>This activity requires <a href="http://www.adobe.com/products/flashplayer/">Flash Player '.$swf->version.'</a> to be installed.</strong></p>
  <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" border=0/>
    </a>
    </p>
</div>
				<!--[if !IE]>-->
				</object>
				<!--<![endif]-->
			</object>
		</div>
	</div>';
	return $swf_body;
}
/// End of mod/swf/lib.php
?>