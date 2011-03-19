<?php

/**
 *  Logout
 *
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
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
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

error_reporting(0);

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID
//$moderator = optional_param('m', 0, PARAM_INT); 
//$meetingID = optional_param('p1', '', PARAM_TEXT);
//$modPW = optional_param('p0', '', PARAM_TEXT);

$logoutURL = $CFG->wwwroot."/course/view.php?id=".$id;

$salt = trim($CFG->lwt_apikey);
$url = trim(trim($CFG->lwt_server),'/').'/';
/*
if( $moderator == 1 ) {
	add_to_log($id, "livewebteaching", "logout", "view.php?id=$id", "$meetingID");
    BigBlueButton::endMeeting( $meetingID, $modPW, $url, $salt );
}
*/
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Moodle Live Web Teaching</title>
</head>

<body>
    <script>
    window.parent.location.href = '<?php echo $logoutURL;?>';
    </script>
</body>
</html>
