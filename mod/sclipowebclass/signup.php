<?php // $Id: signup.php,v 1.3 2009/09/16 11:47:47 alexsclipo Exp $

//  Moves, adds, updates, duplicates or deletes modules in a course

    require("../../config.php");
    require_once("lib.php");
	require_once("sclipoapi.php");

    require_login();

	$email = $_POST["userEmail"];
	$pass = $_POST["userPass"];
	$firstname = $_POST["first_name"];
	$lastname = $_POST["last_name"];
	$gender = $_POST["gender"];
	$bMonth = $_POST["birth_Month"];
	$bDay = $_POST["birth_Day"];
	$bYear = $_POST["birth_Year"];

	$redirectpage = $_POST["redirectpage"];

	$id = required_param('id',PARAM_INT);
	$add = $_POST["add"];
    $section = required_param('section',PARAM_INT);

        if (! $course = get_record("course", "id", $id)) {
            error("This course doesn't exist");
        }

        if (! $module = get_record("modules", "name", $add)) {
            error("This module type doesn't exist");
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        if (!course_allowed_module($course,$module->id)) {
            error("This module has been disabled for this particular course");
        }

	//$form->coursemodule = $cm->id;
    //$form->section      = $cm->section;     // The section ID
    $form->course       = $course->id;
    $form->module       = $module->id;
    $form->modulename   = $module->name;
    //$form->instance     = $cm->instance;
    $form->mode         = "update";
    $form->sesskey      = !empty($USER->id) ? $USER->sesskey : '';

    $navlinks = array();
    $navlinks[] = array('name' => "Sclipo Live Web Classes", 'link' => "$CFG->wwwroot/mod/$module->name/index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => "Create & Schedule Web Classes", 'link' => '', 'type' => 'action');
    $navigation = build_navigation($navlinks);

	// Try to signup
	$sessionid = sclipo_signup($email, $pass, $USER->username, $firstname, $lastname, $gender, $bDay, $bMonth, $bYear);

    print_header_simple("Sclipo", '', $navigation, "", "", false);

	if ($sessionid != -1) {
		$_SESSION["sclipo_id"] = $sessionid;
		$good_login = 1;
	}
	else
		$wrong_signup = 1;

    $modform = $CFG->dirroot."/mod/$module->name/mod.html";
	$nextpage = $CFG->wwwroot."/mod/$module->name/".$redirectpage;
    if (file_exists($modform)) {
        $icon = '<img class="icon" src="'.$CFG->modpixpath.'/'.$module->name.'/icon.gif" alt="'.get_string('modulename',$module->name).'"/>';

        print_heading_with_help("Create & Schedule Your Sclipo Web Classes", "mods", $module->name, $icon);
        print_simple_box_start('center', '', '', 5, 'generalbox', $module->name);
		if (isset($good_login) && $good_login == 1)
			redirect($nextpage, "Redirecting, please wait ...", 0);
		else {
			$wrong_signup = 1;
			include_once($modform);
		}
		print_simple_box_end();

    } else {
        notice("This module cannot be added to this course yet! (No file found at: $modform)", "$CFG->wwwroot/course/view.php?id=$course->id");
    }

    print_footer($course);
?>