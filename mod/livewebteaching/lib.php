<?php

if (!isset($CFG->lwt_server)) {
    set_config('lwt_server', 'http://www.livewebteaching.com/');
}

/**
 * Join a live session room
 *
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 *  
 *  This file is free software: you may copy, redistribute and/or modify it  
 *  under the terms of the GNU General Public License as published by the  
 *  Free Software Foundation, either version 2 of the License, or any later version.  
 *  
 *  This file is distributed in the hope that it will be useful, but  
 *  WITHOUT ANY WARRANTY; without even the implied warranty of  
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU  
 *  General Public License for more details.  
 *  
 *  You should have received a copy of the GNU General Public License  
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.  
 *
 *  This file incorporates work covered by the following copyright and permission notice:
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 *  @copyright 2010 Blindside Networks
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

include( 'bbb_api/bbb_api.php' );


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $livewebteaching An object from the form in mod_form.php
 * @return int The id of the newly inserted livewebteaching record
 */
function livewebteaching_add_instance($livewebteaching) {

    $livewebteaching->timecreated = time();

	if (record_exists( 'livewebteaching', 'meetingID', $livewebteaching->name)) {
		error("A meeting with that name already exists.");
		return false;
	}

	$livewebteaching->moderatorpass = livewebteaching_rand_string( 16 );
	$livewebteaching->viewerpass = livewebteaching_rand_string( 16 );
	$livewebteaching->meetingid = livewebteaching_rand_string( 16 );

	return insert_record('livewebteaching', $livewebteaching);
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $livewebteaching An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function livewebteaching_update_instance($livewebteaching) {

    $livewebteaching->timemodified = time();
    $livewebteaching->id = $livewebteaching->instance;

	if (! isset($livewebteaching->wait)) {
		$livewebteaching->wait = 1;
	}


    # You may have to add extra stuff in here #

    return update_record('livewebteaching', $livewebteaching);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function livewebteaching_delete_instance($id) {
    global $CFG;

    if (! $livewebteaching = get_record('livewebteaching', 'id', $id)) {
        return false;
    }

    $result = true;

    //
	// End the session associated with this instance (if it's running)
	//
	$meetingID = $livewebteaching->meetingid;
	$modPW = $livewebteaching->moderatorpass;
	$url = trim(trim($CFG->lwt_server),'/').'/';
	$salt = trim($CFG->lwt_apikey);

	$getArray = BigBlueButton::endMeeting( $meetingID, $modPW, $url, $salt, $CFG->lwt_username );
	
    if (! delete_records('livewebteaching', 'id', $livewebteaching->id)) {
    	//echo $endURL = '<a href='.BBBMeeting::endMeeting( $mToken, "mp", getBBBServerIP(), $salt ).'>'."End Meeting".'</a>';
#switch to remove the meetingname
#    	  BBBMeeting::endMeeting( $livewebteaching->, "mp", getBBBServerIP(), $livewebteaching->salt );
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
function livewebteaching_user_outline($course, $user, $mod, $livewebteaching) {
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function livewebteaching_user_complete($course, $user, $mod, $livewebteaching) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in livewebteaching activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function livewebteaching_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function livewebteaching_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of livewebteaching. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $livewebteachingid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function livewebteaching_get_participants($livewebteachingid) {
    global $CFG;
    return false;
}


/**
 * This function returns if a scale is being used by one livewebteaching
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $livewebteachingid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function livewebteaching_scale_used($livewebteachingid, $scaleid) {
    $return = false;

    //$rec = get_record("livewebteaching","id","$livewebteachingid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of livewebteaching.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any livewebteaching
 */
function livewebteaching_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('livewebteaching', 'grade', -$scaleid)) {
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
function livewebteaching_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function livewebteaching_uninstall() {
    return true;
}


//////////////////////////////////////////////////////////////////////////////////////
/// Any other livewebteaching functions go here.  Each of them must have a name that
/// starts with livewebteaching_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all funcions below to a new "localib.php" file.

# function taken from http://www.php.net/manual/en/function.mt-rand.php
# modified by Sebastian Schneider
# credits go to www.mrnaz.com
function livewebteaching_rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
{
    $string = '';
    for ($i = 0; $i < $len; $i++)
    {
        $pos = rand(0, strlen($chars)-1);
        $string .= $chars{$pos};
    }
    return (sha1($string));
}

?>
