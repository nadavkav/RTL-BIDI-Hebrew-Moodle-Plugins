<?php  

/**
* Allows teachers to designate maximum upload sizes and directory sizes for 
* students in their course
* @package block_file_manager
* @category block
* @author
* 
*/
	
	require_once("../../config.php");
	require_once($CFG->dirroot.'/blocks/file_manager/lib.php');
	
	$id             = required_param('id', PARAM_INT); // the current block id
	$maxbytes       = optional_param('maxbytes', 0, PARAM_INT);
	$maxdir         = optional_param('maxdir', 0, PARAM_INT);
	$sharetoany     = optional_param('sharetoany', 0, PARAM_INT);
	$allowsharing   = optional_param('allowsharing', 1, PARAM_INT);
	$enablefmanager = optional_param('enablefmanager', 1, PARAM_INT);
	$tab2           = optional_param('tab2', 'students', PARAM_ALPHA);
	$tab            = optional_param('tab', 'files', PARAM_ALPHA);
	$action         = optional_param('what', '', PARAM_ALPHA);
	$fmdir          = fm_get_root_dir();

	$userinttype = 2;
	if ($tab2 == 'students') {
		$userinttype = 2;
	} elseif ($tab2 == 'teachers') {
		$userinttype = 1;
	} elseif ($tab2 == 'admins') {
		$userinttype = 0;
	}
	
    if (! $course = get_record('course', 'id', $id) ) {
        error("Invalid Course ID", "view.php?id={$id}&rootdir={$rootdir}");
    }
	require_login($course->id);	
	
	$systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);

	// If they are not admin and get to this page, error is displayed and error will be logged
	if (!has_capability("moodle/site:doanything", $systemcontext )) {
		error("You do not have administrative privilege.");
	}
	
	$stradminsettings = get_string('adminsettings', 'block_file_manager');

    $strtitle = get_string('msgadminsetinstruct','block_file_manager');
    $navigation = build_navigation(array(array('name'=>$strtitle, 'link'=>null,'type'=>'misc')));
    print_header($strtitle, format_string($course->fullname), $navigation, '', '', false, "&nbsp;", "&nbsp;");

	print_heading($stradminsettings);

	if (!empty($action)) include 'admin_settings.controller.php';

	$options = get_max_upload_sizes();
	
	// Display neat tabs
    $tabs = array();
	$tabs[0][] = new tabobject('files', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab=files&tab2=students',
				get_string('btnfileuploads','block_file_manager'));
	$tabs[0][] = new tabobject('sharing', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab=sharing&tab2=students',
				get_string('btnfilesharing','block_file_manager'));
	$tabs[0][] = new tabobject('security', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab=security&tab2=students',
				get_string('btnsecurity','block_file_manager'));

	$tabs[1][] = new tabobject('students', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab='.$tab.'&tab2=students', 
				get_string('btnstudents', 'block_file_manager'));
	$tabs[1][] = new tabobject('teachers', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab='.$tab.'&tab2=teachers', 
				get_string('btnteachers', 'block_file_manager'));
	$tabs[1][] = new tabobject('admins', $CFG->wwwroot.'/blocks/file_manager/admin_settings.php?id='.$id.'&tab='.$tab.'&tab2=admins',
				get_string('btnadmins', 'block_file_manager'));

	$root = array($tab);
	print_tabs($tabs, $tab2, NULL, $root, false);
			
	print_simple_box_start('center', '400');
	echo "<br/>";
		
	echo "<form name=\"form1\" method=\"post\" action=\"{$CFG->wwwroot}/blocks/file_manager/admin_settings.php\">";
    echo "<input type=\"hidden\" name=\"what\" value=\"update\" />";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "<input type=\"hidden\" name=\"tab\" value=\"$tab\" />";
    echo "<input type=\"hidden\" name=\"tab2\" value=\"$tab2\" />";
	echo "<center>";
	
	// FILES SECTION
	if ($tab == 'files') {
		$filemenuname = 'maxbytes';
		$dirmenuname = 'maxdir';
		$selected1 = '';
		$selected2 = '';
		// Selects existing values if they are present
		if ($maxf = get_record('fmanager_admin', 'usertype', $userinttype)) {
			$selected1 = $maxf->maxupload;
			$selected2 = $maxf->maxdir;
		}
		echo get_string('maxup', 'block_file_manager').": <br/>";
		choose_from_menu($options, $filemenuname, $selected1, '');

		echo "<br/><br/>";

		$opt[0] = get_string('unlimited');
		//$opt[1] = "1Mb";
		//$opt[2] = "2Mb";
		$opt[5] = "5MB";
		$opt[10] = "10MB";
		$opt[20] = "20MB";
		$opt[50] = "50MB";
		$opt[100] = "100MB";
		$opt[250] = "250MB";
		$opt[500] = "500MB";
		$opt[750] = "750MB";
		$opt[1000] = "1GB";
		echo get_string('maxdir', 'block_file_manager').": <br/>";
		choose_from_menu($opt, $dirmenuname, $selected2, '');
	}
	
	// SHARING SECTION
	if ($tab == 'sharing') {
		$opt = NULL;
		$opt[0] = get_string('no');
		$opt[1] = get_string('yes');
		$selected1 = '';
		$selected2 = '';
		// Select existing values
		if ($share = get_record('fmanager_admin', 'usertype', $userinttype)) {
			$selected1 = $share->sharetoany;
			$selected2 = $share->allowsharing;
		}		
		echo get_string("allowsharing", 'block_file_manager')."?: <br/>";
		choose_from_menu($opt, 'allowsharing', $selected2, '');
		echo "<br/><br/>".get_string('sharetoany','block_file_manager')."?: <br/>";
		choose_from_menu($opt, "sharetoany", $selected1, '');
		echo "<br/>";
	}
	// SECURITY SECTION
	if ($tab == "security") {
		$opt = NULL;
		$opt[0] = get_string('no');
		$opt[1] = get_string('yes');
		$selected1 = "";
		// Select existing values
		if ($secure = get_record('fmanager_admin', 'usertype', $userinttype)) {
			$selected1 = $secure->enable_fmanager;
		}
		echo get_string('enablefmanager', 'block_file_manager').": <br/>";
		choose_from_menu($opt, 'enablefmanager', $selected1, '');
		echo "<br/>";
	}
	echo "</center>";
	$strcancel = get_string('btncancel', 'block_file_manager');
	$strupdate = get_string('btnupdate', 'block_file_manager');
	$strdone = get_string('btndone', 'block_file_manager');
	echo "<br/><center><input type=\"submit\" name=\"update\" value=\"$strupdate\" /></center> ";
	echo "</form>";
	print_simple_box_end();	
	echo "<br/>";
	echo "<form name=\"form2\" method=\"post\" action=\"$SESSION->fromdiscussion\">";
	echo "<center><input type=\"submit\" name=\"done\" value=\"$strdone\" />";
	echo "&nbsp;&nbsp;<input type=\"submit\" name=\"cancel\" value=\"$strcancel\" /></center>";
	echo "</form>";

	print_footer();				   
?>