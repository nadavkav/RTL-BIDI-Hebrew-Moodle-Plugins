<?php // $Id: snapshot.php,v 1.0 2010/01/25 matbury Exp $
/**
 * This page receives a bitmap image sent by the Snapshot plugin
 * in the Media Player Activity Module and writes a copy of it in
 * a moodledata/&course->id/snaphots/
 * Please note, if /moodledata/&course->id/snapshots/ directory is
 * not present this script will fail
 *
 * @author Matt Bury - matbury@gmail.com
 * @version $Id: view.php,v 1.1 2010/01/25 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package mplayer
 **/
 
/**    Copyright (C) 2009  Matt Bury
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

require_once('../../../config.php');
global $CFG;
global $USER;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID

// Get the course and module
if ($id)
{
	if (! $cm = get_record('course_modules', 'id', $id))
	{
		echo 'Course Module ID was incorrect';
		exit();
	}
	
	if (! $course = get_record('course', 'id', $cm->course))
	{
		echo 'Course is misconfigured';
		exit();
	}
}

// Make sure user is logged in
require_login($course->id);

if(isset ($GLOBALS['HTTP_RAW_POST_DATA']))
{
	// Get the image from POST data
	$mplayer_image =  $GLOBALS['HTTP_RAW_POST_DATA'];
	
	// Create an easy to find unique file name, i.e. "firstname_year_month_date_hours-mins-secs.jpg"
	$mplayer_filename = $USER->firstname.'_'.date('Y\_M\_dS\_h\-i\-s').'.jpg';
	
	// Set file path to moodledata snapshots directory for current user
	$mplayer_snapshots_path = '/'.$course->id.'/snapshots/'.$USER->id.'/';
	$mplayer_moodledata = $CFG->wwwroot.'/file.php/'.$mplayer_snapshots_path;
	
	// Try to write the image as a JPG in moodledata/snapshots/[user ID]/
	try {
		$mplayer_filepath = fopen($CFG->dataroot.$mplayer_snapshots_path.$mplayer_filename, 'wb');
		fwrite($mplayer_filepath, $mplayer_image);
		fclose($mplayer_filepath);
	} catch(Exception $e) {
		exit();
	}
	
	// Send the image filename and path back to the Media Player Snapshot plugin
	if (exif_imagetype($CFG->dataroot.$mplayer_snapshots_path.$mplayer_filename) == IMAGETYPE_JPEG)
	{
		echo $mplayer_moodledata.$mplayer_filename;
		exit();
	}
} else {
	echo 'error: '.$mplayer_moodledata.$mplayer_filename.' was not saved';
}

?>
