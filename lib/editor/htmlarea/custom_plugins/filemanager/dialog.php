<?php // $Id: insert_table.php,v 1.4 2007/01/27 23:23:44 skodak Exp $
    require_once("../../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    require_course_login($id);
    @header('Content-Type: text/html; charset=utf-8');

    //include $CFG->dirroot."/blocks/file_manager/print_lib.php";
    include $CFG->dirroot."/blocks/file_manager/lib.php";

     // $id		= course id
  // $groupid		= group id if we want to see files of this group
  // $rootdir	= target directory
  function filemanager_print_user_files_form($id=1, $rootdir=0, $action='none', $groupid=0,$readonlyaccess=false) {
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
      $table->head = array($strcbx, $strnamename, $strcatname, $strfilesizename, $strdatename, $stractionname);
    }else{
      $table->head = array("", $strnamename, $strcatname, $strfilesizename, $strdatename);
    }
    $table->align = array("center", "left", "center", "center", "center");
    $table->width = "90%";
    $table->size = array("5%", "20%", "15%", "10%", "10%", "12%");
    $table->wrap = array(NULL, 'no', 'no', 'no', 'no');
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
      $table->data[] = array("", $name, "", "", "","", "");
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
        $cbx = "<input type=\"checkbox\" name=\"".format_text($folder->name,FORMAT_PLAIN)."\" value=\"fold$folder->id\">";
          $actions = "<a title=\"".get_string('edit')."\" href=\"folder_manage.php?id=$id&groupid=$groupid&foldid=$folder->id&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->pixpath/i/edit.gif\" alt=\"" . get_string("edit"). "\" /></a>&nbsp;";
          // deleting shared resources is disabled, from this dialog
          //<a title=\"".get_string('delete')."\" href=\"conf_delete.php?id=$id&groupid=$groupid&from=folder&fromid=$folder->id&rootdir=$rootdir\"><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" . get_string("delete"). "\" /></a>";
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
          $table->data[] = array($cbx, $name, $catname,  $filesize, $date, $actions);
        }else{
          $table->data[] = array("", $name, $catname,  $filesize, $date);
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
        $cbx = "<input type=\"checkbox\" name=\"".format_text($link->name,FORMAT_PLAIN)."\" value=\"{$link->id}\" />";
        $actions = "<a title=\"".get_string('edit')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/link_manage.php?id={$id}&groupid={$groupid}&linkid={$link->id}&rootdir={$rootdir}\"><img border=\"0\" src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"" . get_string('edit'). "\" /></a>&nbsp;";
        // deleting shared resources is disabled, from this dialog
        //<a title=\"".get_string('delete')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/conf_delete.php?id=$id&groupid={$groupid}&from=link&fromid={$link->id}&rootdir={$rootdir}\"><img border=\"0\" src=\"../file_manager/pix/delete.gif\" alt=\"" . get_string('delete'). "\"></a>";
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
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('sharetoany','block_file_manager')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/sharing.php?id=$id&groupid={$groupid}&linkid=$link->id&from='link'&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('sharetoany','block_file_manager')."\"></a>";
          }
        } else {
          if ($priv->allowsharing == 1) {
            $actions .= "&nbsp;&nbsp;<a title=\"".get_string('msgsharetoothers','block_file_manager')."\" href=\"{$CFG->wwwroot}/blocks/file_manager/sharing.php?id=$id&groupid={$groupid}&linkid=$link->id&from='link'&rootdir=$rootdir\"><img border=\"0\" src=\"$CFG->wwwroot/blocks/file_manager/pix/".$icon."\" alt=\"".get_string('msgsharetoothers','block_file_manager')."!\"></a>";
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
          $table->data[] = array($cbx, $name, $catname,  $filesize, $date, $actions);
        }else{
          $table->data[] = array("", $name, $catname,  $filesize, $date);
        }
      }
    }
    return $table;
  }
  print_header_simple();
  echo "<form id=\"filemanager\" method=\"post\" action=\"dialog.php\">";
  print_table( filemanager_print_user_files_form($id));
  echo "<input type=\"button\" onclick=\"onOK();\" value=\"".get_string('add')."\">";
  echo "</form>";
  print_footer();
?>

<script type="text/javascript">
//<![CDATA[

function Init() {
  var param = window.dialogArguments;
  /*
  if (param) {
      var alt = param["f_url"].substring(param["f_url"].lastIndexOf('/') + 1);
      document.getElementById("f_url").value = param["f_url"];
      document.getElementById("f_alt").value = param["f_alt"] ? param["f_alt"] : alt;
      document.getElementById("f_border").value = parseInt(param["f_border"] || 0);
      window.ipreview.location.replace('preview.php?id='+ <?php print($course->id);?> +'&imageurl='+ param.f_url);
  }
*/
  document.getElementById('filelink').focus();
};

function onOK() {
//  var required = {
//    "filelink": "You should better choose some files, before we move on..."
//  };
//  for (var i in required) {
//    var el = document.getElementById(i);
//    if (!el.innerHTML) {
//      alert(required[i]);
//      el.focus();
//      return false;
//    }
//  }

  var param = new Object();
  var inputs = document.getElementsByTagName('input');
  for(var i = 0; i < inputs.length; i++) {
   if (inputs[i].checked == true){
     //alert(inputs[i].value);
     param[i] = '<a target="_new" href="<?php echo $CFG->wwwroot; ?>/blocks/file_manager/file.php?cid=<?php echo $id; ?>&groupid=0&fileid='+inputs[i].value+'">'+inputs[i].name+'</a>';
   };
  }

//  var fields = ["filelink"];
//  var param = new Object();
//  for (var i in fields) {
//    var id = fields[i];
//    var el = document.getElementById(id);
//    param[id] = el.innerHTML;
//    //alert(document.getElementById('objective').innerHTML);
//  }

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function onCancel() {
//  if (preview_window) {
//    preview_window.close();
//  }
  window.close();
  return false;
};
//[[>
</script>