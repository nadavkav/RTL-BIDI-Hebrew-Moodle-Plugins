<?php

/**
 * View all Live Sessions Scheduled in this course.
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
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


require_once('../../config.php');
require_once('lib.php');


$id = required_param('id', PARAM_INT);    // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // livewebteaching instance ID


if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);


$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
$moderator = has_capability('mod/livewebteaching:moderate', $coursecontext);

add_to_log($course->id, 'livewebteaching', 'view all', "index.php?id=$course->id", '');


/// Get all required stringslivewebteaching

$strlivewebteachings = get_string('modulenameplural', 'livewebteaching');
$strlivewebteaching  = get_string('modulename', 'livewebteaching');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strlivewebteachings, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strlivewebteachings, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $livewebteachings = get_all_instances_in_course('livewebteaching', $course)) {
    notice('There are no instances of livewebteaching', "../../course/view.php?id=$course->id");
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strweek  = get_string('week');
$strtopic = get_string('topic');
$heading_name  			= get_string('index_header_name', 'livewebteaching' );
$heading_users			= get_string('index_heading_users', 'livewebteaching');
$heading_viewer  		= get_string('index_heading_viewer', 'livewebteaching');
$heading_moderator 		= get_string('index_heading_moderator', 'livewebteaching' );
$heading_actions 		= get_string('index_heading_actions', 'livewebteaching' );


if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $heading_name, $heading_users, $heading_viewer, $heading_moderator, $heading_actions);
    $table->align = array ('center', 'center', 'center', 'center', 'center',  'center' );
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}


$salt = trim($CFG->lwt_apikey);
$url = trim(trim($CFG->lwt_server),'/').'/';
$logoutURL = $CFG->wwwroot;

if( isset($_POST['submit']) && $_POST['submit'] == 'end' ) { 
	//
	// A request to end the live session
	//
	if (! $livewebteaching = get_record('livewebteaching', 'id', $a)) {
        	error("BigBlueButton ID $a is incorrect");
	}
	print get_string('index_ending', 'livewebteaching');

	$meetingID = $livewebteaching->meetingid;
	$modPW = $livewebteaching->moderatorpass;

	$getArray = BigBlueButton::endMeeting( $meetingID, $modPW, $url, $salt );
	// print_object( $getArray );
	$livewebteaching->meetingid = livewebteaching_rand_string( 16 );
	if (! update_record('livewebteaching', $livewebteaching) ) {
		notice( "Unable to assign a new meetingid" );
	} else {
		redirect('index.php?id='.$id);
	}
}

// print_object( $livewebteachings );

foreach ($livewebteachings as $livewebteaching) {
	$info = null;
	$joinURL = null;
	$user = null;
	$result = null;
	$users = "-";
	$running = "-";
	$actions = "-";
	$viewerList = "-";
	$moderatorList = "-";
		
	// print_object( $livewebteaching );

    if (!$livewebteaching->visible) {
    	// Nothing to do
    } else {
		$modPW = get_field( 'livewebteaching', 'moderatorpass', 'name', $livewebteaching->name );
		$attPW = get_field( 'livewebteaching', 'viewerpass',  'name', $livewebteaching->name );

		// print "## $modPW ##";

		$joinURL = '<a href="view.php?id='.$livewebteaching->coursemodule.'">'.format_string($livewebteaching->name).'</a>';
		// $status = $livewebteaching->meetingid;

		//echo "XX";

		//
		// Output Users in the live session
		//
		$getArray = BigBlueButton::getMeetingInfoArray( $livewebteaching->meetingid, $modPW, $url, $salt );

		// print_object( $getArray );

		if (!$getArray) {
			//
			// The server was unreachable
			//
			error( get_string( 'index_unable_display', 'livewebteaching' ));
			return;
		}

		if (isset($getArray['messageKey'])) {
			//
			// There was an error returned
			//
			if ($info['messageKey'] == "checksumError") {
				error( get_string( 'index_checksum_error', 'livewebteaching' ));
				return;
			}

			if ($getArray['messageKey'] == "notFound" ) {
				//
				// The live session does not exist yet on the BigBlueButton server.  This is OK.
				//
			} else {
				//
				// There was an error
				//
				$users = $getArray['messageKey'].": ".$info['message'];
			}
		} else {

			//
			// The live session info was returned
			//
			if ($getArray['running'] == 'true') {
				//$status =  get_string('index_running', 'livewebteaching' );
				
				if ( $moderator ) {
					$actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="'.$id.'"><INPUT type="hidden" name="a" value="'.$livewebteaching->id.'"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\''. get_string('index_confirm_end', 'livewebteaching' ).'\')"></form>';
				}

				$xml = $getArray['attendees'];
				if (count( $xml ) && count( $xml->attendee ) ) {
					$users = count( $xml->attendee );
					$viewer_count = 0;
					$moderator_count = 0;
					foreach ( $xml->attendee as $attendee ) {
						if ($attendee->role == "MODERATOR" ) {
							if ( $viewer_count++ > 0 ) {
								$moderatorList .= ", ";
							} else {
								$moderatorList = "";
							}
							$moderatorList .= $attendee->fullName;
						} else {
							if ( $moderator_count++ > 0 ) {
								$viewerList .= ", ";
							} else {
								$viewerList = "";
							}
							$viewerList .= $attendee->fullName;
						}
					}
				}
			}
		}
	}

	if ($course->format == 'weeks' or $course->format == 'topics' ) {
		$table->data[] = array ($livewebteaching->section, $joinURL, $users, $viewerList, $moderatorList, $actions );
	} else {
		$table->data[] = array ($livewebteaching->section, $joinURL, $users, $viewerList, $moderatorList, $actions );
	}
}

print_heading($strlivewebteachings);
print_table($table);

print_footer($course);

?>
