<?php

//  Handles custom updated for the FN course format.

    require("../../../config.php");
    require("lib.php");

    require_login();

///     Handle activity complete.
///
    if (isset($_GET['rescomplete']) and confirm_sesskey()) {

        if (! $cm = get_record("course_modules", "id", $_GET['id'])) {
            error("This course module doesn't exist");
        }

        set_resource_complete($_GET['rescomplete'], $USER->id);
    } else if (isset($_GET['hidegrades']) and confirm_sesskey()) {

        if (! $cm = get_record("course_modules", "id", $_GET['id'])) {
            error("This course module doesn't exist");
        }

        if (!isadmin()) {
            error("You can't modify the gradebook settings!");
        }
   
        set_gradebook_for_module($cm->id, $_GET['hidegrades']);
    } else if (isset($_GET['mandatory']) and confirm_sesskey()) {

        if (! $cm = get_record("course_modules", "id", $_GET['id'])) {
            error("This course module doesn't exist");
        }

        if (!isadmin()) {
            error("You can't modify the mandatory settings!");
        }
   
        fn_set_mandatory_for_module($cm->id, $_GET['mandatory']);
    } else if (isset($_POST['sec0title'])) {
        if (!$course = get_record('course', 'id', $_POST['id'])) {
            error('This course doesn\'t exist.');
        }
        FN_get_course($course);
        $course->sec0title = $_POST['sec0title'];
        FN_update_course($course);
        $cm->course = $course->id;
    } else if (isset($_GET['openchat'])) {
        if (!$course = get_record('course', 'id', $_GET['id'])) {
            error('This course doesn\'t exist.');
        }
        if ($varrec = get_record('course_config_FN', 'courseid', $course->id, 'variable', 'classchatopen')) {
            $varrec->value = $_GET['openchat'];
            update_record('course_config_FN', $varrec);
        } else {
            $varrec->courseid = $course->id;
            $varrec->variable = 'classchatopen';
            $varrec->value = $_GET['openchat'];
            insert_record('course_config_FN', $varrec);
        }
        $cm->course = $course->id;
    }

    $site = get_site();
    if ($site->id == $cm->course) {
        redirect($CFG->wwwroot);
    } else {
        redirect($CFG->wwwroot.'/course/view.php?id='.$cm->course);
    }
    exit;
?>
