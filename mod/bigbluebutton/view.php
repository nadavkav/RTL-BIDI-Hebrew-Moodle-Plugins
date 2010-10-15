<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                                                                       //
// Copyright (C) 2010 Dual Code Inc. (www.dualcode.com)                  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/blocklib.php');
require_once('pagelib.php');

$id		= optional_param('id', 0, PARAM_INT);
$c      = optional_param('c', 0, PARAM_INT);
$edit   = optional_param('edit', '');

if ($id) {
  if (! $cm = get_record('course_modules', 'id', $id)) {error('The course module ID is incorrect.');}
  if (! $course = get_record('course', 'id', $cm->course)) {error('The course is misconfigured.');}

// bigbluebutton_update_bigbluebutton_times($cm->instance);
  if (! $bigbluebutton = get_record('bigbluebutton', 'id', $cm->instance)) {error('The module ID is incorrect.');}
} 

else {
// bigbluebutton_update_bigbluebutton_times($c);
  if (! $bigbluebutton = get_record('bigbluebutton', 'id', $c)) {error('The course module ID is incorrect.');}
  if (! $course = get_record('course', 'id', $bigbluebutton->course)) {error('The course is misconfigured.');}
  if (! $cm = get_coursemodule_from_instance('bigbluebutton', $bigbluebutton->id, $course->id)) {
    error('The module ID is incorrect.');
  }
}

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
add_to_log($course->id, 'bigbluebutton', 'view', "view.php?id=$cm->id", $bigbluebutton->id, $cm->id);


/// Initialize $PAGE
$PAGE       			= page_create_instance($bigbluebutton->id);
$pageblocks 			= blocks_setup($PAGE);
$blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);


/// Print the page header
$strnextsession  = get_string('nextsession', 'bigbluebutton');

if (!empty($edit) && $PAGE->user_allowed_editing()) {
  if ($edit == 'on') {
    $USER->editing = true;
  } 
  else if ($edit == 'off') {
    $USER->editing = false;
  }
}

$PAGE->print_header($course->shortname.': %fullname%');

echo '<table id="layout-table"><tr>';

if(!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing())) {
  echo '<td style="width: '.$blocks_preferred_width.'px;" id="left-column">';
  blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
  echo '</td>';
}

echo '<td id="middle-column">';

print_heading(format_string($bigbluebutton->meetingname));


/// Get all of the variables required by BBB
$ip				= $CFG->bbb_serverhost;
$securitySalt	= $CFG->bbb_securitysalt;
$fullname 		= urlencode($USER->firstname." ".$USER->lastname);
$meetingname	= urlencode(get_field('bigbluebutton','meetingname','id',$bigbluebutton->id));
$meetingid 		= urlencode(get_field('bigbluebutton','meetingid','id',$bigbluebutton->id));
$attendeePW 	= urlencode(get_field('bigbluebutton','attendeepw','id',$bigbluebutton->id));
$moderatorPW 	= urlencode(get_field('bigbluebutton','moderatorpw','id',$bigbluebutton->id));
$loginRule 		= urlencode(get_field('bigbluebutton','autologin','id',$bigbluebutton->id));
$newwindow 		= urlencode(get_field('bigbluebutton','newwindow','id',$bigbluebutton->id));
$welcomeMsg 	= urlencode(get_field('bigbluebutton','welcomemsg','id',$bigbluebutton->id));
$logoutURL 		= urlencode(bbb_currentPageURL());  // Parameter does not work in BBB v0.63 or earlier


/// Create the meeting
$createResponse = bbb_createMeeting($ip,$securitySalt,$meetingname,$meetingid,$attendeePW,$moderatorPW,$welcomeMsg,$logoutURL);
if ($createResponse=="SUCCESS") {


/// Determine whether to launch the session in the same window or a new window	
  $newWindowStr = "";
  if ($newwindow=='1') {$newWindowStr = "target=\"_blank\"";}
	
	
/// Create the links to join the meeting
  $joinURL 		= bbb_joinMeetingURL($ip,$securitySalt,$fullname,$meetingid,$attendeePW);  // as attendee
  $joinURLasMod = bbb_joinMeetingURL($ip,$securitySalt,$fullname,$meetingid,$moderatorPW); // as moderator


/// Build HTML page
  $formload = 'true';
  if (!isguestuser() and isloggedin()) {
    $context = get_context_instance(CONTEXT_MODULE,$cm->id);
    if (has_capability('mod/bigbluebutton:ismoderator', $context)) {
	  print_simple_box_start('center');
	  echo get_string('joinmeeting_instructions', 'bigbluebutton').'<br /><br />';
	  echo "<a ".$newWindowStr." href='".$joinURL."'>".get_string('joinmeeting_asguest', 'bigbluebutton')."</a><br /><br />";
		  
/// Let the user log in as a moderator if he has the right privileges
      echo "<a ".$newWindowStr." href='".$joinURLasMod."'>".get_string('joinmeeting_asmoderator', 'bigbluebutton')."</a><br /><br />";
      print_simple_box_end();
    }  
    else {
	  print_simple_box_start('center');
	  echo get_string('joinmeeting_instructions', 'bigbluebutton').'<br /><br />';
	  echo "<a ".$newWindowStr." href='".$joinURL."'>".get_string('joinmeeting', 'bigbluebutton')."</a>";
	  print_simple_box_end();
    }
  }
  else {
    $wwwroot = $CFG->wwwroot.'/login/index.php';
    if (!empty($CFG->loginhttps)) {
      $wwwroot = str_replace('http','https', $wwwroot);
    }
    notice_yesno(get_string('noguests', 'bigbluebutton').'<br /><br />'.get_string('liketologin'),
    $wwwroot, $_SERVER['HTTP_REFERER']);
    print_footer($course);
    exit;
  }
}
else {  // If not meeting has not been created successfully
  print_simple_box_start('center');
  echo get_string($createResponse, 'bigbluebutton').'<br />';
  print_simple_box_end();
}

/// Finish the page
echo '</td></tr></table>';
print_footer($course);
?>
