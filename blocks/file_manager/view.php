<?php

/**
* view.php
*
* This file allows a user to view a list of their links and
* Gives them the ability to manage many aspects of their files,
* Including sharing to other users, organizing, submitting to
* course assignments, etc.
*
* @package block_file_manager
* @category blocks
*/
    // include("debugging.php");

    require_once("../../config.php");
    require_once($CFG->dirroot.'/blocks/file_manager/lib.php');
    require_once($CFG->dirroot.'/blocks/file_manager/print_lib.php');

    $id      = required_param('id', PARAM_INT); // The Id of the current course
    $rootdir = optional_param('rootdir', '0', PARAM_INT);		// 0 == root
    $groupid = optional_param('groupid', '0', PARAM_INT);		// 0 == no group then display user's directory
    $action  = optional_param('what', '', PARAM_ACTION);

    $readonlyaccess = false; // Says if we are in read-only access

    if (! $course = get_record('course', 'id', $id) ) {
        error('That\'s an invalid course id', "view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
    }

    require_login($course->id);

    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);
    $canmanagegroups = has_capability('block/file_manager:canmanagegroups', $coursecontext);

	$cb = fm_clean_checkbox_array();
	if ($cb != NULL) {
		$USER->fm_cb = $cb;
	}

/// Ensures the user is able to view the fmanager
	fm_check_access_rights($course->id);

	if ($groupid == 0){
		// Ensures user owns the folder
		fm_user_owns_folder($rootdir);
	} else {
		// Ensures group owns the folder
		fm_group_owns_folder($rootdir, $groupid);

		// Depending on the groupmode, ensures that the user is member of the group and is allowed to access
		if (function_exists('build_navigation')){
		    $groupmode = groups_get_course_groupmode($course);
		} else {
		    // 1.8 backward compatibility
		    // TODO : remove that option in further releases
		    $groupmode = groupmode($course);
		}

		switch ($groupmode){
			case NOGROUPS :
                // Should no to be there ...
                error(get_string('errnogroups', 'block_file_manager'), "{$CFG->wwwroot}/course/view.php?id={$course->id}");
                break;
            case SEPARATEGROUPS :
                if (!$canmanagegroups && !groups_is_member($groupid)){ // Must check if the user is member of that group
                    error(get_string('errnotmember', 'block_file_manager'), "{$CFG->wwwroot}/course/view.php?id={$course->id}");
                }
                break;
            case VISIBLEGROUPS :
                if (!$canmanagegroups && !groups_is_member($groupid)){ // It is ok, no check needed for read-only access
                    $readonlyaccess = true;
                }
                break;
        }
    }

	if (isset($_POST['newlink'])) {
		redirect("link_manage.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
	} elseif (isset($_POST['newfolder'])) {
		redirect("folder_manage.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$rootdir}");
	} elseif (isset($_POST['btnmovehere'])) {
		$cb = $USER->fm_cb;
		foreach($cb as $c) {
			fm_move_to_folder($c, $rootdir, $groupid);
		}
	}

	// Prints the folders breadcrumb navigation links
    $nav = array();
    $tmplink = '';
    if ($rootdir != 0) { // if we are in another folder than the root of the user or group
		$folder = get_record('fmanager_folders', 'id', $rootdir);
		$tmplink = $action;
		if ($action != '') {
			$tmplink = '&amp;what='.$tmplink;
		}

		while ($folder->pathid != 0) {
            $nav[] = array('name'=>format_text($folder->name,FORMAT_PLAIN), 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$folder->id}{$tmplink}", 'type'=>'misc');
			$folder = get_record('fmanager_folders','id',$folder->pathid);
		}
        $nav[] = array('name'=>format_text($folder->name,FORMAT_PLAIN), 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$folder->id}{$tmplink}", 'type'=>'misc');
    }

    $strtitle = get_string('filemanager','block_file_manager');
    $nav[] = array('name'=>$strtitle, 'link'=>"view.php?id={$id}&amp;groupid={$groupid}&amp;rootdir={$tmplink}", 'type'=>'misc');
    $nav = array_reverse($nav);
    $nav[] = array('name'=>get_string('myfiles', 'block_file_manager'), 'link'=>null, 'type'=>'misc');
    $navigation = build_navigation($nav);
    print_header($strtitle, format_string($course->fullname), $navigation, "", "", false, "&nbsp;", "&nbsp;");

    if (right_to_left()) { // rtl support for table cell alignment (nadavkav patch)
      $alignmentleft = 'right';
      $alignmentright = 'left';
    } else {
      $alignmentleft = 'left';
      $alignmentright = 'right';
    }

// start page content

	print_heading(get_string('myfiles', 'block_file_manager'));
    if ($readonlyaccess){
		print_simple_box( text_to_html(get_string('msgreadonly', 'block_file_manager')) , 'center', '620');
	}
    echo "<br/>";
    print_simple_box( text_to_html(get_string('msgexplainmanager','block_file_manager')) , 'center', '620');
	echo "<br/>";
	echo fm_print_js_select();
	echo fm_print_js_amenu();
?>
<div style="<?php echo $alignmentleft; ?>">
<form name="linkform" action="view.php" method="post">
<input type="hidden" name="id" value="<?php p($id) ?>" />
<input type="hidden" name="groupid" value="<?php p($groupid) ?>" />
<input type="hidden" name="rootdir" value="<?php p($rootdir) ?>" />
<i>
<?php
	switch($action) {
		case 'movesel':
			$count = 0;
			foreach ($USER->fm_cb as $c) {
				if ($c != 0 || substr($c,0,2) == "f-") {
					$count++;
				}
			}
			echo $count.get_string('msgmovetohere','block_file_manager');
			break;
	}
?>
</i>
</div>
<?php



if ($groupid == 0){
	// Prints user files management form
	print_table(fm_print_user_files_form($id, $rootdir, $action,0,$readonlyaccess));
	// Prints how much space user has left
	$tmpdir = $CFG->dataroot."/".fm_get_user_dir_space();
} else {
	// Prints group files management form
	print_table(fm_print_user_files_form($id, $rootdir, $action, $groupid,$readonlyaccess));
	// Prints how much space group has left
	$tmpdir = $CFG->dataroot."/".fm_get_group_dir_space($groupid);
}

$dirsize = fm_get_size($tmpdir, 1);
$usertype = fm_get_user_int_type();
$adminsettings = get_record('fmanager_admin', 'usertype', $usertype);
$sizeleft = get_string('directorysize', 'block_file_manager') .': <b><i>'.fm_readable_filesize($dirsize)."</i></b>";
// Defaults to dirsize if unlimited space
if ($adminsettings->maxdir != 0) {
	$sizeleft = ($adminsettings->maxdir * (1048576)) - $dirsize;
	$sizeleft = fm_readable_filesize($sizeleft);
	$sizeleft = get_string('remains', 'block_file_manager', $sizeleft);
}
echo "<p align=\"$alignmentleft\">$sizeleft</p>";
echo "<center>";
if ($action == 'movesel') {
	echo "<input type=\"submit\" value=\"".get_string('btnmovehere','block_file_manager')."\" name=\"btnmovehere\">&nbsp;&nbsp;";
}

if (!$readonlyaccess) {
?>
<table align="<?php echo $alignmentleft; ?>" width="10%">
    <tr>
        <td width="5%">&nbsp;

        </td>
        <td>
            <?php echo fm_print_actions_menu($id, 'link', $rootdir, $groupid) ?>
        </td>
        <td>
            <?php helpbutton('myfilesaction', get_string('menuhelp', 'block_file_manager'), 'block_file_manager'); ?>
      </td>
  </tr>
</table>
<br />
<br />
<?php
}

if (!$readonlyaccess) {
?>
<input type="submit" value="<?php print_string('btnnewfolder', 'block_file_manager') ?>" name="newfolder" />
&nbsp;
&nbsp;
<input type="submit" value="<?php print_string('btnnewlink', 'block_file_manager') ?>" name="newlink" />
</center>
</form>
<?php
}

echo "<br/><br/><br/>";
if (!$readonlyaccess) {
?>
<form name="catform" action="cat_manage.php" method="post">
<input type="hidden" name="id" value="<?php p($id) ?>" />
<input type="hidden" name="groupid" value="<?php p($groupid) ?>" />
<input type="hidden" name="rootdir" value="<?php p($rootdir) ?>" />
<div class="categories" style="text-align:right;">
<br /><br />


<?php
/*
	$tablehead = get_string('categories', 'block_file_manager');
	$table->head = array($tablehead);
	$table->align = array("left");
	$table->width = "400";
	$table->size = array("100%");
	$table->wrap = array('no');
	$table->data = array();

	$actions_menu = fm_print_actions_menu($id, 'category', $rootdir)." ".helpbutton('mycataction', get_string('menuhelp', 'block_file_manager'), 'filemanager');

	$table->data[] = array($actions_menu);

	print_table($table);
	*/
?>

<?php
}
// Displays the Category list table
echo "<div style=\"text-align:$alignmentleft\">";
print_table(fm_print_category_list($id, $rootdir, $groupid,$readonlyaccess));
if (!$readonlyaccess) {
?>

<table class="actions" align="<?php echo $alignmentleft; ?>" width="20%">
    <tr>
    <td width="5%">&nbsp;</td>
    <td colspan="2">
            <?php echo "<strong>".get_string('categories', 'block_file_manager')."</strong><br />"; ?>
        </td>
  </tr>
  <tr>
    <td width="5%">&nbsp;</td>
        <td>
            <?php echo fm_print_actions_menu($id, 'category', $rootdir, $groupid); ?>
        </td>
        <td>
            <?php helpbutton('mycataction', get_string('menuhelp', 'block_file_manager'), 'block_file_manager'); ?>
      </td>
  </tr>
</table>

<input type="submit" value="<?php print_string('btncreatenewcat', 'block_file_manager') ?>" name="newcat" />
</div>
</form>
<br />
<br />
</div>
<?php
}
//print_footer($course);
print_footer();
?>