<?php
/**
* link_manage.php
* 
* This file manages the uploading and editing of links (files/urls etc)
* For deleting, see conf_delete.php
* For sharing, see share_link.php
*
* @package block_file_manager
* @category block
*/
    require_once("../../config.php");
    require_once($CFG->dirroot.'/blocks/file_manager/lib.php');
	require_once($CFG->dirroot.'/blocks/file_manager/print_lib.php');

	$id         = required_param('id', PARAM_INT); // the current course
	$groupid    = optional_param('groupid', 0, PARAM_INT);
	$rootdir    = optional_param('rootdir', 0, PARAM_INT);
	$linkname   = trim(optional_param('linkname', '', PARAM_CLEAN));
	$linkcat    = optional_param('linkcat', '', PARAM_INT);
	$rad        = optional_param('rad', 'file', PARAM_ALPHA);
	$linkdesc   = optional_param('linkdesc', '', PARAM_CLEAN);

    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);

	if ($rad == 'file') {
		$linkurl = @$_FILES['linkfile']['name']; 
		$linkurl = clean_param($linkurl, PARAM_FILE);
	} else {
		$linkurl = trim(optional_param('linkurl', '', PARAM_CLEAN));
	}

	$linkid     = optional_param('linkid', NULL, PARAM_INT);		// If page called with linkid, them modding old link
	$store      = optional_param('store', NULL, PARAM_CLEAN);
	$linkrename = trim(optional_param('linkrename', NULL, PARAM_FILE));
	$popup      = optional_param('popup', NULL, PARAM_FILE);
	$changefilename = trim(optional_param('changefilename',NULL,PARAM_FILE));
	
	$store->name = $linkname;
	$store->category = $linkcat;
	$store->url = $linkurl;
	$store->description = $linkdesc;	
	$store->radioval = $rad;
	$store->folder = $rootdir;
	$store->link = $changefilename;
	
	$missinginput = array('name' => false, 'link' => false);
	$dupfile = false;

	$onload = '';
	if ($popup == 'close'){
		$onload = 'onload="window.close()"';
	} elseif ($popup != '') {
		$onload = 'onload="window.focus()"';
	}

    if (! $course = get_record('course', 'id', $id) ) {
        error('Invalid Course Id', "view.php?id={$id}&groupid={$groupid}&amp;rootdir={$rootdir}");
    }

	require_login($course->id);
		
	// Ensures the user is able to view the fmanager
	fm_check_access_rights($course->id);	
	
	if ($groupid == 0){
		// Ensures user owns the folder
		fm_user_owns_folder($rootdir);
	} else {
		// Ensures group owns the folder
		fm_group_owns_folder($rootdir, $groupid);
		// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
		// 1.8 old code compatible
		if (function_exists('build_navigation')){
		    $groupmode = groups_get_course_groupmode($course);
		} else {
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

	// Prints the folders breadcrumb navigation links
    $tmplink = '';
	if ($rootdir != 0) { // if we are in another folder than the root of the user or group
		$folder = get_record('fmanager_folders', 'id', $rootdir);
		$tmplink = @$action;
		if ($tmplink != '') {
			$tmplink = '&amp;action='.$tmplink;
		}

		while ($folder->pathid != 0) {
			$nav[] = array('name'=>format_text($folder->name,FORMAT_PLAIN), 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$folder->id}{$tmplink}", 'type'=>'misc'); // " -> <a href='view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$folder->id}{$tmplink}'>{$folder->name}</a>".$rootlink;
			$folder = get_record('fmanager_folders', 'id', $folder->pathid);
		}
		$nav[] = array('name'=>format_text($folder->name,FORMAT_PLAIN), 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$folder->id}{$tmplink}", 'type'=>'misc');
    }
    $strtitle = get_string('filemanager','block_file_manager');
    $nav[] = array('name'=>$strtitle, 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$tmplink}", 'type'=>'misc');
    $nav = array_reverse($nav);
    $nav[] = array('name'=>get_string('addlink', 'block_file_manager'), 'link'=>null, 'type'=>'misc');
    $navigation = build_navigation($nav);
    print_header($strtitle, format_string($course->fullname), $navigation, "", "", false, "&nbsp;", "&nbsp;");     
	  
	print_heading(get_string('addlink', 'block_file_manager'));
	
	if (isset($_POST['cancel'])) {
		print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
		redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
	}
	if (isset($_POST['unzip'])) {
		print_simple_box(get_string('msgunzipinprogress', 'block_file_manager'), 'center');
		redirect("zip.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}&amp;zipid={$linkid}&amp;what='unzip'");
	}
	if (isset($_POST['add'])) {
		if ($linkname == '' || $linkurl == '') {	// verify's input
			print_simple_box(get_string('msgneedinputname', 'block_file_manager'), 'center', '', '#FFFFFF');
			if($linkname == '') { 
				$missinginput['name'] = true;
			}
			if ($linkurl == '') {
				$missinginput['link'] = true;
			}
		} else {
			if ($store->radioval == 'file') {
				// If hd space exceeded...unlinks file and issue warning
				if ($groupid == 0){
					$tmpdir = $CFG->dataroot."/".fm_get_user_dir_space();
				} else {
					$tmpdir = $CFG->dataroot."/".fm_get_group_dir_space($groupid);
				}
				$usertype = fm_get_user_int_type();
				$adminsettings = get_record('fmanager_admin', 'usertype', $usertype);
				if ($_FILES['linkfile']['size'] > ($adminsettings->maxupload * (1048576))) {
					error(get_string('errfiletoolarge', 'block_file_manager'));
				} else {
					$store->url = fm_upload_file($_FILES['linkfile'], $linkrename, $rootdir, $groupid);
					$dirsize = fm_get_size($tmpdir, 1);
					if ((($adminsettings->maxdir * 1048576) - $dirsize) < 0 && $adminsettings->maxdir != 0) {
						unlink($tmpdir.fm_get_folder_path($store->folder, false, $groupid)."/".$store->url);
						error(get_string('errmaxdirexceeded', 'block_file_manager'), "link_manage.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
					}
				}
			}
			if ($store->url != '') {
				fm_update_link($store, $groupid);
				print_simple_box(get_string('msgmodificationok','block_file_manager'), "center", "", "#FFFFFF");
				if (!$popup){
					redirect("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
				} else {
					redirect("link_manage.php?id={$id}&groupid={$groupid}&popup=close&rootdir={$rootdir}");
				}
			} else {
				// Add option to rename file if it exists already
				print_simple_box(get_string('msgfileexists', 'block_file_manager', $_FILES['linkfile']['name']), 'center', '', '#FFFFFF');
				$dupfile = true;
				$missinginput['link'] = true;
			}				
		}
	}
	if (isset($_POST['change'])) {
		if ($store->name == '' || (($store->radioval == 'url') && ($store->url == ''))) {	// verify's input
			print_simple_box(get_string('msgneedinputname', 'block_file_manager'), 'center', '', '#FFFFFF');
		} else {
			if ($groupid == 0) {
				fm_user_owns_link($linkid);
			} else {
				fm_group_owns_link($linkid, $groupid);
			}
			fm_update_link($store, $groupid, $linkid, $id, $rootdir);
			print_simple_box(get_string('msgmodificationok','block_file_manager'), "center", "", "#FFFFFF");
			if (!$popup){
				redirect("view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
			} else {
				redirect("link_manage.php?id={$id}&amp;groupid={$groupid}&amp;popup=close&amp;rootdir={$rootdir}");
			}
		}
	}
    
	if ($linkid != NULL) {		// Modding existing link
		if ($groupid == 0) {
			fm_user_owns_link($linkid);
		} else {
			fm_group_owns_link($linkid, $groupid);
		}
		echo '<br/>'.print_simple_box(text_to_html(get_string('msgmodlink', 'block_file_manager')), 'center', '').'<br/>';
	} else {
		echo '<br/>'.print_simple_box(text_to_html(get_string('msgaddlink', 'block_file_manager')), 'center', '').'<br/>';
	}
	print_simple_box_start('center', '', '#C0C0C0');
	
	$linkrec = NULL;
	if ($linkid != NULL) {
		if ($groupid == 0) {
			$linkrec = fm_get_user_link($linkid);
		} else {
			$linkrec = fm_get_group_link($linkid, $groupid);
		}
		$store->name = $linkrec->name;
		$store->category = $linkrec->category;
		if ($linkrec->type == TYPE_URL) {	// a url link
			$store->url = $linkrec->link;
			$store->radioval = "url";
		}
		$store->description = $linkrec->description;

	}
	
	$foldid = '';
	include('link_manage.html');

	print_simple_box_end();
	
	print_footer();
?>