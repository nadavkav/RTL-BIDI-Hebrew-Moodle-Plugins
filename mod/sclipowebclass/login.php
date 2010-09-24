<?php // $Id: login.php,v 1.3 2009/09/16 11:47:47 alexsclipo Exp $

//  Moves, adds, updates, duplicates or deletes modules in a course

    require("../../config.php");
    require_once("lib.php");

    require_login();

	$email = $_POST["userEmail"];
	$pass = $_POST["userPass"];

	$redirectpage = $_POST["redirectpage"];
	if ($_REQUEST["showadd"] == 1)
		$redirectpage .= '&showadd=1';

	$id = $_POST["id"];
    $sectionreturn = optional_param('sr', '', PARAM_INT);
    $add           = optional_param('add','', PARAM_ALPHA);
    $type          = optional_param('type', '', PARAM_ALPHA);
    $indent        = optional_param('indent', 0, PARAM_INT);
    $update        = optional_param('update', 0, PARAM_INT);
    $hide          = optional_param('hide', 0, PARAM_INT);
    $show          = optional_param('show', 0, PARAM_INT);
    $copy          = optional_param('copy', 0, PARAM_INT);
    $moveto        = optional_param('moveto', 0, PARAM_INT);
    $movetosection = optional_param('movetosection', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $course        = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $duplicate     = optional_param('duplicate', 0, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
    $cancelcopy    = optional_param('cancelcopy', 0, PARAM_BOOL);

	require("sclipoapi.php");

	// Check if login information is correct
	$sessionid = sclipo_login($email, $pass, $USER->username);

	if ($sessionid != -1) {
		$_SESSION["sclipo_id"] = $sessionid;
		$good_login = 1;

		if ($_POST["delete"]==1) {
			redirect($redirectpage, "Redirecting, please wait ...", 0);
			exit();
		}
	}
	else
		$good_login = 0;

    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $mod = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $mod = (object)$_POST;
    }

	  if (! $course = get_record("course", "id", $id)) {
            error("This course doesn't exist:");
        }

        if (! $module = get_record("modules", "name", $add)) {
            error("This module type doesn't exist");
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        if (!course_allowed_module($course,$module->id)) {
            error("This module has been disabled for this particular course");
        }

	$form->coursemodule = $module->id;
   // $form->section      = $module->section;     // The section ID
    $form->course       = $course->id;
    $form->module       = $module->id;
    $form->modulename   = $module->name;
    //$form->instance     = $module->instance;
    $form->mode         = "update";
    $form->sesskey      = !empty($USER->id) ? $USER->sesskey : '';

    $navlinks = array();
    $navlinks[] = array('name' => "Sclipo Live Web Classes", 'link' => "$CFG->wwwroot/mod/$module->name/index.php?id=$course->id", 'type' => 'activity');
	$navlinks[] = array('name' => "Create & Schedule Web Classes", 'link' => '', 'type' => 'action');
    $navigation = build_navigation($navlinks);

    print_header_simple("Sclipo", '', $navigation, "", "", false);

    $modform = $CFG->dirroot."/mod/sclipowebclass/mod.html";
	if (empty($delete))
		$nextpage = $CFG->wwwroot."/mod/sclipowebclass/".$redirectpage;
    if (file_exists($modform)) {

        $icon = '<img class="icon" src="'.$CFG->modpixpath.'/sclipowebclass/icon.gif" alt="'.get_string('modulename',"sclipowebclass").'"/>';

        print_heading_with_help("Create & Schedule Your Sclipo Web Classes", "mods", "sclipowebclass", $icon);
        print_simple_box_start('center', '', '', 5, 'generalbox', "sclipowebclass");

		if ($good_login == 1)
			redirect($nextpage,"Redirecting, please wait ...",0);
		else {
			$wrong_login = 1;
			include_once($modform);
		}
		print_simple_box_end();

    } else {
        notice("This module cannot be added to this course yet! (No file found at: $modform)", "$CFG->wwwroot/course/view.php?id=$course->id");
    }

    print_footer($course);
?>