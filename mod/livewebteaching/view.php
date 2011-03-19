<?php

/**
 * Join a Live Session room
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
 * *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @copyright 2010 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

error_reporting(0);

require_once('../../config.php');
require_once('lib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // livewebteaching instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('livewebteaching', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $livewebteaching = get_record('livewebteaching', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $livewebteaching = get_record('livewebteaching', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $livewebteaching->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('livewebteaching', $livewebteaching->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$moderator = has_capability('mod/livewebteaching:moderate', $context);


add_to_log($course->id, "livewebteaching", "join", "view.php?id=$cm->id", "$livewebteaching->id");

/// Print the page header
$strlivewebteachings = get_string('modulenameplural', 'livewebteaching');
$strlivewebteaching  = get_string('modulename', 'livewebteaching');

$navlinks = array();
$navlinks[] = array('name' => $strlivewebteachings, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($livewebteaching->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

//
// BigBlueButton Setup
//

$salt = trim($CFG->lwt_apikey);
$url = trim(trim($CFG->lwt_server),'/').'/';
$logoutURL = $CFG->wwwroot."/course/view.php?id=".$cm->course;
$logoutURL = $CFG->wwwroot."/mod/livewebteaching/logout.php?id=".$cm->course;

$username = $USER->firstname.' '.$USER->lastname;
$userID = $USER->id;

$modPW = $livewebteaching->moderatorpass;
$viewerPW = $livewebteaching->viewerpass;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="height: 90%;">
<head>
    <title>Moodle Live Web Teaching</title>
</head>

<body style="background: #333; color: #fff; margin: 0px; padding: 0px; font-size: 12px; font-family: Arial; height: 100%;">

    <div id="header_wcr_v3" style="background: #000; width: 100%; height: 24px;">
        <span style="float: left; margin-top: 5px; margin-left: 10px; font-weight: bold; font-family: 'Trebuchet MS';"><?php echo $livewebteaching->name; ?></span>
        <span style="float: right; margin-top: -6px;"></span>
    </div>
    <div style="height: 100%;">

<?php

$welcome_text  = get_string('welcome_l1', 'livewebteaching' );
$welcome_text .= get_string('welcome_l2', 'livewebteaching' );
$welcome_text .= get_string('welcome_l3', 'livewebteaching' );
$welcome_text .= get_string('welcome_l4', 'livewebteaching' );
$welcome_text .= get_string('welcome_l5', 'livewebteaching' );
if( $moderator ) {
	//$logoutURL .= "&m=1&p0=".urlencode($modPW)."&p1=".urlencode($livewebteaching->meetingid);
	$response = BigBlueButton::createMeetingArray( "" , $livewebteaching->meetingid, $welcome_text, $modPW, $viewerPW, $salt, $url, $logoutURL, trim($CFG->lwt_username) );

	if (!$response) {
		// If the server is unreachable, then prompts the user of the necessary action
		error( 'Unable to join the live session. Please contact your Moodle administrator to check the plugin installation.' );
	}

	if( $response['returncode'] == "FAILED" ) {
		// The meeting was not created
		if ($response['messageKey'] == "checksumError"){
			 error( get_string( 'index_checksum_error', 'livewebteaching' ));
		}
		else {
			error( $response['message'] );
		}
	}

	$joinURL = BigBlueButton::joinURL($livewebteaching->meetingid, $username, $modPW, $salt, $url, $userID);
	?>
	<iframe src ="<?php echo $joinURL;?>" width="100%" height="100%" style="position: absolute: bottom: 0px; border: 0px;overflow:hidden;"></iframe>
	<?php
} else {
	//
	// Login as a viewer, check if we need to wait
	//

	// "Viewer";
	if( $livewebteaching->wait ) {
		// check if the session is running; if not, user is not allowed to join
		// print "MeeingID: #".$livewebteaching->meetingid."#<br>";
		$arr = BigBlueButton::getMeetingInfoArray( $livewebteaching->meetingid, $modPW, $url, $salt );
		$joinURL = BigBlueButton::joinURL( $livewebteaching->meetingid, $username, $viewerPW, $salt, $url, $userID);

		// print_object( $arr );
		// print "Is Meeting runnign: #".BigBlueButton::isMeetingRunning( $livewebteaching->meetingid,  $url, $salt )."#<br>";
		// print "BBB";
		
		if( BigBlueButton::isMeetingRunning( $livewebteaching->meetingid, $url, $salt ) == "true" ) {
			//
			// since the meeting is already running, we just join the session
			//
			//print "<br />".get_string('view_login_viewer', 'livewebteaching' )."<br /><br />";
			//print "<center><img src='loading.gif' /></center>";
			?>
        <iframe src ="<?php echo $joinURL;?>" width="100%" height="100%" style="position: absolute: bottom: 0px; border: 0px;overflow:hidden;"></iframe>
        		<?php



		} else {
			print '<div style="position: absolute; left: 20px; top: 100px; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius:10px; border: solid 1px #ccc; background: #fff; color: #000; padding: 20px; text-align: center;">';
			print '<br />'.get_string('view_wait', 'livewebteaching' ).'<br /><br />';
			print '<img src="polling.gif">';
			print '</div>';

?>
<p></p>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="heartbeat.js"></script>
<script type="text/javascript" >
                        $(document).ready(function(){
                        $.jheartbeat.set({
                        url: "<?php echo $CFG->wwwroot ?>/mod/livewebteaching/check.php?name=<?echo $livewebteaching->meetingid; ?>",
                        delay: 5000
                        }, function() {
                                mycallback();
                        });
                        });
                function mycallback() {
                        // Not elegant, but works around a bug in IE8
                        var isMeeting = ($("#HeartBeatDIV").text().search("true")  > 0 );
                        if ( isMeeting ) {
                                //window.location = "<?php echo $joinURL ?>";
								window.location.reload(true);
                        }
                }
</script>
<?php
		}
	} else {
	
	//
	// Join as Viewer, no wait check
	//

	//print "<br />".get_string('view_login_viewer', 'livewebteaching' )."<br /><br />";
	//print "<center><img src='loading.gif' /></center>";
	
	$response = BigBlueButton::createMeetingArray( "" , $livewebteaching->meetingid, $welcome_text, $modPW, $viewerPW, $salt, $url, $logoutURL );

	if (!$response) {
		// If the server is unreachable, then prompts the user of the necessary action
		error( 'Unable to join the live session. Please contact your administrator.' );
	}

	if( $response['returncode'] == "FAILED" ) {
		// The meeting was not created
		if ($response['messageKey'] == "checksumError"){
			error( get_string( 'index_checksum_error', 'livewebteaching' ));
		}
		else {
			error( $response['message'] );
		}
	}

	$joinURL = BigBlueButton::joinURL($livewebteaching->meetingid, $username, $viewerPW, $salt, $url, $userID);
	?>
        <iframe src ="<?php echo $joinURL;?>" width="100%" height="100%" style="position: absolute: bottom: 0px; border: 0px;overflow:hidden;"></iframe>
        <?php
	}
}

?>
    </div>
    <div id="footer_wcr_v3">
        <span style="float: right; margin-top: 15px; margin-right: 10px; font-family: 'Trebuchet MS';">Powered by <a style="color: #fff;" href="http://www.livewebteaching.com" target="_blank">LiveWebTeaching.com</a></span>
    </div>

</body>
</html>
