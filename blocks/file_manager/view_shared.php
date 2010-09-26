<?php 
/**
* This file allows users to view a list of people who have
* shared files to them. When clicked they then view all
* files shared to them from that person.
*
* @package block_file_manager
* @category block
* @author
* @date
*/

    require_once("../../config.php");
    require_once($CFG->dirroot.'/blocks/file_manager/lib.php');
	require_once($CFG->dirroot.'/blocks/file_manager/print_lib.php');
	
	$id         = required_param('id', PARAM_INT);
	$rootdir    = optional_param('rootdir', 0, PARAM_INT);
	$original   = optional_param('original', NULL, PARAM_INT);		// If set, then they are viewing shared files from one user or one group
	$ownertype  = optional_param('ownertype', 0, PARAM_INT);			// Tells what kind of owner (see lib.php to know different owner types)
	$catlinkid  = optional_param('catlinkid', NULL, PARAM_INT);
	$foldlinkid = optional_param('foldlinkid', NULL, PARAM_INT);
	$sitewide   = optional_param('sitewide', NULL, PARAM_INT);

    if (! $course = get_record('course', 'id', $id) ) {
        error("That's an invalid course id", "view.php?id={$id}&amp;rootdir={$rootdir}");
    }

	require_login($course->id);
	
	$nav = array();

	$msgexplain = '';		

	if (isset($original)) {
        $nav[] = array('name'=>get_string("sharedfiles", 'block_file_manager'), 'link'=>"view_shared.php?id={$id}&amp;ownertype={$ownertype}",'type'=>'misc');
		$msgexplain = get_string('msgexplainingsharedind','block_file_manager');
		// Removes new! flag from all files from this user upon viewing them
		$mysql = "
		    SELECT 
		        * 
		    FROM 
		        {$CFG->prefix}fmanager_shared 
		    WHERE 
		        owner = {$original} AND 
		        ownertype = {$ownertype} AND 
		        course = {$id} AND 
		        userid = {$USER->id} AND 
		        viewed = 0
		";
		$allnewshared = get_records_sql($mysql);
		if (!empty($allnewshared)) {
			foreach ($allnewshared as $s) {
				$s->viewed = 1;
				if (!update_record('fmanager_shared', $s)) {
					error(get_string('errnoupdate', 'block_file_manager'));
				}
			}
		}
	} else {
		$msgexplain = get_string('msgexplainingshared','block_file_manager');
	}
	if (isset($catlinkid)) {
		fm_user_has_shared($original, 0, $ownertype);
		fm_user_has_shared_cat($original, $catlinkid, $ownertype);
		$head = get_string('sharedcat', 'block_file_manager');
		$msgexplain = get_string('msgexpsharedcat', 'block_file_manager');
	} elseif (isset($foldlinkid)) {
		fm_user_has_shared($original, 0, $ownertype);
		fm_user_has_shared_folder($original, $foldlinkid, $ownertype);
		$head = get_string('sharedfold', 'block_file_manager');
		$msgexplain = get_string('msgexpsharedfold', 'block_file_manager');
	} else {
		$head = get_string('sharedfiles', 'block_file_manager');
	}
    $strtitle = get_string('othersharedfiles','block_file_manager');
    $nav[] = array('name'=>$strtitle, 'link'=>null,'type'=>'misc');
    $navigation = build_navigation($nav);
    print_header($strtitle, format_string($course->fullname), $navigation, '', '', false, "&nbsp;", "&nbsp;");

	// Ensures the user can view the file from the user
	if (isset($original)) {
		fm_user_has_shared($original, 0, $ownertype);
	}    
	print_heading($head);
    echo '<br/>';
    print_simple_box( text_to_html($msgexplain) , 'center');
	echo '<br/>';
	
	if (isset($catlinkid)) {
		print_table(fm_print_user_shared_cat($id, $catlinkid, $original, $ownertype, OWNERISUSER, OWNERISGROUP));
	} elseif (isset($foldlinkid)) {
		print_table(fm_print_user_shared_folder($id, $foldlinkid, $original, $ownertype, OWNERISUSER, OWNERISGROUP));		
	} else {
		// If $original not set, shows all files shared to current user
		if (!isset($original)) {
			// Link shows coursewide/sitewide shared
			if (!isset($sitewide)) {
				echo "<center><a href=\"view_shared.php?id={$id}&amp;sitewide=1\">".get_string("showallshared",'block_file_manager')."</a>";
			} else {
				echo "<center><a href=\"view_shared.php?id=$id\">".get_string("showcourseonly",'block_file_manager')."</a>";
			}
			helpbutton('showfiles', get_string('showfileshelp', 'block_file_manager'), 'block_file_manager');
			echo "</center><br/>";

			fm_print_users_shared($id, $sitewide, OWNERISUSER, OWNERISGROUP);
		} else {
			echo "<form name=\"sharedform\" method=\"post\" action=\"conf_delete.php?id={$id}&amp;from='shared'\">";
			echo "<script language=\"javascript\">
					<!--
						// selects all the checkboxes for the form
						function selectboxes(allbox) {
							for (var i = 0; i < document.sharedform[\"cb[]\"].length; i++) {
								document.sharedform[\"cb[]\"][i].checked = allbox.checked;
							}
						}
					-->
					</script>";
			echo fm_print_actions_menu($id, 'shared',$rootdir);
			helpbutton('sharedfilesaction', get_string('menuhelp', 'block_file_manager'), 'block_file_manager');
			echo fm_print_js_amenushared();
			// Prints the selected users shared files to current user
			if ($ownertype == OWNERISUSER){
				if (!$orig = get_record('user', 'id', $original)) {
						error(get_string('errcantfinduser', 'block_file_manager'));
				}
				echo "<b><center>".get_string('sharedfiles', 'block_file_manager')." ".get_string('by', 'block_file_manager')." $orig->lastname $orig->firstname</center></b>";
			} else {
				if (!$orig = get_record('groups', 'id', $original)) {
						error(get_string('errcantfinduser', 'block_file_manager'));
				}
				echo "<b><center>".get_string('sharedfiles', 'block_file_manager')." ".get_string('by', 'block_file_manager')." $orig->name</center></b>";
			}
			
			print_table(fm_print_user_shared($orig, $id, $ownertype, OWNERISUSER, OWNERISGROUP));
			echo "</form>";
		}
	}
	if (!isset($catlinkid) && !isset($foldlinkid)) {
	    print_footer($course);
	}

?>