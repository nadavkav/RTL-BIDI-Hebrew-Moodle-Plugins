<?php
/**********************************************************/
// confirm_delete.php
// 
// This file checks to make sure the user wants to delete
// the file/link/folder/category etc that they clicked on.
/*********************************************************/
/////////////// CB on line 105ish is being all set to 0!///////////
//////////////// NEed to fix to get multiple folder deletion working //////////

    require_once("../../config.php");
    require_once("lib.php");
	
	$id = required_param('id', PARAM_INT);
	$groupid = optional_param('groupid', 0, PARAM_INT);
	$from = required_param('from', PARAM_ALPHA);
	$rootdir = optional_param('rootdir', 0, PARAM_INT);
	$fromid = optional_param('fromid', NULL, PARAM_INT);
	//$singlecb = optional_param('cb', 0, PARAM_INT);
	$cb = optional_param('cb', array(), PARAM_RAW);
	
    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);

	//if (!isset($singlecb)){
	$cb = fm_clean_checkbox_array();
	//}
	
	if ($fromid != NULL) {
		$cb[] = $fromid;
	}
	$list = NULL;
	$warnmsg = NULL;

    if (! $course = get_record('course', 'id', $id) ) {
        error('Invalid course id', "view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
    }

	require_login($course->id);

	if ($from != 'shared') {
		// Ensures the user is able to view the fmanager
		fm_check_access_rights($course->id);	
	}
	
	// If we try to access to a file, category or sharing of a group
	if ($groupid != 0){
		// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
		$groupmode = groups_get_course_groupmode($course);
		
		switch ($groupmode){
			case NOGROUPS : 
				// Should no to be there ...
				error(get_string("errnogroups", 'block_file_manager'), "$CFG->wwwroot/course/view.php?id={$course->id}");
				break;
			case VISIBLEGROUPS :
			case SEPARATEGROUPS : 
				if (!$canmanagegroups && !groups_is_member($groupid)){ // Must check if the user is member of that group
					error(get_string("errnotmemberreadonly", 'block_file_manager'), "view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
				}
				break;
		}
	}

    $strtitle = get_string('confdelete','block_file_manager');
    $navigation = build_navigation(array(array('name'=>$strtitle, 'link'=>"view.php?id=$id&groupid={$groupid}", 'type'=>'misc')));
    print_header($strtitle, format_string($course->fullname), $navigation, "", "", false, "&nbsp;", "&nbsp;");

    // if abandonned and shared
	if (isset($_POST['nodel'])) {
		if ($from == 'shared') {
			print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
			redirect("view_shared.php?id={$id}");
		}
		print_simple_box(get_string('msgcancelok', 'block_file_manager'), 'center');
		redirect("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
	}

/// Used to provide details on deleting confirmation message	  

	$list->type = get_string($from,'block_file_manager' ).get_string('plural', 'block_file_manager');
	$list->thelist = array();
	$list->inusemsg = NULL;
	switch($from) {
		case 'category':
			$catusedfile = 0;	
			$tmpcount = 0;
			// Ensures user owns category(s)
			foreach($cb as $c) {
				$tmplist = '';
				if ($c != 0) {
					if (($groupid == 0 && !fm_user_owns_cat($c)) || ($groupid != 0 && !fm_group_owns_cat($c, $groupid))) {		
						die();		// backup...
					}
					if (!isset($_POST['yesdel']) && !isset($_POST['nodel'])) {
						$tmpcount = $catusedfile;
						$tmpcount += count_records('fmanager_link', 'category', $c);
						$tmpcount += count_records('fmanager_folders', 'category', $c);
						if ($tmpcount > $catusedfile) {
							$tmplist .= '* ';
						}
						$catusedfile += $tmpcount;
						$tmprec = get_record('fmanager_categories', 'id', $c);
						$tmplist .= $tmprec->name;
						$list->thelist[] = $tmplist;
						
						$catshared = count_records('fmanager_shared', 'sharedlink', $c, 'type', STYPE_CAT);
						
					}
					// confirmed
					if (isset($_POST['yesdel'])) {
						$catusedfile = count_records('fmanager_link', 'category', $c);
						$catusedfile += count_records('fmanager_folders', 'category', $c);
						if ($catusedfile > 0) {
							fm_update_links_cats($c);
							fm_update_folders_cats($c);
						}
						$catshared = count_records('fmanager_shared', 'sharedlink', $c, 'type', STYPE_CAT);
						if ($catshared > 0) {
							fm_update_shared_cats($c);
						}
						$catusedfile = 0;
						$catshared = 0;
					}
				}
			}

			if (fm_process_del('fmanager_categories', $cb)) {
				print_simple_box(get_string('msgdeleteok', 'block_file_manager'), 'center', "", "#FFFFFF");
				redirect("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
			}
			
			$tempmessage = '';
			if ($catusedfile > 0) {
			    // User is noticed that some file/links or folders are using this category
				$tempmessage .= get_string('msgcatinuse', 'block_file_manager')."<br>";
			}
			if ($catshared > 0) {
				// User is noticed that this category is shared to someone
				$tempmessage .= get_string('msgcatshared', 'block_file_manager');
			}
			$list->inusemsg = $tempmessage;
			break;

// removes a link

		case 'link':
			$linkshared = 0;
			$tmpcount = 0;	
			/* if (!is_array($cb["c"])) { 	
			echo "n'est pas un tableau <br/> \n"; 		test pour voir si c'est un tableau
			}  */
			// F($cb);
			foreach($cb as $c) {
				$tmplist = "";
				if ($c != 0) {
					// Ensures the user owns the link
					if (($groupid == 0 && !fm_user_owns_link($c)) || ($groupid != 0 && !fm_group_owns_link($c, $groupid))) {
						die();		// backup...
					}
					if (!isset($_POST['yesdel']) && !isset($_POST['nodel'])) {
						$tmpcount = $linkshared;
						// Checks if user has shared the file
						if (($tmpcount += count_records('fmanager_shared', 'sharedlink', $c)) > $linkshared) {
							$tmplist .= "* ";
						}
						$linkshared += $tmpcount;
						$tmprec = get_record('fmanager_link', 'id', $c);
						$tmplist .= $tmprec->name;
						$list->thelist[] = $tmplist;
					}
					// delete confirmed
					if (isset($_POST['yesdel'])) {	
						$linkshared = count_records('fmanager_shared', 'sharedlink',$c);
						if ($linkshared > 0) {	
							fm_update_shared_links($c);
						}
						$linkshared = 0;
						if ($groupid == 0){
							$file = fm_get_user_link($c);
						} else {
							$file = fm_get_group_link($c, $groupid);
						}
						if ($file->type == TYPE_FILE || $file->type == TYPE_ZIP) {
							$warnmsg .= fm_remove_file($file,$groupid);
						}
					}					

				// Means that multiple types were selected (folders/links) and these are folders

				} else if (substr($c, 0, 2) == 'f-') {
					$foldid = substr($c,2);
					$tmplist = "";
					if ($groupid == 0) {
						fm_user_owns_folder($foldid);
					} else {
						fm_group_owns_folder($foldid, $groupid);
					}
					if (!isset($_POST['yesdel']) && !isset($_POST['nodel'])) {
						$foldname = get_record('fmanager_folders', 'id', $foldid);
						$tmpcount = count_records('fmanager_shared', 'sharedlink', $foldid, 'type', STYPE_FOLD);
						if ($tmpcount > 0) {
							$list->inusemsg = get_string('msgfolderinuse', 'block_file_manager');
							$tmplist .= '*';
						}
						$tmplist .= $foldname->name;
						$list->thelist[] = $tmplist;
					} 
					// delete confirmed
					if (isset($_POST['yesdel'])) {
						if ($foldid != 0) {
							$tmppath = get_record('fmanager_folders', 'id', $foldid);
							fm_delete_folder($tmppath, $groupid);
						}					
					}
				}
			}
			if (fm_process_del('fmanager_link', $cb)) {
				print_simple_box(get_string('msgdeleteok', 'block_file_manager'), 'center', "", "#FFFFFF");
				redirect("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
			}
			if ($linkshared > 0) {
				$list->inusemsg = get_string('msglinkinuse', 'block_file_manager');
			}
			break;

// deletes a shared object

		case 'shared':
			$list->type = get_string($from, 'block_file_manager').get_string('plural', 'block_file_manager');
			foreach($cb as $c) {
				$tmplist = '';
				if ($c != 0) {
					if (!fm_user_has_shared_ind($c)) {
						die();
					}
					if (!isset($_POST['yesdel']) && !isset($_POST['nodel'])) {
						$tmp = get_record('fmanager_shared', 'id', $c);
						if ($tmp->type == STYPE_FILE) {
							$tmprec = get_record('fmanager_link', 'id', $tmp->sharedlink);
						} else if ($tmp->type == STYPE_CAT) {
							$tmprec = get_record('fmanager_categories','id',$tmp->sharedlink);
						} else if ($tmp->type == STYPE_FOLD) {
							$tmprec = get_record('fmanager_folders','id',$tmp->sharedlink);
						}
						$list->thelist[] = $tmprec->name;
					}
				}
			}
			if (fm_del_shared($cb)) {
				print_simple_box(get_string('msgdeleteok', 'block_file_manager'), 'center', "", "#FFFFFF");
				redirect("view_shared.php?id={$id}&groupid={$groupid}");
			}
			break;

// deletes a folder

		case 'folder':
			$tmpcount = 0;
			$list->type = get_string($from, 'block_file_manager').' '.get_string('plural', 'block_file_manager');
			foreach($cb as $c) {
				$tmplist = '';
				if ($c != 0) {
					if (($groupid == 0 && !fm_user_owns_folder($c)) || ($groupid != 0 && !fm_group_owns_folder($c, $groupid))) {
						die();
					}
					if (!isset($_POST['yesdel']) && !isset($_POST['nodel'])) {
						$tmpcount = count_records('fmanager_shared', 'sharedlink', $c, 'type' ,2);
						if ($tmpcount > 0) {
							$tmplist .= '*';
						}
						$tmprec = get_record('fmanager_folders', 'id', $c);
						$tmplist .= $tmprec->name;
						$list->thelist[] = $tmplist;
					}
				}
				if ($tmpcount > 0) {
					$list->inusemsg = get_string('msgfolderinuse', 'block_file_manager');
				}
			}
			$list->inusemsg .= get_string('msgsublinksdeleted', 'block_file_manager');
			if (isset($_POST['yesdel'])) {
				$chkdup = NULL;
				foreach($cb as $c) {
					if ($c != 0 && $chkdup != $c) {
						$tmppath = get_record('fmanager_folders', 'id', $c);
						fm_delete_folder($tmppath, $groupid);
					}
					$chkdup = $c;
				}
					// ALl deletion handled by fm_delete_folder function
				//if (fm_process_del('fmanager_folders',$cb)) {
					print_simple_box(get_string('msgdeleteok', 'block_file_manager'), 'center', "", "#FFFFFF");
					redirect("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
				//}
			}			
			break;
		default:
			error(get_string('errwrongparam', 'block_file_manager'), "view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}");
			break;
	}

	print_heading(get_string('confdelete', 'block_file_manager'));
    
    echo '<br/>';
	print_simple_box_start('center', '', '#C0C0C0');
    print_simple_box_start('center', '100%', '#FFFFFF');
	echo text_to_html(get_string('msgconfdelete', 'block_file_manager', $list->type));
	echo '<center>';
	foreach ($list->thelist as $tmp) {
		echo "$tmp<br/>";
	}
	echo '</center>';
	echo text_to_html($list->inusemsg);
	print_simple_box_end();
	
	echo "<center><form action=\"conf_delete.php?id={$id}&groupid={$groupid}&from={$from}&fromid={$fromid}&rootdir={$rootdir}\" method=\"post\">";
	echo "<input type=\"submit\" name=\"yesdel\" value=\"".get_string('btnyes', 'block_file_manager')."\">&nbsp;&nbsp;";
	echo "<input type=\"submit\" name=\"nodel\" value=\"".get_string('btnno', 'block_file_manager')."\">";	
	// Stores $cb id's
	if ($cb != NULL) {
		foreach($cb as $c) {
			echo "<input type=\"hidden\" name=\"cb[]\" value=\"$c\">";
		}
	}
	echo "</form></center>";
	print_simple_box_end();
	
	print_footer();
	
?>