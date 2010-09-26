<?php
/**
* This file provides the user the ability to create/modify
* a new or existing category.
*
* @package block_file_manager
* @category block
*
*/
    require_once("../../config.php");
    require_once("lib.php");
	
	$id         = required_param('id', PARAM_INT);
	$catid      = optional_param('catid', NULL, PARAM_INT);
	$groupid    = optional_param('groupid', 0, PARAM_INT);
	$catname    = trim(optional_param('catname', '', PARAM_CLEAN));
	$store      = optional_param('store', NULL, PARAM_CLEAN);
	$from       = optional_param('from', NULL, PARAM_ALPHAEXT);
	$fromid     = optional_param('fromid', NULL, PARAM_INT);		// Stores id of updating link
	$cat        = optional_param('multcat', NULL, PARAM_INT);
	$rootdir    = optional_param('rootdir', NULL, PARAM_INT);

    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);
	
	$cb = fm_clean_checkbox_array();

	if ($groupid == 0){
		$ownertype = OWNERISUSER;
	} else {
		$ownertype = OWNERISGROUP;
	}
	
	if ($from == 'link_manage') {	// Grabs info from link_manage form (WIP)
		$linkname = optional_param('linkname', NULL, PARAM_ALPHAEXT);
		$linkcat = optional_param('linkcat', NULL, PARAM_INT);
		$rad = optional_param('rad', 'file', PARAM_ALPHA);
		if ($rad == "file") {
			$linkfile = optional_param('linkfile', NULL, PARAM_CLEAN);
		} else {
			$linkurl = optional_param('linkurl', NULL, PARAM_CLEAN);
		}
		$linkdesc = optional_param('linkdesc', NULL, PARAM_ALPHAEXT);
	}
	$dupname = false;			// Flag for duplicate names
	
    if (! $course = get_record('course', 'id', $id) ) {
        error("That's an invalid course id", "view.php?id={$id}&amp;rootdir={$rootdir}");
    }
	require_login($course->id);
	
	// Ensures the user is able to view the fmanager
	fm_check_access_rights($course->id);	
	
    $strtitle = get_string('titlecats','block_file_manager');
    $nav[] = array('name'=>get_string('filemanager','block_file_manager'), 'link'=>"view.php?id=$id&groupid={$groupid}", 'type'=>'misc');
    $nav[] = array('name'=>$strtitle, 'link'=>null, 'type'=>'misc');
    $navigation = build_navigation($nav);
    print_header($strtitle, format_string($course->fullname), $navigation, "", "", false, "&nbsp;", "&nbsp;");
	  
	print_heading(get_string("titlecats", 'block_file_manager'));
    
    echo '<br/>';
	if (isset($_POST['cancel'])) {
		if ($from == 'link' || $from == NULL) {
			print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
			redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
		}
		print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
		redirect("$from.php?id={$id}&amp;groupid={$groupid}&amp;linkid={$fromid}");
	}
	if (isset($_POST['assigncat'])) {
		foreach ($cb as $c) {
			if (substr($c, 0, 2) == 'f-') {
				$tmp = (int)substr($c, 2);
				$fold = NULL;
				$fold->id = $tmp;
				$fold->category = $cat;
				if (!update_record('fmanager_folders',$fold)) {
					error(get_string('errnoupdate','block_file_manager'));
				}
			} else {
				if ($c != 0) {
					$link = NULL;
					$link->id = $c;
					$link->category = $cat;
					if (!update_record('fmanager_link',$link)) {
						error(get_string('errnoupdate','block_file_manager'));
					}
				}
			}
		}
		print_simple_box(get_string('msgcatassigned', 'block_file_manager'), 'center', "", "#FFFFFF");
		redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
	}
	if (isset($_POST['submit'])) {
		if ($catname == '') {	// verify's input
			print_simple_box(get_string('msgneedinputname','block_file_manager'), "center", "", "#FFFFFF");
			$dupname = true;
		} else {
			// Wont create categories with the same name
			if ($groupid == 0){
				$category = get_record('fmanager_categories', "owner", $USER->id, "ownertype", $ownertype, "name", $catname);
			} else {
				$category = get_record('fmanager_categories', "owner", $groupid, "ownertype", $ownertype, "name", $catname);
			}
			if (isset($category->id) && $category->id != $catid) {
				print_simple_box(get_string('msgduplicate','block_file_manager'), "center", "", "#FFFFFF");
				$dupname = true;
			} else {
				fm_update_category($catname, $catid, $groupid);
				print_simple_box(get_string('msgmodificationok','block_file_manager'), "center", "", "#FFFFFF");
				if ($from != NULL) {
					// bug fix...
					if ($from == 'link') {	// from assign mult categories
						redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
					}
					if ($from == 'folder_manage') {
						redirect("{$from}.php?id={$id}&amp;groupid={$groupid}&amp;foldid={$fromid}");
					} else {
						redirect("{$from}.php?id={$id}&amp;groupid={$groupid}&amp;linkid={$fromid}");
					}
				} else {
					redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
				}
			}
		}				
	}
	
	if (isset($catid)) {	// Modifying an existing category
		if ($groupid == 0){
			fm_user_owns_cat($catid);	// Ensures the user owns the category
		} else {
			fm_group_owns_cat($catid, $groupid);
			// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
			$groupmode = groups_get_course_groupmode($course);
			switch ($groupmode){
			case NOGROUPS : 
				// Should no to be there ...
				error(get_string('errnogroups', 'block_file_manager'), "$CFG->wwwroot/course/view.php?id={$course->id}");
				break;
			case VISIBLEGROUPS :
			case SEPARATEGROUPS : 
				if (!$canmanagegroups && !groups_is_member($groupid)){ // Must check if the user is member of that group
					error(get_string('errnotmemberreadonly', 'block_file_manager'), "view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
				}
				break;
		}
		}
		print_simple_box(get_string('msgcatmodify', 'block_file_manager'), 'center');
	} else {
		if ($groupid != 0){
			// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
			$groupmode = groups_get_course_groupmode($course);
			switch ($groupmode){
			case NOGROUPS : 
				// Should no to be there ...
				error(get_string('errnogroups', 'block_file_manager'), "$CFG->wwwroot/course/view.php?id={$course->id}");
				break;
			case VISIBLEGROUPS :
			case SEPARATEGROUPS : 
				if (!$canmanagegroups && !groups_is_member($groupid)){ // Must check if the user is member of that group
					error(get_string('errnotmemberreadonly', 'block_file_manager'), "view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
				}
				break;
			}
		}
		if (!isset($from) || $from == NULL){
			print_simple_box(get_string('msgcatcreate', 'block_file_manager'), 'center');
		} else {
			print_simple_box(get_string('msgcatassign', 'block_file_manager'), 'center');
		}
	}
	print_simple_box_start('center', '350', '#C0C0C0');

	include('cat_manage.html');

	print_simple_box_end();
	
	print_footer();
?>