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
 * Library of interface functions and constants for module sclipo
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the sclipo specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod-sclipo
 * @copyright 2009 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/// (replace sclipo with the name of your module and delete this line)

$sclipo_EXAMPLE_CONSTANT = 42;     /// for example


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $sclipo An object from the form in mod_form.php
 * @return int The id of the newly inserted sclipo record
 */
function sclipowebclass_add_instance($sclipo) {

    $sclipo->timecreated = time();

    # You may have to add extra stuff in here #
    return insert_record('sclipowebclass', $sclipo);
}

function sclipowebclass_add_event($sclipo) {

    $sclipo->timemodified = time();

    # May have to add extra stuff in here #
    $returnid = insert_record("event", $sclipo);

    return $returnid;
}

function sclipowebclass_update_event($sclipo) {

    $sclipo->timemodified = time();

    # May have to add extra stuff in here #
    $returnid = update_record("event", $sclipo);

    return $returnid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $sclipo An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function sclipowebclass_update_instance($sclipo) {

    # You may have to add extra stuff in here #

    return update_record('sclipowebclass', $sclipo);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function sclipowebclass_delete_instance($id) {
	require_once("sclipoapi.php");
	global $USER;
	global $CFG;

    if (! $sclipo = get_record("sclipowebclass", "id", "$id")) {
        return false;
    }

    $result = true;
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	if (sclipo_checkLogin($_SESSION["sclipo_id"], $USER->username) == 0) {
		// Need to log in
		$redirectpage = $pageURL;
		$cssfile=$CFG->wwwroot;
		$cssfile.="/mod/sclipowebclass/css/";

		$navlinks = array();
		$navlinks[] = array('name' => "Sclipo Live Web Class", 'link' => "", 'type' => 'activity');
		$navlinks[] = array('name' => "Create & Schedule Web Classes", 'link' => '', 'type' => 'action');
		$navigation = build_navigation($navlinks);

		print_header_simple("Sclipo Live Web Class", '', $navigation, "", "", false);
		$icon = '<img class="icon" src="../mod/sclipowebclass/icon.gif" alt="Sclipo"/>';

        print_heading_with_help("Create & Schedule Your Sclipo Web Classes", "mods", "sclipowebclass", $icon);
        print_simple_box_start('center', '', '', 5, 'generalbox', "sclipowebclass");
		echo "<center><strong>Please log in first to Sclipo through Moodle</strong></center>";

		$redirectpage = $CFG->wwwroot."/course/view.php?id=".$sclipo->course;
		$delete = 1;
		include("mod.html");
		exit();
	}
	if (sclipo_getUserIDFromSession($_SESSION["sclipo_id"], $USER->username) != $sclipo->teacherid) {
		// No permission
		$redirectpage = $pageURL;
		$cssfile=$CFG->wwwroot;
		$cssfile.="/mod/sclipowebclass/css/";

		$navlinks = array();
		$navlinks[] = array('name' => "Sclipo Live Web Class", 'link' => "", 'type' => 'activity');
		$navlinks[] = array('name' => "Create & Schedule Web Classes", 'link' => '', 'type' => 'action');
		$navigation = build_navigation($navlinks);

		print_header_simple("Sclipo", '', $navigation, "", "", false);
		$icon = '<img class="icon" src="../mod/sclipowebclass/icon.gif" alt="Sclipo"/>';

        print_heading_with_help("Create & Schedule Your Sclipo Web Classes", "mods", "sclipowebclass", $icon);
        print_simple_box_start('center', '', '', 5, 'generalbox', "sclipowebclass");
		echo "<center><strong>Only the teacher who created the web class can delete it</strong></center>";
		exit();
	}

	// Delete web class at sclipo server
	sclipo_deleteWebClass($_SESSION["sclipo_id"], $USER->username, $sclipo->reference);

    # Delete any dependent records here #
    if (! delete_records("sclipowebclass", "id", "$sclipo->id")) {
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
 */
function sclipowebclass_user_outline($course, $user, $mod, $sclipo) {
    $return = new stdClass;
    $return->time = 0;
    $return->info = '';
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function sclipowebclass_user_complete($course, $user, $mod, $sclipo) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in sclipo activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function sclipowebclass_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

function sclipowebclass_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function sclipowebclass_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of sclipo. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $sclipoid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function sclipowebclass_get_participants($sclipoid) {
    return false;
}


/**
 * This function returns if a scale is being used by one sclipo
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $sclipoid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function sclipowebclass_scale_used($sclipoid, $scaleid) {
    global $DB;

    $return = false;

    //$rec = $DB->get_record("sclipo", array("id" => "$sclipoid", "scale" => "-$scaleid"));
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of sclipo.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any sclipo
 */
function sclipowebclass_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('sclipo', 'grade', -$scaleid)) {
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
function sclipowebclass_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function sclipowebclass_uninstall() {
    return true;
}

?>