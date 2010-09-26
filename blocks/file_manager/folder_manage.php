<?php

/**
* folder_manage.php
* 
* This file provides the user the ability to create/modify
* a new or existing category.
*
* @package block-file_manager
* @category blocks
*/
    // global $USER, $CFG;

    require_once("../../config.php");
    require_once($CFG->dirroot.'/blocks/file_manager/lib.php');
	require_once($CFG->dirroot.'/blocks/file_manager/print_lib.php');
	
	$id         = required_param('id', PARAM_INT);
	$groupid    = optional_param('groupid', 0, PARAM_INT);
	$foldername = optional_param('foldername', NULL, PARAM_CLEAN);	//
	$rootdir    = optional_param('rootdir', 0, PARAM_INT);		//
	//$from     = required_param('from', NULL, PARAM_ALPHAEXT);
	$foldid     = optional_param('foldid', NULL, PARAM_INT);		//
	$cat        = optional_param('foldercat', 0, PARAM_INT);		//

	$dupname = false;
	
    if (! $course = get_record('course', 'id', $id) ) {
        error("Invalid Course Id", "view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
    }

	require_login($course->id);
	
    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);
	
	// Ensures the user is able to view the fmanager
	fm_check_access_rights($course->id);	

	if ($groupid == 0){
		// Ensures user owns the folder
		fm_user_owns_folder($rootdir);
	} else {
		// Ensures group owns the folder
		fm_group_owns_folder($rootdir, $groupid);

		// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
		// 1.8 backcompatibility hack
		if (function_exists('build_navigation')){
		    $groupmode = groups_get_course_groupmode($course);
		} else {
		    // TODO : spit out this code in further versions
		    $groupmode = groupmode($course);
		}
		
		switch ($groupmode){
			case NOGROUPS : 
				// Should no to be there ...
				error(get_string('errnogroups', 'block_file_manager'), "{$CFG->wwwroot}/course/view.php?id={$course->id}");
				break;
			case VISIBLEGROUPS :
			case SEPARATEGROUPS : 
				if (!$canmanagegroups && !groups_is_member($groupid)){ // Must check if the user is member of that group
					error(get_string('errnotmemberreadonly', 'block_file_manager'), "view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
				}
				break;
		}
	}

    $strtitle = get_string('folders','block_file_manager');
    $nav[] = array('name'=>get_string('filemanager','block_file_manager'), 'link'=>"view.php?id=$id&groupid={$groupid}", 'type'=>'misc');
    $nav[] = array('name'=>$strtitle, 'link'=>null,'type'=>'misc');
    $navigation = build_navigation($nav);
    print_header($strtitle, format_string($course->fullname), $navigation, '', '', false, "&nbsp;", "&nbsp;");

    print_heading(get_string('folders', 'block_file_manager'));

    echo "<br/>";

	if (isset($_POST['submit'])) {
		// For existing folder...
		if (isset($foldid)) {
			if ($groupid == 0){
				// Ensures user owns the folder
				fm_user_owns_folder($foldid);
			} else {
				// Ensures group owns the folder
				fm_group_owns_folder($foldid, $groupid);
			}
			if ($foldername) {
				if (count_records('fmanager_folders', 'name', $foldername, 'pathid', $rootdir) != count_records('fmanager_folders', "name", $foldername, "id", $foldid)) {
					print_simple_box(get_string('msgduplicate','block_file_manager'), 'center', "", "#FFFFFF");
					$dupname = true;
				} else {
					$oldentry = get_record('fmanager_folders', 'id', $foldid);
					$entry = NULL;
					$entry->id = $foldid;
					if ($groupid == 0){
						$entry->owner = $USER->id;
						$entry->ownertype = OWNERISUSER;
					} else {
						$entry->owner = $groupid;
						$entry->ownertype = OWNERISGROUP;
					}
					$entry->name = $foldername;
					$entry->category = $cat;
					$entry->pathid = $oldentry->pathid;
					$entry->path = $oldentry->path;
					$entry->timemodified = time();
					
					if ($groupid == 0){
						$oldpath = "$CFG->dataroot/".fm_get_user_dir_space()."$oldentry->path$oldentry->name";
						$newpath = "$CFG->dataroot/".fm_get_user_dir_space()."$oldentry->path$foldername";
					} else {
						$oldpath = "$CFG->dataroot/".fm_get_group_dir_space($groupid)."$oldentry->path$oldentry->name";
						$newpath = "$CFG->dataroot/".fm_get_group_dir_space($groupid)."$oldentry->path$foldername";
					}
					if (file_exists($newpath)) {
						// If its an existing folder and just a category is being changed
						$t = get_record('fmanager_folders', 'id', $foldid);
						if ($t->name == $foldername) {
							update_record('fmanager_folders', $entry);
							print_simple_box(get_string('msgmodificationok','block_file_manager'), "center", "", "#FFFFFF");
							redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
						} else {
							print_simple_box(get_string('msgduplicate','block_file_manager'), "center", "", "#FFFFFF");
							$dupname = true;
						}
					}
					if (!$dupname && !@rename($oldpath, $newpath)) {
						error(get_string('errnocreatefold','block_file_manager'));
					}										
					if (!$dupname && !update_record('fmanager_folders', $entry)) {
						error(get_string('errnoinsert', 'block_file_manager'));
					}
					if ($dupname != true) {
						print_simple_box(get_string('msgmodificationok','block_file_manager'), "center", "", "#FFFFFF");
						redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
					}
				}
			}
		} else {
			if ($foldername) {
				if (count_records('fmanager_folders','name', $foldername, 'path', $rootdir) != count_records('fmanager_folders', 'name', $foldername, 'id', $foldid)) {
					print_simple_box(get_string('msgduplicate','block_file_manager'), 'center', '', '#FFFFFF');
					$dupname = true;
				} else {
					$oldpath = get_record('fmanager_folders', 'id', $rootdir);
                    if (empty($oldpath)) {
                        $oldpath->name = '';
                        $oldpath->path= '';
                    }
                    $entry = NULL;
					if ($groupid == 0){
						$entry->owner = $USER->id;
						$entry->ownertype = OWNERISUSER;
					} else {
						$entry->owner = $groupid;
						$entry->ownertype = OWNERISGROUP;
					}
					$entry->name = $foldername;
					if ($rootdir == 0) {
						$entry->path = "/";
					} else {
						$entry->path = $oldpath->path.$oldpath->name."/";
					}
					$entry->category = $cat;
					$entry->pathid = $rootdir;
					$entry->timemodified = time();
					
					$newpath = $CFG->dataroot;
					//var_dump($oldpath);

					if ($groupid == 0){
						$newpath .= "/".fm_get_user_dir_space()."$oldpath->path$oldpath->name/$foldername";
					} else {
						$newpath .= "/".fm_get_group_dir_space($groupid)."$oldpath->path$oldpath->name/$foldername";
					}
					if (file_exists($newpath)) {
						print_simple_box(get_string('msgduplicate', 'block_file_manager'), 'center', '', '#FFFFFF');
						$dupname = true;				
					}					
					if ($groupid == 0){
						$newpath = fm_get_user_dir_space();
					} else {
						$newpath = fm_get_group_dir_space($groupid);
					}
					$newpath = make_upload_directory($newpath);
					$newpath .= "$oldpath->path$oldpath->name/$foldername";
					if (!$dupname && !@mkdir($newpath)) {
						error(get_string('errnocreatefold', 'block_file_manager'));
					}				
					if (!$dupname && !insert_record('fmanager_folders', $entry)) {
						error(get_string('errnoinsert', 'block_file_manager'));
					}
					if ($dupname != true) {
						print_simple_box(get_string('msgcreationok','block_file_manager'), "center", "", "#FFFFFF");
						redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
					}
				}
			}
		}		
	} else if (isset($_POST['cancel'])) {
		print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
		redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
	}
	
	if (isset($foldid)) {	// Renaming an existing folder
		if ($groupid == 0){
			// Ensures user owns the folder
			fm_user_owns_folder($foldid);
		} else {
			// Ensures group owns the folder
			fm_group_owns_folder($foldid, $groupid);
		}
		print_simple_box(get_string('msgfoldmod', 'block_file_manager'), 'center');
	} else {
		print_simple_box(get_string('msgfoldcreate', 'block_file_manager'), 'center');
	}
	print_simple_box_start('center', '350');
	include("folder_manage.html");
	print_simple_box_end();
	
	print_footer();
?>