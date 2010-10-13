<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once dirname(__FILE__).'/inc.php';

$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_BOOL);
$filename = optional_param('filename', '', PARAM_FILE);
$assignmentid = optional_param('assignmentid', 0, PARAM_INT);
$post = new stdClass();
$checked_file = new stdClass();
$action = 'add';

if(!confirm_sesskey()) {
	print_error("badsessionkey","block_exabis_eportfolio");
}

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);

if(! $course = get_record("course", "id", $courseid) ) {
	print_error("invalidcourseid","block_exabis_eportfolio");
}
		
if(!block_exabis_eportfolio_has_categories($USER->id)) {
	print_error("nocategories", "block_exabis_eportfolio", "view.php?courseid=" . $courseid);
}

if($assignmentid == 0) {
	error("No assignment given!");
}

if(!($checked_file = check_assignment_file($assignmentid, $filename))) {
   print_error("invalidfileatthisassignment","block_exabis_eportfolio");
}


if ($id) {
	if (!$existing = get_record('block_exabeporitem', 'id', $id, 'userid', $USER->id)) {
		print_error("wrongfileid","block_exabis_eportfolio");		
	}

	$returnurl = $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$courseid."&type=file"; //?userid='.$existing->userid;
} else {
	$existing  = false;
	$returnurl = $CFG->wwwroot.'/blocks/exabis_eportfolio/view_items.php?courseid='.$courseid."&type=file"; //'index.php?userid='.$USER->id;
}

if ($action=='delete'){
	if (!$existing) {
		print_error("wrongfilepostid","block_exabis_eportfolio");			
	}
	if (data_submitted() and $confirm and confirm_sesskey()) {
		do_delete($existing,$returnurl, $courseid);
		redirect($returnurl);
	} else {
		$optionsyes = array('id'=>$id, 'action'=>'delete', 'confirm'=>1, 'sesskey'=>sesskey(), 'courseid'=>$courseid);
		$optionsno = array('userid'=>$existing->userid, 'courseid'=>$courseid);
		print_header("$SITE->shortname", $SITE->fullname);
		// ev. noch eintrag anzeigen!!!
		//blog_print_entry($existing);
		echo '<br />';
		notice_yesno(get_string("deletefileconfirm", "block_exabis_eportfolio"), 'add_file.php', 'view_items.php', $optionsyes, $optionsno, 'post', 'get');
		print_footer();
		die;
	}
}

require_once("{$CFG->dirroot}/blocks/exabis_eportfolio/lib/item_edit_form.php");

if(($checked_file  != '')&&($action == 'add')) {
	$existing->action       = $action;
	$existing->courseid     = $courseid;
	$existing->type			= 'file';
	$existing->dir          = "";
	$existing->name         = "";
	$existing->categoryid   = "";
	$existing->intro        = "";
	//$existing->fullpath     = $checked_file->fullpath;
	$existing->filename     = $checked_file->filename;
	$existing->assignmentid = $assignmentid;
}

$exteditform = new block_exabis_eportfolio_item_edit_form(null, Array('existing' => $existing, 'type' => 'file', 'action' => 'edit'));


if ($exteditform->is_cancelled()){
	redirect($returnurl);
} else if ($exteditform->no_submit_button_pressed()) {
	die("nosubmitbutton");
	//no_submit_button_actions($exteditform, $sitecontext);
} else if ($fromform = $exteditform->get_data()){
	switch ($action) {
		case 'add':
			do_add($fromform, $exteditform, $returnurl, $courseid, $checked_file);
		break;

		case 'edit':
			if (!$existing) {
				print_error("wrongfileid","block_exabis_eportfolio");					
			}
			do_edit($fromform, $exteditform,$returnurl, $courseid);
		break;
		default :
				print_error("unknownaction","block_exabis_eportfolio");					
	}
	redirect($returnurl);
}

$strAction = "";
// gui setup
switch ($action) {
	case 'add':
		$post->action       = $action;
		$post->courseid     = $courseid;
		$post->assignmentid = $assignmentid;
		$post->filename     = $checked_file->filename;
		$strAction = get_string('new');
		break;
	default :
			print_error("unknownaction","block_exabis_eportfolio");			
}

block_exabis_eportfolio_print_header("bookmarksfiles");


echo "<div class='block_eportfolio_center'>\n";
print_box(block_exabis_eportfolio_print_file($CFG->wwwroot . '/' . 'file.php?file=/' . $checked_file->fullpath, $checked_file->filename, $checked_file->filename));
echo "</div>";

$exteditform->set_data($post);
$exteditform->display();

print_footer($course);
die;



/**
 * Update a file in the database
 */
function do_edit($post, $blogeditform,$returnurl, $courseid) {
	global $CFG, $USER;

	$post->timemodified = time();

	if (update_record('block_exabeporitem', $post)) {
		add_to_log(SITEID, 'bookmark', 'update', 'add_file.php?courseid='.$courseid.'&id='.$post->id.'&action=edit', $post->name);
	} else {
		print_error('updateposterror', 'block_exabis_eportfolio', $returnurl);
	}
}

/**
 * Write a new blog entry into database
 */
function do_add($post, $blogeditform, $returnurl,$courseid, $checked_file) {
	global $CFG, $USER;
	
	$post->userid       = $USER->id;
	$post->timemodified = time();
	$post->course = $courseid;
	$post->type = 'file';

	// Insert the new blog entry.
	if ($post->id = insert_record('block_exabeporitem', $post)) {
		add_to_log(SITEID, 'bookmark', 'add', 'add_file.php?courseid='.$courseid.'&id='.$post->id.'&action=add', $post->name);
		$dir = block_exabis_eportfolio_file_area_name($post);
		if(is_file($CFG->dataroot . '/' . $checked_file->fullpath)) {
			if(make_upload_directory($dir) != false) {
				if(!copy($CFG->dataroot . '/' . $checked_file->fullpath, $CFG->dataroot . '/' . $dir . '/' . $checked_file->filename)) {
					error("Copy failed!");
				}
		        set_field("block_exabeporitem", "attachment", $checked_file->filename, "id", $post->id);
		    }
			else {
 			        print_error("couldntcreatedirectory","block_exabis_eportfolio");
			}
		}
		else {
			print_error("filenotfound","block_exabis_eportfolio");
		}				
	} else {
		print_error('addposterror', 'block_exabis_eportfolio', $returnurl);
	}
}

/**
 * Delete blog post from database
 */
function do_delete($post,$returnurl, $courseid) {


	$status = delete_records('block_exabeporitem', 'id', $post->id);

	add_to_log(SITEID, 'blog', 'delete', 'add_file.php?courseid='.$courseid.'&id='.$post->id.'&action=delete&confirm=1', $post->name);

	if (!$status) {
		print_error('deleteposterror', 'block_exabis_eportfolio', $returnurl);
	}
}

function check_assignment_file($assignmentid, $file) {
	global $CFG, $USER;
	$fileinfo = new stdClass();
	
	if(! $assignment = get_record_sql('SELECT s.id, s.assignment, a.course AS courseid
                                    FROM ' . $CFG->prefix . 'assignment_submissions s
                                    JOIN ' . $CFG->prefix . 'assignment a ON s.assignment=a.id
                                    WHERE s.userid=\'' . $USER->id . '\' AND s.id=\'' . $assignmentid . '\'')) {

       print_error("invalidassignmentid","block_exabis_eportfolio");
    }
    
    $basedir = block_exabis_eportfolio_moodleimport_file_area_name($USER->id, $assignment->assignment, $assignment->courseid);
    
    if ($files = get_directory_list($CFG->dataroot . '/' . $basedir)) {
        foreach ($files as $key => $actFile) {
        	if($actFile == $file) {
				$fileinfo->filename = $file;
				$fileinfo->basedir = $basedir;
				$fileinfo->fullpath = $basedir . '/' . $actFile;
        		return $fileinfo;
        	}
        }
	}
	return false;
}
