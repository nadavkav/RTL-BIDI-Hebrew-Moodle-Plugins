<?php
/****************************************************************************
 Filename:  print_lib.php
  Created:  Michael Avelar
  Created:  6/8/05
 Modified:  6/8/05
  Purpose:  This file contains all the print function mess used within the file_manager
*****************************************************************************/
/**************************** Organization **********************************/
// Print Functions				// (See print_lib.php) Functions to print tables/forms to screen etc.
// JS Print Functions			// (See print_lib.php) Prints short javascript functions
/****************************************************************************/

/*************************** Print Functions ********************************/
// Returns an object of all files shared to USER from the for use in print_table()
//    fm_print_users_shared($id, $sitewide)
// Returns an object of all files shared to USER from selected user for use in print_table()
//	fm_print_user_shared($record, $id)
// Prints the category list owned by current user
// 	fm_print_category_list($id)
// Returns an object of all files/links USER has uploaded for use in print_table
//	fm_print_user_files_form($id)
// Returns a box to be printed containing actions for multiple selections
// 	fm_print_actions_menu($id, $for)
// Returns an object of all users for use in the print_table
// 	fm_print_share_course_members($id)
// Prints a list of members of users group
//	fm_print_separate_groups()
// Prints a list of all groups in the course
// 	fm_print_visible_groups()
// Returns an object with all links with applied category for viewing in shared table
//	fm_print_user_shared_cat($id,$catid,$original)
// Returns an object with all links under the folder for viewing in shared table
//	fm_print_user_shared_folder($id,$foldid,$original)
/****************************************************************************/


// $id 		= source course id
// $sitewide = if true (1) then shows all shared sitewide
  function fm_print_users_shared($id, $sitewide = NULL, $ownerisuser, $ownerisgroup) {
    global $CFG, $USER;
    $table = NULL;
    $fmdir = fm_get_root_dir();

    $table->align = array("center");
    $table->width = "35%";
    $table->data = array();

    $table->head = array(get_string("usersshared", 'block_file_manager'));
    // Prepares sql statement and counts newfiles
    if ($sitewide == 1) {
      $mysql = "SELECT * FROM {$CFG->prefix}fmanager_shared WHERE  userid = '$USER->id' OR userid = '0'";
    } else {
      $mysql = "SELECT * FROM {$CFG->prefix}fmanager_shared WHERE userid = '$USER->id' AND course = '$id' OR userid = '0' AND course = '$id'";
    }
    // Pulls out all shared users files
    $users = get_records_sql($mysql);
    if ($users) {
      $userarr = array();
      // Stores one entry per user per course who is sharing
      foreach ($users as $u) {
        $userarr[$u->course][$u->owner] = $u;
      }
      // Prints out users name once for all shared files
      foreach ($userarr as $ua) {
        foreach ($ua as $u) {
          if ($u->ownertype == $ownerisuser) {
            $name = get_record("user", "id", $u->owner);
            $entry = "";
            $entry .= "<a href=\"view_shared.php?id=$u->course&original=$u->owner&ownertype=$ownerisuser\"><img border=\"0\" src=\"../file_manager/pix/noteitshared.gif\" />";
            $entry .= $name->lastname.", ".$name->firstname;
            if (!$course = get_record("course","id",$u->course)) {
              error("That's an invalid course id", "view.php?id={$id}&rootdir={$rootdir}");
            }
            if ($sitewide == 1) {
              $entry .= "  (".get_string("course", 'block_file_manager').": $course->shortname)";
            }
            if ($u->viewed == 0) {
              $entry .= " <img border=\"0\" src=\"../file_manager/pix/new.gif\" />";
            }
          } else {
            $name = get_record("groups", "id", $u->owner);
            $entry = "";
            $entry .= "<a href=\"view_shared.php?id=$u->course&original=$u->owner&ownertype=$ownerisgroup\"><img border=\"0\" src=\"../file_manager/pix/noteitshared.gif\" />";
            $entry .= $name->name;
            if (!$course = get_record("course","id",$u->course)) {
              error("That's an invalid course id", "view.php?id={$id}&rootdir={$rootdir}");
            }
            if ($sitewide == 1) {
              $entry .= "  (".get_string("course", 'block_file_manager').": $course->shortname)";
            }
            if ($u->viewed == 0) {
              $entry .= " <img border=\"0\" src=\"../file_manager/pix/new.gif\" />";
            }
          }
          $table->data[] = array($entry);
        }
      }
    }
    print_table($table);
  }

  // $record 		= record of user who has shared files to current USER
  // $id 			= course id
  function fm_print_user_shared($record, $id=1, $ownertype, $ownerisuser, $ownerisgroup) {
    global $CFG, $USER;
    $table = NULL;
    $table->align = array("center","","center","center","center","center");
    $table->size = array("5%", "23%", "15%", "40%", "10%", "7%");
    $table->data = array();

    $sharedfiles = NULL;
    $mysql = "SELECT * FROM {$CFG->prefix}fmanager_shared WHERE userid = '$USER->id' AND owner = '$record->id' AND course = '$id' OR userid = '0' AND owner = '$record->id' AND course = '$id'";
    $sharedfiles = get_records_sql($mysql);
    if (!$sharedfiles) {
      error(get_string("errnosharedfound", 'block_file_manager'));
    }

    $fmdir = fm_get_root_dir();
    $strcbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"0\" onClick=\"selectboxes(this)\">&nbsp;".get_string("selectall", 'block_file_manager');
    $strnamename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&original=$record->id&ownertype=$ownertype&tsort=sortname\">".get_string("namename",'block_file_manager')."</a>";
    $strcatname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&original=$record->id&ownertype=$ownertype&tsort=sortcat\">".get_string("catname",'block_file_manager')."</a>";
    $strdescname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&original=$record->id&ownertype=$ownertype&tsort=sortdesc\">".get_string("descname",'block_file_manager')."</a>";
    $strdatename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&original=$record->id&ownertype=$ownertype&tsort=sortdate\">".get_string("datename",'block_file_manager')."</a>";
    $stractionname = get_string("actionsname",'block_file_manager');
    $table->head = array($strcbx, $strnamename, $strcatname, $strdescname, $strdatename, $stractionname);

    // Gets all shared files from the user
    foreach ($sharedfiles as $sharedfile){
      // So far...if the user views a users shared files, the NEW flag will be removed at this point
      $sharedfile->viewed = 1;
      if (!update_record('fmanager_shared',$sharedfile)) {
        notify(get_string('errnoupdate','block_file_manager'));
      }
      if ($sharedfile->type == STYPE_FILE) {	// for links
        $fileinfo = get_record('fmanager_link', "id", $sharedfile->sharedlink);
      } else if ($sharedfile->type == STYPE_CAT) {		// for categories
        $fileinfo = get_record('fmanager_categories', "id", $sharedfile->sharedlink);
      } else if ($sharedfile->type == STYPE_FOLD) {
        $fileinfo = get_record('fmanager_folders',"id", $sharedfile->sharedlink);
      }
      if ($fileinfo) {
        fm_user_has_shared($fileinfo->owner, $sharedfile->sharedlink, $ownertype);	// Ensures they can view the file
      }

      $options = "menubar=1,toolbar=1,status=1,location=0,scrollbars,resizable,width=700,height=500,top=20,left=20";
      if ($sharedfile->type == STYPE_FILE) {		// for links
        if ($fileinfo->type == TYPE_FILE) {
          $icon = "file.gif";
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&fileid=$fileinfo->id";
          if ($ownertype == $ownerisgroup) {
            $tmphref .= "&groupid=$sharedfile->owner";
          }
          $hreflink = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $fileinfo->link)."\" href=\"$tmphref\" onClick=\"window.open('$fileinfo->link','urlpopup','$options');\">";
        } else if ($fileinfo->type == TYPE_URL) {
          $hreflink = "<a target=\"urlpopup\" title=\"".get_string("msgopenlink",'block_file_manager')."\" href=\"$fileinfo->link\"".
          "onClick=\"window.open('$fileinfo->link','urlpopup','$options');\">";
          $icon = "www.gif";
        } else if ($fileinfo->type == TYPE_ZIP) {
          $icon = "zip.gif";
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&fileid=$fileinfo->id";
          if ($ownertype == $ownerisgroup) {
            $tmphref .= "&groupid=$sharedfile->owner";
          }
          $hreflink = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $fileinfo->link)."\" href=\"$tmphref\" onClick=\"window.open('$fileinfo->link','urlpopup','$options');\">";
        }
      } else if ($sharedfile->type == STYPE_CAT) {		// for categories
        $icon = "cat.gif";
        $tmphref = "$CFG->wwwroot/blocks/file_manager/view_shared.php?id=$id&ownertype=$ownertype&original=$record->id&catlinkid=$fileinfo->id";
        $hreflink = "<a target=\"catpopup\" title=\"".get_string("msgopencat",'block_file_manager')."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','catpopup','$options');\">";
      } else if ($sharedfile->type == STYPE_FOLD) {
        $icon = "folder.gif";
        $tmphref = "$CFG->wwwroot/blocks/file_manager/view_shared.php?id=$id&ownertype=$ownertype&original=$record->id&foldlinkid=$fileinfo->id";
        $hreflink = "<a target=\"foldpopup\" title=\"".get_string("msgopenfold",'block_file_manager')."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','foldpopup','$options');\">";
      }
      if ($sharedfile->userid == 0) {
        $cbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"$sharedfile->id\">";
      } else {
        $cbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"$sharedfile->id\">";
      }
      $name = "$hreflink<img src=\"$CFG->wwwroot/blocks/file_manager/pix/$icon\" />&nbsp;$fileinfo->name</a>";
      $tmp = "";
      if (isset($fileinfo->category)) {
        $tmp = fm_get_user_categories($fileinfo->category);
      }
      $desc = "";
      if (isset($fileinfo->description)) {
        $desc = $fileinfo->description;
      }
      $date = "<font size=1>".userdate($fileinfo->timemodified, "%m/%d/%Y  %H:%I")."</font>";
      $actions = "<a href=\"conf_delete.php?id=$id&from='shared'&fromid=$sharedfile->id\" /><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" . get_string("delete"). "\"></a>";
      $table->data[] = array($cbx,$name,$tmp,$desc,$date, $actions);
    }
    return $table;
  }

  // $id 		= course id
  function fm_print_category_list($id, $rootdir, $groupid=0,$readonlyaccess=false) {
    global $CFG, $USER;

    $fmdir = fm_get_root_dir();
    $fmdir = "$CFG->wwwroot/$fmdir/view.php?id=$id&groupid=$groupid&tsort";
    $strcbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"0\" onClick=\"selectboxes(this, 2)\">&nbsp;".get_string("selectall",'block_file_manager');
    $cathead = "<a href=\"$fmdir=sortnamecat\">".get_string("catname",'block_file_manager')." ".get_string("namename", 'block_file_manager')."</a>";
      $actionhead = get_string("actionsname", 'block_file_manager');

    if(!$readonlyaccess){
      $table->head = array($strcbx, $cathead, $actionhead);
    }else{
      $table->head = array($cathead);
    }
    $table->align = array("center", "center", "center");
    $table->width = "40%";
    $table->size = array("20%", "60%", "10%");
    $table->wrap = array(NULL, 'no', 'no');
    $table->data = array();

    if ($groupid==0){
      $ownertype = OWNERISUSER;
      $owncats = get_records_select('fmanager_categories', "owner=$USER->id AND ownertype=$ownertype", "name ASC");
    } else {
      $ownertype = OWNERISGROUP;
      $owncats = get_records_select('fmanager_categories', "owner=$groupid AND ownertype=$ownertype", "name ASC");
    }


      if ($owncats){
      foreach ($owncats as $owncat){
        $tmpcount = count_records('fmanager_shared',"sharedlink",$owncat->id,"type",1,'course',$id);
        if ($tmpcount > 0) {
          $icon = "group.gif";
        } else {
          $icon = "group_noshare.gif";
        }
        $cbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"$owncat->id\">";
        $catname = format_text($owncat->name);

        $actions = "<a title=\"".get_string('edit')."\" href=\"cat_manage.php?id=$id&groupid=$groupid&catid=$owncat->id&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->pixpath/i/edit.gif\" alt=\"" .get_string("edit"). "\" /></a>&nbsp;";
        $actions .= "<a title=\"".get_string('delete')."\" href=\"conf_delete.php?id=$id&groupid=$groupid&from=category&fromid=$owncat->id&rootdir=$rootdir\"><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" .get_string("delete"). "\" /></a>&nbsp;";
        $actions .= "<a title=\"".get_string('msgsharetoothers', 'block_file_manager')."\" href=\"sharing.php?id=$id&groupid=$groupid&linkid=$owncat->id&from=category&rootdir=$rootdir\"><img border=\"0\" src=\"../file_manager/pix/".$icon."\" alt=\"".get_string("msgsharetoothers",'block_file_manager')."\" /></a>&nbsp;";
        if(!$readonlyaccess){
          $table->data[] = array($cbx, $catname, $actions);
        }else{
          $table->data[] = array($catname);
        }
      }
      } else {
      if(!$readonlyaccess){
        $table->data[] = array('', '<center><i><b>'.get_string('msgnonedefined', 'block_file_manager').'</b></i></center>', "&nbsp;");
      }else{
        $table->data[] = array('', '<center><i><b>'.get_string('msgnonedefined', 'block_file_manager').'</b></i></center>');
      }
    }
    return $table;
  }

  // $id		= course id
  // $groupid		= group id if we want to see files of this group
  // $rootdir	= target directory
  function fm_print_user_files_form($id=1, $rootdir=0, $action='none', $groupid=0,$readonlyaccess=false) {
    global $CFG, $USER;

    // TO ADD :
    // if the user is not member of the group, he can't change anything, he just has the reading right only if the group mode is GROUP VISIBLE
    unset($table);
    $fmdir = fm_get_root_dir();

    $strcbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"0\" onClick=\"selectboxes(this, 1)\">&nbsp;".get_string("selectall", 'block_file_manager');
    $strnamename = "<a href=\"$CFG->wwwroot/$fmdir/view.php?id={$id}&groupid=$groupid&rootdir=$rootdir&tsort=sortname\">".get_string('namename','block_file_manager')."</a>";
    $strcatname = "<a href=\"$CFG->wwwroot/$fmdir/view.php?id={$id}&groupid=$groupid&rootdir=$rootdir&tsort=sortcat\">".get_string('catname','block_file_manager')."</a>";
    $strdescname = "<a href=\"$CFG->wwwroot/$fmdir/view.php?id={$id}&groupid=$groupid&rootdir=$rootdir&tsort=sortdesc\">".get_string('descname','block_file_manager')."</a>";
    $strfilesizename = "<a href=\"$CFG->wwwroot/$fmdir/view.php?id={$id}&groupid=$groupid&rootdir=$rootdir&tsort=sortsize\">".get_string('filesizename','block_file_manager')."</a>";
    $strdatename = "<a href=\"$CFG->wwwroot/$fmdir/view.php?id={$id}&groupid=$groupid&rootdir=$rootdir&tsort=sortdate\">".get_string('datename','block_file_manager')."</a>";
    $stractionname = get_string('actionsname','block_file_manager');

    if(!$readonlyaccess){
      $table->head = array($strcbx, $strnamename, $strcatname, $strdescname, $strfilesizename, $strdatename, $stractionname);
    }else{
      $table->head = array("", $strnamename, $strcatname, $strdescname, $strfilesizename, $strdatename);
    }
    $table->align = array("center", "left", "center", "center", "center", "center");
    $table->width = "90%";
    $table->size = array("5%", "20%", "15%", "25%", "10%", "10%", "12%");
    $table->wrap = array(NULL, 'no', 'no', 'no', 'no', 'no');
    $table->data = array();

    if ($action == 'movesel') {
      $moveurl = "&what='$action'";
    } else {
      $moveurl = '';
    }

    // Not at root...so print an up folder link
    if ($rootdir != 0) {
      $tmpfold = get_record('fmanager_folders', "id", $rootdir);
      $name = "<a href=\"view.php?id=$id&groupid=$groupid&rootdir=$tmpfold->pathid$moveurl\"><img border=\"0\" src=\"$CFG->pixpath/f/parent.gif\" alt=\"".get_string("msgrootdir",'block_file_manager')."\" />&nbsp;".get_string("msgrootdir",'block_file_manager')."</a>";
      $table->data[] = array("", $name, "", "", "", "","", "");
    }

    // Prints folders
    if ($groupid==0){
      $ownertype = OWNERISUSER;
      $allfolders = get_records('fmanager_folders', "owner=$USER->id AND ownertype = {$ownertype} AND pathid", $rootdir, "name");
    } else {
      $ownertype = OWNERISGROUP;
      $allfolders = get_records('fmanager_folders', "owner=$groupid AND ownertype = {$ownertype} AND pathid", $rootdir, "name");
    }
    if ($allfolders) {
      foreach($allfolders as $folder) {
        $date = "<font size=1>".userdate($folder->timemodified, "%m/%d/%Y  %H:%I")."</font>";
        $cbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"fold$folder->id\">";
          $actions = "<a title=\"".get_string('edit')."\" href=\"folder_manage.php?id=$id&groupid=$groupid&foldid=$folder->id&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->pixpath/i/edit.gif\" alt=\"" . get_string("edit"). "\" /></a>&nbsp;
              <a title=\"".get_string('delete')."\" href=\"conf_delete.php?id=$id&groupid=$groupid&from=folder&fromid=$folder->id&rootdir=$rootdir\"><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" . get_string("delete"). "\" /></a>";
        // Determines if the user can view the share option in the main course (default is no)
        $userinttype = fm_get_user_int_type();
        $tmpcount = count_records('fmanager_shared',"sharedlink",$folder->id,"type",2,"course",$id);
        if ($tmpcount > 0) {
          $icon = "group.gif";
        } else {
          $icon = "group_noshare.gif";
        }
        $priv = NULL;
        $priv = get_record('fmanager_admin', 'usertype', $userinttype);
        if ($id == 1) {
          // They can share from the main page to anyone
          if ($priv->sharetoany == 1) {
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('sharetoany','block_file_manager')."\" href=\"sharing.php?id={$id}&groupid=$groupid&linkid={$folder->id}&groupid={$groupid}&from=folder&rootdir={$rootdir}\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('sharetoany','block_file_manager')."\" /></a>";
          }
        } else {
          if ($priv->allowsharing == 1) {
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('msgsharetoothers','block_file_manager')."\" href=\"sharing.php?id=$id&groupid=$groupid&linkid=$folder->id&from=folder&groupid={$groupid}&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('msgsharetoothers','block_file_manager')."!\" /></a>";
          }
        }
        $name = "<a href=\"{$CFG->wwwroot}/".fm_get_root_dir()."/view.php?id={$id}&groupid={$groupid}&rootdir={$folder->id}{$moveurl}\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/folder.gif\" alt=\"".get_string('msgfolder','block_file_manager',format_text($folder->name,FORMAT_PLAIN))."\" />". format_text($folder->name,FORMAT_PLAIN)."</a>";
        $catname = get_record('fmanager_categories', 'id', $folder->category);
        if (isset($catname->name)){
          $catname = format_text($catname->name,FORMAT_PLAIN);
        }
        // Finds size of folder
        if ($groupid == 0){
          $tmpdir = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($folder->id, false, $groupid);
        } else {
          $tmpdir = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($folder->id, false, $groupid);
        }
        $filesize = fm_get_size($tmpdir);
        $desc = ''; // There is no description for folders
        if(!$readonlyaccess){
          $table->data[] = array($cbx, $name, $catname, $desc, $filesize, $date, $actions);
        }else{
          $table->data[] = array("", $name, $catname, $desc, $filesize, $date);
        }
      }
    }
    // Prints all links
    if ($groupid==0){
      $ownertype = OWNERISUSER;
      $alllinks = get_records('fmanager_link', "owner={$USER->id} AND ownertype={$ownertype} AND folder", $rootdir, 'name');
    } else {
      $ownertype = OWNERISGROUP;
      $alllinks = get_records('fmanager_link', "owner={$groupid} AND ownertype={$ownertype} AND folder", $rootdir, 'name');
    }
    if (!$alllinks) {
      $table->data[] = array('', '<center><i><b>'.get_string('msgnolinks', 'block_file_manager').'</b></i></center>', '', '', '', '', '');
    } else {
      // Gets all associative information tied with user's links
      foreach($alllinks as $link) {
        $catname = fm_get_user_categories($link->category);
        $date = "<font size=\"1\">".userdate($link->timemodified, "%m/%d/%Y  %H:%I")."</font>";
        $cbx = "<input type=\"checkbox\" name=\"cb[]\" value=\"{$link->id}\" />";
        $actions = "<a title=\"".get_string('edit')."\" href=\"link_manage.php?id={$id}&groupid={$groupid}&linkid={$link->id}&rootdir={$rootdir}\"><img border=\"0\" src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"" . get_string('edit'). "\" /></a>&nbsp;
              <a title=\"".get_string('delete')."\" href=\"conf_delete.php?id=$id&groupid={$groupid}&from=link&fromid={$link->id}&rootdir={$rootdir}\"><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" . get_string('delete'). "\"></a>";
        // Determines if the user can view the share option in the main course (default is no)
        $userinttype = fm_get_user_int_type();
        $tmpcount = count_records('fmanager_shared', 'sharedlink', $link->id, 'type', 0, 'course', $id);
        if ($tmpcount > 0) {
          $icon = "group.gif";
        } else {
          $icon = "group_noshare.gif";
        }
        $priv = NULL;
        $priv = get_record('fmanager_admin', 'usertype', $userinttype);
        if ($id == 1) {
          // They can share from the main page to anyone
          if ($priv->sharetoany == 1) {
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('sharetoany','block_file_manager')."\" href=\"sharing.php?id=$id&groupid={$groupid}&linkid=$link->id&from='link'&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('sharetoany','block_file_manager')."\"></a>";
          }
        } else {
          if ($priv->allowsharing == 1) {
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('msgsharetoothers','block_file_manager')."\" href=\"sharing.php?id=$id&groupid={$groupid}&linkid=$link->id&from='link'&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('msgsharetoothers','block_file_manager')."!\"></a>";
          }
        }

        $options = "menubar=1,toolbar=1,status=1,location=0,scrollbars,resizable,width=700,height=500,top=20,left=20";
        if ($link->type == TYPE_FILE) {
          if ($link->folder != 0) {
            $folderinfo->path = fm_get_folder_path($link->folder, false, $groupid);
            $bdir = $folderinfo->path.$link->link;
          } else {
            $bdir = $link->link;
          }
          $tmphref = "{$CFG->wwwroot}/blocks/file_manager/file.php?cid={$id}&groupid=$groupid&fileid={$link->id}";
          $name = "<a target=\"urlpopup\" title=\"".get_string('msgopenfile', 'block_file_manager', $link->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/file.gif\" >&nbsp;".format_text($link->name,FORMAT_PLAIN)."</a>";
        } else if ($link->type == TYPE_URL) {
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenlink",'block_file_manager')."\" href=\"$link->link\"".
          "onClick=\"window.open('$link->link','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/www.gif\">&nbsp;".format_text($link->name,FORMAT_PLAIN)."</a>";
        } else if ($link->type == TYPE_ZIP) {
          if ($link->folder != 0) {
            $folderinfo = fm_get_folder_path($link->folder, false, $groupid);
            if (isset($folderinfo->path)){
              $bdir = $folderinfo->path.$link->link;
            }
          } else {
            $bdir = $link->link;
          }
          $tmphref = "{$CFG->wwwroot}/blocks/file_manager/file.php?cid={$id}&groupid=$groupid&fileid={$link->id}";
          $name = "<a target=\"urlpopup\" title=\"".get_string('msgopenfile', 'block_file_manager', $link->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/zip.gif\" >&nbsp;".format_text($link->name,FORMAT_PLAIN)."</a>";
        }
        /*
        $desc = wordwrap($link->description, 70, '<br/>');
        $tmp = '';
        if (strlen($desc) > 120) {
          $tmp = "&nbsp;&nbsp;<b><i>(More)...</i></b>";
        }
        $desc = substr($desc, 0, 120);
        $desc = $desc.$tmp;
        */

        $desc = format_text(shorten_text($link->description, 120));

        // Finds size of file
        $filesize = '';
        if ($link->type == TYPE_FILE || $link->type == TYPE_ZIP) {
          if ($groupid == 0){
          $tmpdir = $CFG->dataroot."/".fm_get_user_dir_space().fm_get_folder_path($link->folder, false, $groupid)."/".$link->link;
          } else {
            $tmpdir = $CFG->dataroot."/".fm_get_group_dir_space($groupid).fm_get_folder_path($link->folder, false, $groupid)."/".$link->link;
          }
          $filesize = fm_get_size($tmpdir);
        }
        if(!$readonlyaccess){
          $table->data[] = array($cbx, $name, $catname, $desc, $filesize, $date, $actions);
        }else{
          $table->data[] = array("", $name, $catname, $desc, $filesize, $date);
        }
      }
    }
    return $table;
  }

  // $id 		= course id
  // $for 	= What menu this is for ['link' or 'category' or 'shared']
  function fm_print_actions_menu($id, $for, $rootdir=0, $groupid=0) {
    $opt = NULL;
    if ($for == 'link') {
      $opt = array("view.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&what='movesel'" => get_string("btnmoveact",'block_file_manager'),
            "cat_manage.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&from='link'" => get_string("btnassigncatact",'block_file_manager'),
            "zip.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&what='zipsel'" => get_string("btnzipact",'block_file_manager'),
            "sharing.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&linkid=&from='link'" => get_string('msgsharetoothers','block_file_manager'),
            "sepsel" => "----------",
            "conf_delete.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&from='link'&rootdir=$rootdir" => get_string("btndelact",'block_file_manager'));

      return choose_from_menu($opt, "linksel", "", get_string("btnlinkact",'block_file_manager'), "alinkmenu()", "", true);
    } else if ($for == 'category') {
      $opt = array("sharing.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&linkid=&from='category'" => get_string("msgsharetoothers",'block_file_manager'),
            "sepsel" => "----------",
            "conf_delete.php?id={$id}&groupid={$groupid}&rootdir={$rootdir}&from='category'&rootdir=$rootdir" => get_string("btndelact",'block_file_manager'));

      return choose_from_menu($opt, "catsel", "", get_string("btncatact",'block_file_manager'), "acatmenu()", "", true);
    } else if ($for == 'shared') {
      $opt = array("sepsel" => "----------",
            "conf_delete.php?id=$id&groupid=$groupid&from='shared'&rootdir=$rootdir" => get_string("btndelact",'block_file_manager'));

      return choose_from_menu($opt, "sharedsel", "", get_string("btnsharedact",'block_file_manager'), "amenushared()", "", true);
    }
  }

  // $id		= course id
  // $linkid	= id of the link to share
  // $type 	= type of item to share (0=link;1=cat;2=folder)
  function fm_print_share_course_members($id, $linkid=NULL, $type=0, $groupid=0) {
      global $CFG, $USER;

    $fmdir = fm_get_root_dir();

    $table->align = array("center", "left", "left", "center");
    $table->width = "60%";
    $table->size = array("15%", "35%", "35%", "20%");
    $table->wrap = array(NULL, 'no', 'no', 'no');
    $table->data = array();

    $strcbx = "<input type=\"checkbox\" name=\"cbu[]\" value=\"0\" onClick=\"selectboxes(this, 1)\">&nbsp;".get_string("selectall", 'block_file_manager');
    $strlastname = "<a href=\"$CFG->wwwroot/$fmdir/sharing.php?id=$id&linkid=$linkid&tsort=sortlast\">".get_string("userlastname",'block_file_manager')."</a>";
    $strfirstname = "<a href=\"$CFG->wwwroot/$fmdir/sharing.php?id=$id&linkid=$linkid&tsort=sortfirst\">".get_string("userfirstname",'block_file_manager')."</a>";
    $struserrole = "<a href=\"$CFG->wwwroot/$fmdir/sharing.php?id=$id&linkid=$linkid&tsort=sortrole\">".get_string("userrole",'block_file_manager')."</a>";

    $table->head = array($strcbx, $strlastname, $strfirstname, $struserrole);
    if ($id == 1) { //adding this block to a site frontpage has been disabled - it's really bad performance wise!
      if (!$allusers = get_records_select("user", 'deleted=0', 'lastname', 'lastname, firstname, id')) {
        $table->data[] = array("","<center><i><b>".get_string("msgnocourseusers",'block_file_manager')."</b></i></center>");
      } else {
        //echo "please choose a course, from which, to share resources";
        echo get_string('cannotsharefromsitelevel','block_file_manager');
        continue; // nadavkav . too much load on the server :-(

        // Prints all roles of user throughout site
        foreach($allusers as $key => $au) {
          $allusers[$key]->role = NULL;
          if ( has_capability('moodle/legacy:admin', get_context_instance(CONTEXT_SYSTEM), $au->id, false) ) {
            $allusers[$key]->role = "Admin ";
            $allusers[$key]->userid = $au->id;
          }
          if (isteacherinanycourse($au->id, false)) {
            if ($allusers[$key]->role) { $slash = "/"; }
            $allusers[$key]->role .= "$slash Teacher ";
            $allusers[$key]->userid = $au->id;
          }
          if (record_exists('role_assignments', 'userid', $au->id)) {
            $slash = "";
            if ($allusers[$key]->role) { $slash = "/"; }
            $allusers[$key]->role .= "$slash Student ";
            $allusers[$key]->userid = $au->id;
          }
        }
      }
    } else {
      // We try to select any user who is in this course, his name and his role
// deprecated
//       $sqltoexecute = "SELECT {$CFG->prefix}user.id, {$CFG->prefix}user.firstname, {$CFG->prefix}user.lastname, {$CFG->prefix}role.name
//                         FROM {$CFG->prefix}user, {$CFG->prefix}course_display, {$CFG->prefix}role_assignments, {$CFG->prefix}role
//                         WHERE (course = $id AND {$CFG->prefix}course_display.userid = {$CFG->prefix}user.id
//                           AND {$CFG->prefix}user.id = {$CFG->prefix}role_assignments.userid
//                           AND {$CFG->prefix}role_assignments.roleid = {$CFG->prefix}role.id)";

      //  a better way to get roles of users in a course , for Moodle v1.9.x (nadavkav patch)
      $sqltoexecute = "SELECT u.id, u.firstname, u.lastname, r.name
                        FROM {$CFG->prefix}user u
                        JOIN {$CFG->prefix}role_assignments ra ON ra.userid = u.id
                        JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                        JOIN {$CFG->prefix}context con ON ra.contextid = con.id
                        JOIN {$CFG->prefix}course c ON c.id = con.instanceid AND con.contextlevel = 50
                        WHERE (r.shortname = 'student' OR r.shortname = 'teacher' OR r.shortname = 'editingteacher') AND c.id = {$id}";

      //echo $sqltoexecute;
      // If there is nobody in this course, then print the message "no users in this course"
      if (!$allpersons = get_records_sql($sqltoexecute)) {
        $table->data[] = array("","<center><i><b>".get_string("msgnocourseusers", "block_file_manager")."</b></i></center>");
      } else {
        // Gives all users their role in the course
        $allusers = array();
        if ($allpersons) {
          foreach($allpersons as $key => $au) {
            //$tmp = get_record("user", "id", $au->userid);
            $allusers[$au->id]->role = $au->name;
            $allusers[$au->id]->lastname = $au->lastname;
            $allusers[$au->id]->firstname = $au->firstname;
            $allusers[$au->id]->userid = $au->id;
          }
        }
      }
    }
    if ($allusers) {
      $sharetoall = false;
      // checks if file is shared to all
      if (count_records_sql("SELECT * FROM {$CFG->prefix}fmanager_shared WHERE owner = '$USER->id' AND course = '$id' AND userid = '0' AND sharedlink = '$linkid' AND type = '$type'")) {
        $sharetoall = true;
      }
      foreach ($allusers as $au) {
        if ($au->userid != $USER->id) {		// Cant share to yourself
          if ($linkid != NULL) {
            $thecount = 0;
            if ($sharetoall) {
              $thecount = 1;
            } else {
              if($groupid!=0){
                $mysql = "SELECT * FROM {$CFG->prefix}fmanager_shared WHERE owner = '$groupid' AND course = '$id' AND userid = '$au->userid' AND sharedlink = '$linkid' AND type = '$type'";
              }else{
                $mysql = "SELECT * FROM {$CFG->prefix}fmanager_shared WHERE owner = '$USER->id' AND course = '$id' AND userid = '$au->userid' AND sharedlink = '$linkid' AND type = '$type'";
            }


              $thecount = count_records_sql($mysql);
            }
            if ($thecount > 0) {
              $cbx = "<input type=\"checkbox\" name=\"cbu[]\" value=\"$au->userid\" checked>";
            } else {
              $cbx = "<input type=\"checkbox\" name=\"cbu[]\" value=\"$au->userid\">";
            }
          } else {
            $cbx = "<input type=\"checkbox\" name=\"cbu[]\" value=\"$au->userid\">";
          }
          $table->data[] = array($cbx, format_text($au->lastname,FORMAT_PLAIN), format_text($au->firstname,FORMAT_PLAIN), $au->role);
        }
      }
    }

    return $table;
  }

  // $id			= course id
  // $catid		= category id
  // $original 	= user who shared cat
  function fm_print_user_shared_cat($id,$catid,$original,$ownertype, $ownerisuser, $ownerisgroup) {
    global $USER, $CFG;

    if ($ownertype == $ownerisuser){
      $groupid = 0;
    } else if ($ownertype == $ownerisgroup){
      $groupid = $original;
    }

    $fmdir = fm_get_root_dir();
    $options = "menubar=1,toolbar=1,status=1,location=0,scrollbars,resizable,width=600,height=400,top=150,left=100";
    $table->align = array("", "center", "center", "center");
    $table->width = "100%";
    $table->size = array("25%", "25%", "30%", "20%");
    $table->wrap = array('no', 'no', 'yes', 'no');
    $table->data = array();
    $strnamename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$catid&tsort=sortname\">".get_string("namename",'block_file_manager')."</a>";
    $strcatname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$catid&tsort=sortcat\">".get_string("catname",'block_file_manager')."</a>";
    $strdescname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$catid&tsort=sortdesc\">".get_string("descname",'block_file_manager')."</a>";
    $strdatename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$catid&tsort=sortdate\">".get_string("datename",'block_file_manager')."</a>";
    $table->head = array($strnamename, $strcatname, $strdescname, $strdatename);

    $sharedfolders = fm_get_folder_shared_by_cat($original, $catid);
    if ($sharedfolders) {
      foreach($sharedfolders as $sf) {
        $name = "";
        $cat =  "";
        $desc = "";
        $date = "";
        $linkurl = $CFG->wwwroot."/blocks/file_manager/view_shared.php?id=$id&ownertype=$ownertype&original=$original&foldlinkid=$sf->id";
        $name = "<a target=\"foldpopup\" title=\"".get_string("msgopenlink",'block_file_manager')."\" href=\"$linkurl\"".
          "onClick=\"window.open('$linkurl','foldpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/folder.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        if (isset($sf->description)){
          $desc = format_text($sf->description);
        }
        $cat = fm_get_user_categories($sf->category);
        $date = "<font size=1>".userdate($sf->timemodified, "%m/%d/%Y  %H:%I")."</font>";
        $table->data[] = array($name, $cat, $desc, $date);
      }
    }

    $sharedfiles = fm_get_links_shared_by_cat($original, $catid);
    if ($sharedfiles) {
      foreach($sharedfiles as $sf) {
        $name = "";
        $cat =  "";
        $desc = "";
        $date = "";
        if ($sf->type == TYPE_FILE) {
          if ($sf->folder != 0) {
            $folderinfo = fm_get_folder_path($sf->folder, true, $groupid);
            $bdir = $folderinfo.$sf->link;
          } else {
            $bdir = $sf->link;
          }
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&groupid=$groupid&fileid=$sf->id";
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $sf->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/file.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        } else if ($sf->type == TYPE_URL) {
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenlink",'block_file_manager')."\" href=\"$sf->link\"".
          "onClick=\"window.open('$sf->link','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/www.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        } else if ($sf->type == TYPE_ZIP) {
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&groupid=$groupid&fileid=$sf->id";
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $sf->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/zip.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        }
        $desc = format_text($sf->description);
        $cat = fm_get_user_categories($sf->category);
        $date = "<font size=1>".userdate($sf->timemodified, "%m/%d/%Y  %H:%I")."</font>";
        $table->data[] = array($name, $cat, $desc, $date);
      }
    } else {
      $table->data[] = array(get_string("msgnosharedcatlink",'block_file_manager'));
    }

    return $table;
  }

  // $id 			= course id
  // $foldid		= folder id
  // $original 	= owner of the folder's user id
  function fm_print_user_shared_folder($id,$foldid,$original,$ownertype, $ownerisuser, $ownerisgroup) {
    global $USER, $CFG;

    if ($ownertype == $ownerisuser){
      $groupid = 0;
    } else if ($ownertype == $ownerisgroup){
      $groupid = $original;
    }

    $fmdir = fm_get_root_dir();
    $options = "menubar=1,toolbar=1,status=1,location=0,scrollbars,resizable,width=600,height=400,top=150,left=100";
    $table->align = array("", "center", "center", "center");
    $table->width = "100%";
    $table->size = array("25%", "25%", "30%", "20%");
    $table->wrap = array('no', 'no', 'yes', 'no');
    $table->data = array();
    $strnamename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$foldid&tsort=sortname\">".get_string("namename",'block_file_manager')."</a>";
    $strcatname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$foldid&tsort=sortcat\">".get_string("catname",'block_file_manager')."</a>";
    $strdescname = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$foldid&tsort=sortdesc\">".get_string("descname",'block_file_manager')."</a>";
    $strdatename = "<a href=\"$CFG->wwwroot/$fmdir/view_shared.php?id=$id&ownertype=$ownertype&original=$original&catlinkid=$foldid&tsort=sortdate\">".get_string("datename",'block_file_manager')."</a>";
    $table->head = array($strnamename, $strcatname, $strdescname, $strdatename);

    // Get all shared folders under folder
    $sharedfolder = fm_get_all_sharedf_by_folder($original, $foldid);

    if ($sharedfolder) {
      foreach($sharedfolder as $sf) {
        $name = "";
        $cat =  "";
        $desc = "";
        $date = "";
        $tmpurl = "$CFG->wwwroot/blocks/file_manager/view_shared.php?id=$id&ownertype=$ownertype&original=$original&foldlinkid=$sf->id";
        $name = "<a target=\"foldpopup\" title=\"".get_string("msgopenfold",'block_file_manager')."\" href=\"$tmpurl\">".
            "<img src=\"$CFG->wwwroot/blocks/file_manager/pix/folder.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";

        $table->data[] = array($name, $cat, $desc, $date);
      }
    }

    // Get all shared files under folder
    $sharedfiles = fm_get_all_shared_by_folder($original, $foldid);

    if ($sharedfiles) {
      foreach($sharedfiles as $sf) {
        $name = "";
        $cat =  "";
        $desc = "";
        $date = "";

        if ($sf->type == TYPE_FILE) {
          if ($sf->folder != 0) {
            $folderinfo = fm_get_folder_path($sf->folder, true);
            $bdir = $folderinfo.$sf->link;
          } else {
            $bdir = $sf->link;
          }
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&groupid=$groupid&ownertype=$ownertype&fileid=$sf->id";
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $sf->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/file.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        } else if ($sf->type == TYPE_URL) {
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenlink",'block_file_manager')."\" href=\"$sf->link\"".
          "onClick=\"window.open('$sf->link','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/www.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        } else if ($sf->type == TYPE_ZIP) {
          $tmphref = "$CFG->wwwroot/blocks/file_manager/file.php?cid=$id&groupid=$groupid&ownertype=$ownertype&fileid=$sf->id";
          $name = "<a target=\"urlpopup\" title=\"".get_string("msgopenfile",'block_file_manager', $sf->link)."\" href=\"$tmphref\" onClick=\"window.open('$tmphref','urlpopup','$options');\"><img src=\"$CFG->wwwroot/blocks/file_manager/pix/zip.gif\" >&nbsp;".format_text($sf->name,FORMAT_PLAIN)."</a>";
        }
        $desc = $sf->description;
        $cat = fm_get_user_categories($sf->category);
        $date = "<font size=1>".userdate($sf->timemodified, "%m/%d/%Y  %H:%I")."</font>";
        $table->data[] = array($name, $cat, $desc, $date);
      }
    }
    // If folder is empty
    if (!$sharedfiles && !$sharedfolder) {
      $table->data[] = array(get_string("msgnosharedfoldlink",'block_file_manager'));
    }

    return $table;
  }
  /*************************** JS Print Functions *****************************/
  // Returns js function to allow a js popup
  // 	fm_print_js_popup()
  // Returns js function to select all/none checkboxes
  // 	fm_print_js_select()
  // Returns js function to allow forms to send to various pages
  //	fm_print_js_sendform($tourl,$formname)
  // Returns js function to execute pages according to drop-menu selection
  //	fm_print_js_amenu()
  // Returns js function to execute pages according to drop-menu selection from view_shared.php
  //	fm_print_js_amenushared()
  /****************************************************************************/

  function fm_print_js_popup() {
    $retval = "<script language=\"javascript\">
          <!--
            // Opens a popup window
            function popup(url) {
              window.open(url, \"Popup\", \"height=450, width=425, scrollbars=yes\");
            }
          -->
          </script>";
    return $retval;
  }

  function fm_print_js_select() {
    $retval = "<script language=\"javascript\">
          <!--
            // selects all the checkboxes for the form
            function selectboxes(allbox, whichform) {
              if (whichform == 1) {
                for (var i = 0; i < document.linkform[\"cb[]\"].length; i++) {
                  document.linkform[\"cb[]\"][i].checked = allbox.checked;
                }
              }
              if (whichform == 2) {
                for (var i = 0; i < document.catform[\"cb[]\"].length; i++) {
                  document.catform[\"cb[]\"][i].checked = allbox.checked;
                }
              }
            }
          -->
          </script>";
    return $retval;
  }

  // $tourl		= to what page (entire url)
  // $formname	= name of the form to submit to
  function fm_print_js_sendform($tourl, $formname) {
    $retval = "<script language=\"javascript\">
          <!--
            // sets form's url
            function sendForm() {
              document.$formname.action = '$tourl';
              document.$formname.submit();
            }
          -->
          </script>";
    return $retval;
  }

  function fm_print_js_amenu() {
    $retval = "<script language=\"javascript\">
          <!--
            // submits to linkform according to what was selected
            function alinkmenu() {
              sel = document.linkform.menulinksel;
              dest = sel.options[sel.selectedIndex].value;
              if (dest != '' && dest != 'sepsel') {
                nonechecked = true;
                for (var i = 0; i < document.linkform[\"cb[]\"].length; i++) {
                  if (document.linkform[\"cb[]\"][i].checked) {
                    nonechecked = false;
                    i = document.linkform[\"cb[]\"].length;
                  }
                }
                if (nonechecked == false) {
                  document.linkform.action = dest;
                  document.linkform.submit();
                } else {
                  document.linkform.menulinksel.value = '';
                }
              } else {
                document.linkform.menulinksel.value = '';
              }
            }

            // submits to cat form
            function acatmenu() {
              sel = document.catform.menucatsel;
              dest = sel.options[sel.selectedIndex].value;
              if (dest != '' && dest != 'sepsel') {		// If default or separator are not selected
                nonechecked = true;					// Flag to ensure at least 1 box is checked
                for (var i = 0; i < document.catform[\"cb[]\"].length; i++) {
                  if (document.catform[\"cb[]\"][i].checked) {
                    nonechecked = false;
                    i = document.catform[\"cb[]\"].length;
                  }
                }
                if (nonechecked == false) {				// If at least 1 is checked, process, otherwise reset menu
                  document.catform.action = dest;
                  document.catform.submit();
                } else {
                  document.catform.menucatsel.value = '';
                }
              } else {
                document.catform.menucatsel.value = '';
              }
            }
          -->
          </script>";
    return $retval;
  }

  function fm_print_js_amenushared() {
    $retval = "<script language=\"javascript\">
          <!--
            // submits to viewshared form
            function amenushared() {
              sel = document.sharedform.menusharedsel;
              dest = sel.options[sel.selectedIndex].value;
              if (dest != '' && dest != 'sepsel') {
                nonechecked = true;
                for (var i = 0; i <document.sharedform[\"cb[]\"].length; i++) {
                  if (document.sharedform[\"cb[]\"][i].checked) {
                    nonechecked = false;
                    i = document.sharedform[\"cb[]\"].length;
                  }
                }
                if (nonechecked == false) {
                  document.sharedform.action = dest;
                  document.sharedform.submit();
                } else {
                  document.sharedform.menusharedsel.value = '';
                }
              } else {
                document.sharedform.menusharedsel.value = '';
              }
            }
          -->
          </script>";
    return $retval;
  }

?>